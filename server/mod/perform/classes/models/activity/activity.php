<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use container_perform\backup\backup_helper;
use container_perform\backup\restore_helper;
use container_perform\perform as perform_container;
use context_course;
use context_coursecat;
use context_module;
use core\entity\expandable;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\data_providers\activity\activity_settings;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_assignment;
use mod_perform\event\activity_created;
use mod_perform\models\activity\helpers\activity_clone;
use mod_perform\models\activity\helpers\activity_deletion;
use mod_perform\models\activity\helpers\activity_multisection_toggler;
use mod_perform\models\activity\helpers\general_info_validator;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use mod_perform\state\activity\active;
use mod_perform\state\activity\activity_state as activity_status;
use mod_perform\state\activity\draft;
use mod_perform\state\state;
use mod_perform\state\state_aware;
use mod_perform\user_groups\grouping;
use mod_perform\util;
use mod_perform\webapi\resolver\type\activity_state;
use moodle_exception;
use stdClass;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;

/**
 * Class activity
 *
 * The core performance activity object. It defines questions and users who can answer them.
 *
 * @property-read int $id ID
 * @property-read int $course
 * @property-read string $name
 * @property-read string $description
 * @property-read int $status
 * @property-read activity_state $state
 * @property-read activity_type $type
 * @property-read int $created_at
 * @property-read int $updated_at
 * @property-read collection|section[] $sections
 * @property-read collection|activity_manual_relationship_selection[] $manual_relationships
 * @property-read collection|track[] $tracks
 * @property-read activity_settings $settings
 * @property-read bool anonymous_responses
 * @property bool multisection_setting
 * @property-read bool $can_clone
 * @property-read int $context_id
 * @property-read perform_container $container
 *
 * @package mod_perform\models\activity
 */
class activity extends model {

    use state_aware;

    protected $entity_attribute_whitelist = [
        'id',
        'course',
        'name',
        'description',
        'anonymous_responses',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $model_accessor_whitelist = [
        'type',
        'sections',
        'settings',
        'manual_relationships',
        'tracks',
        'can_activate',
        'can_potentially_activate',
        'state_details',
        'multisection_setting',
        'can_clone',
        'default_track',
        'visibility_condition_options',
        'context_id',
        'container',
        'sections_ordered_with_respondable_element_count'
    ];

    public const NAME_MAX_LENGTH = 1024;

    /**
     * @var activity_entity
     */
    protected $entity;

    protected static function get_entity_class(): string {
        return activity_entity::class;
    }

    /**
     * Gets a model object based on the mod perform container (course) id.
     *
     * @param int $container_id
     * @return static
     */
    public static function load_by_container_id(int $container_id): self {
        $entity = activity_entity::repository()
            ->where('course', $container_id)
            ->one(true);

        return self::load_by_entity($entity);
    }

    /**
     * Gets a model object based on the course module id.
     *
     * @param int $course_module_id
     * @return static
     */
    public static function load_by_module_id(int $course_module_id): self {
        $entity = activity_entity::repository()
            ->join('course_modules', 'course', 'course')
            ->where('course_modules.id', $course_module_id)
            ->one(true);

        return self::load_by_entity($entity);
    }

    /**
     * Get the course container for this activity.
     *
     * @return perform_container
     */
    public function get_container(): perform_container {
        return perform_container::from_activity($this);
    }

    /**
     * Checks whether the logged in (or given) user has the capability to create the activity.
     *
     * @param int|null $userid
     * @param context_coursecat|null $context
     *
     * @return bool
     */
    public static function can_create(int $userid = null, context_coursecat $context = null): bool {
        global $USER;

        if (null == $userid) {
            // Including zero check
            $userid = $USER->id;
        }

        if (null == $context) {
            $categoryid = util::get_default_category_id();
            if (0 == $categoryid) {
                // Nope, this user is not able to add a performance activity.
                return false;
            }

            $context = context_coursecat::instance($categoryid);
        }

        return has_capability('mod/perform:create_activity', $context, $userid) &&
            perform_container::can_create_instance($userid, $context);
    }

    /**
     * Checks whether the logged in (or given) user has the capability to manage this activity.
     *
     * @param int|null $userid
     *
     * @return bool
     */
    public function can_manage(int $userid = null): bool {
        global $USER;

        $userid = $userid ?? $USER->id;
        return has_capability('mod/perform:manage_activity', $this->get_context(), $userid);
    }

    /**
     * Create activity on perform container
     *
     * @param perform_container $container
     * @param string $name
     * @param activity_type $type
     * @param string|null $description
     * @param int|null $status
     *
     * @return static
     */
    public static function create(
        perform_container $container,
        string $name,
        activity_type $type,
        string $description = null,
        int $status = null
    ): self {
        global $DB;

        if ($status && $status !== draft::get_code() && $status != active::get_code()) {
            throw new coding_exception('Invalid activity status given');
        }

        $modinfo = new stdClass();
        $modinfo->modulename = 'perform';
        $modinfo->course = $container->id;
        $modinfo->name = $name;
        $modinfo->timemodified = time();
        $modinfo->visible = true;
        $modinfo->section = 0;
        $modinfo->groupmode = 0;
        $modinfo->groupingid = 0;

        $entity = new activity_entity();

        $entity->course = $container->id;
        $entity->name = $name;
        $entity->description = $description;
        $entity->status = $status ?? draft::get_code();
        $entity->type_id = $type->id;

        return $DB->transaction(function () use ($entity, $modinfo, $container) {
            global $CFG, $USER;

            $entity->save();

            $modinfo->instanceid = $entity->id;
            $container->add_module($modinfo);

            $container_context = $container->get_context();
            // If the user does not have the manage capability from a higher context already
            // assign him the role so that he can manage the activity in the future.
            if (!empty($CFG->performanceactivitycreatornewroleid)
                && !is_viewing($container_context, null, 'mod/perform:manage_activity')
            ) {
                role_assign($CFG->performanceactivitycreatornewroleid, $USER->id, $container_context);
            }

            $activity = self::load_by_entity($entity);
            $activity->create_default_manual_relationships();

            $created_event = activity_created::create_from_activity($activity);
            $created_event->trigger();

            return $activity;
        });
    }

    /**
     * Creates the default manual relationships for the activity as the subject.
     *
     * @return void
     */
    private function create_default_manual_relationships(): void {
        $subject_relationship = relationship::load_by_idnumber('subject');
        $manual_relationships = (new relationship_provider())
            ->filter_by_component('mod_perform')
            ->filter_by_type(relationship_entity::TYPE_MANUAL)
            ->get();

        foreach ($manual_relationships as $manual_relationship) {
            (new manual_relationship_selection(
                [
                    'activity_id' => $this->id,
                    'manual_relationship_id' => $manual_relationship->id,
                    'selector_relationship_id' => $subject_relationship->id,
                ]
            ))->save();
        }
    }

    /**
     * Forces the model to reload its data from the repository.
     *
     * @param bool $with_relationships defaults to false, use with care as it triggers additional queries
     * @return activity
     */
    public function refresh(bool $with_relationships = false): self {
        $this->entity->refresh();
        if ($with_relationships) {
            if ($this->entity->relation_loaded('relationships')) {
                $this->entity->load_relation('relationships');
            }
            if ($this->entity->relation_loaded('sections_ordered')) {
                $this->entity->load_relation('sections_ordered');
            }
            if ($this->entity->relation_loaded('tracks')) {
                $this->entity->load_relation('tracks');
            }
            if ($this->entity->relation_loaded('settings')) {
                $this->entity->load_relation('settings');
            }
            if ($this->entity->relation_loaded('sections_ordered_with_respondable_element_count')) {
                $this->entity->load_relation('sections_ordered_with_respondable_element_count');
            }
        }
        return $this;
    }

    /**
     * Return the context object for this activity.
     */
    public function get_context(): context_module {
        global $USER;

        $mod_info = get_fast_modinfo($this->course, $USER->id);
        $instances = $mod_info->get_instances_of('perform');
        if (!array_key_exists($this->id, $instances)) {
            throw new moodle_exception('invalidmoduleid', 'error', $this->id);
        }
        $cm = $instances[$this->id];

        return context_module::instance($cm->id);
    }

    /**
     * Gets the id of the context
     *
     * @return int
     */
    public function get_context_id(): int {
        return $this->get_context()->id;
    }

    /**
     * Set general activity details.
     *
     * @param string $name
     * @param string|null $description
     * @param int|null $type_id
     * @return $this
     */
    public function set_general_info(string $name, ?string $description, ?int $type_id): self {
        $validator = new general_info_validator($this, $name, $description, $type_id);
        $errors = implode(', ', $validator->validate()->all());
        if ($errors) {
            throw new coding_exception("The following errors need to be fixed: '$errors'");
        }

        $entity = $this->entity;
        $entity->name = $name;
        $entity->description = $description;

        if ($type_id) {
            $entity->type_id = $type_id;
        }

        return $this;
    }

    /**
     * Set attribution settings.
     *
     * @param bool $anonymous_responses
     * @return $this
     */
    public function set_anonymous_setting(bool $anonymous_responses): self {
        if ($this->is_active()) {
            throw new coding_exception('Attribution settings can not be updated when an activity is active');
        }

        $this->entity->anonymous_responses = $anonymous_responses;

        return $this;
    }

    public function update(): self {
        builder::get_db()->transaction(function () {
            $this->update_container();
            $this->entity->update();
        });

        $this->entity->load_relation('type');

        return $this;
    }

    /**
     * Update the container course record to be in sync with the activity.
     */
    protected function update_container(): void {
        $to_update = [];

        // Update the name if it has changed.
        if (isset($this->entity->get_dirty()['name'])) {
            $to_update['fullname'] = $this->entity->name;
        }

        if (!empty($to_update)) {
            $container = $this->get_container();
            $container->update((object) array_merge($to_update, ['id' => $container->id]));
        }
    }

    /**
     * Get the sections for this activity.
     *
     * @return collection|section[]
     */
    public function get_sections(): collection {
        return $this->entity->sections_ordered->map_to(section::class);
    }

    /**
     * Checks whether the current user can view the participation reporting
     *
     * @return bool
     */
    public function can_view_participation_reporting(): bool {
        return has_capability('mod/perform:view_participation_reporting', $this->get_context());
    }

    /**
     * Returns the activity type.
     *
     * @return activity_type the type.
     */
    public function get_type(): activity_type {
        return activity_type::load_by_entity($this->entity->type);
    }

    /**
     * Get the tracks of this activity.
     *
     * @return collection|track[]
     */
    public function get_tracks(): collection {
        return $this->entity->tracks->map_to(track::class);
    }

    /**
     * @return track|null
     */
    public function get_default_track(): ?track {
        return $this->get_tracks()->first();
    }

    /**
     * Returns whether this activity is still in draft state
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->get_status_state() instanceof draft;
    }

    /**
     * Returns whether this activity is in active state
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->get_status_state() instanceof active;
    }

    /**
     * Can this activity be potentially activated, meaning that the user has
     * the correct capability and it is in the right state.
     * This does not check the conditions to make the change.
     *
     * @param int|null $user_id defaults to current user
     * @return bool
     */
    public function can_potentially_activate(int $user_id = null): bool {
        return $this->can_manage($user_id) && $this->get_status_state()->can_potentially_activate();
    }

    /**
     * Getter method for @see can_potentially_activate
     * @return bool
     */
    public function get_can_potentially_activate(): bool {
        return $this->can_potentially_activate();
    }

    public function get_state_details() {
        return $this->get_status_state();
    }

    /**
     * Can this activity be activated. This checks the capability,
     * the status and the conditions to activate an activity.
     *
     * @return bool
     */
    public function can_activate(): bool {
        return $this->can_potentially_activate() && $this->get_status_state()->can_activate();
    }

    /**
     * Getter method for @see can_activate
     * @return bool
     */
    public function get_can_activate(): bool {
        return $this->can_activate();
    }

    /**
     * Activate this activity if possible
     *
     * @return $this
     */
    public function activate(): self {
        if ($this->get_status_state()->activate()) {
            // Mark all track assignments to be expanded
            foreach ($this->tracks as $track) {
                foreach ($track->assignments as $assignment) {
                    $assignment->mark_as_expand();
                }
            }
        }

        return $this;
    }

    /**
     * Get the number of users that will be assigned to this activity upon activation.
     *
     * @return int|null Number of users, or null if the activity can't be activated yet.
     * @throws coding_exception if the activity has already been activated
     */
    public function get_users_to_assign_count(): ?int {
        if (!$this->is_draft()) {
            throw new coding_exception("Activity {$this->id} has already been activated");
        }

        if (!$this->can_activate()) {
            // Activity is in the draft state, but can't be activated for some other reason.
            return null;
        }

        // Get all the assignments for this activity.
        /** @var track_assignment $track_assignments */
        $track_assignments = track_assignment::repository()
            ->select(['id', 'user_group_type', 'user_group_id'])
            ->join([track_entity::TABLE, 'track'], 'track_id', 'id')
            ->where('track.activity_id', $this->id)
            ->get();

        // Separate the user group types in order to query the users associated with the group.
        $user_groups = [];
        foreach ($track_assignments as $assignment) {
            $user_group_entity = grouping::get_entity_class_by_user_group_type($assignment->user_group_type);
            $user_groups[$user_group_entity][] = $assignment->user_group_id;
        }

        // Build the total user count by counting the expanded user group records.
        // User group id for users is just a user id, so it can't be expanded.
        $user_ids = [];
        if (isset($user_groups[user::class])) {
            $user_ids = [$user_groups[user::class]];
            unset($user_groups[user::class]);
        }
        // Expand each user group for the total user count.
        foreach ($user_groups as $user_group_type => $user_group_ids) {
            /** @var expandable $user_group_type */
            $user_ids[] = $user_group_type::expand_multiple(
                $user_group_ids,
                $this->get_context()
            );
        }
        $user_ids = array_unique(array_merge(...$user_ids));

        return count($user_ids);
    }

    /**
     * @inheritDoc
     */
    public function get_current_state_code(string $state_type): int {
        return $this->entity->{$state_type};
    }

    /**
     * @inheritDoc
     */
    protected function update_state_code(state $state): void {
        $this->entity->{$state::get_type()} = $state::get_code();
        $this->entity->update();
    }

    /**
     * Checks whether the current user can delete this perform activity.
     *
     * @return bool
     */
    public function can_delete(): bool {
        return has_capability('mod/perform:manage_activity', $this->get_context());
    }

    /**
     * Delete the activity and the associated child models.
     * Will only delete elements that are not used by any other perform activities.
     *
     * An activity_deleted event will be triggered on successful deletions.
     *
     * @see activity_deleted
     * @param bool $force
     */
    public function delete(bool $force = false): void {
        (new activity_deletion($this))->delete($force);
    }

    /**
     * Get status state class.
     *
     * @return activity_status
     */
    public function get_status_state(): state {
        return $this->get_state(activity_status::get_type());
    }

    /**
     * Returns the settings belonging to this activity.
     *
     * @return activity_settings the settings.
     */
    public function get_settings(): activity_settings {
        // Preloading settings to save queries in case the relation got eager loaded
        $settings = $this->entity->settings()->get();

        return new activity_settings($this, $settings->map_to(activity_setting::class));
    }

    /**
     * Get manual relationships set for the activity.
     *
     * @return collection
     */
    public function get_manual_relationships(): collection {
        return $this->entity->manual_relationships
            ->map_to(activity_manual_relationship_selection::class)
            ->sort(function ($manual_selection, $manual_selection_2) {
                return $manual_selection->manual_relationship->sort_order <=> $manual_selection_2->manual_relationship->sort_order;
            });
    }

    /**
     * Update the manual relationship selections.
     *
     * @param array $selected_relationships
     * @return activity
     */
    public function update_manual_relationship_selections(array $selected_relationships): activity {
        if($this->is_active()) {
            throw new moodle_exception('error_updating_activity_manual_relationships', 'mod_perform');
        }

        builder::get_db()->transaction(function() use ($selected_relationships) {
            $saved_relationship_selections = manual_relationship_selection::repository()
                ->where('activity_id', $this->id)
                ->get()->map_to(activity_manual_relationship_selection::class);
            $core_relationships = (new relationship_provider())->filter_by_type(relationship_entity::TYPE_STANDARD)->get()->pluck('id');

            $new_manual_relationship_selections = [];
            foreach ($selected_relationships as $selected_relationship) {
                if (!in_array($selected_relationship['selector_relationship_id'], $core_relationships, true)) {
                    throw new moodle_exception('invalid_relationship', 'mod_perform');
                }
                $new_manual_relationship_selections[$selected_relationship['manual_relationship_id']] = $selected_relationship['selector_relationship_id'];
            }

            foreach ($saved_relationship_selections as $relationship_selection) {
                if (isset($new_manual_relationship_selections[$relationship_selection->manual_relationship_id])) {
                    $relationship_selection->update_selector_relationship($new_manual_relationship_selections[$relationship_selection->manual_relationship_id]);
                }
            }
        });

        return $this;
    }

    /**
     * Is the user allowed to clone this activity?
     *
     * @return bool
     */
    public function get_can_clone(): bool {
        return has_capability(backup_helper::CAPABILITY_CONTAINER, context_course::instance($this->entity->course))
            && has_capability(restore_helper::CAPABILITY_CONTAINER, context_course::instance($this->entity->course));
    }

    /**
     * Clone the activity and the associated child models.
     * @see activity_clone
     *
     * @return activity
     */
    public function clone(): activity {
        return (new activity_clone($this))->clone();
    }

    /**
     * Returns the multisection setting.
     *
     * NB: do not use activity settings to directly get hold of the value. Use
     * this method instead; it ensures the final value is consistent with other
     * activity data values.
     *
     * @return bool the multisection value: true = multisection on, false = single
     *         section.
     */
    public function get_multisection_setting(): bool {
        return (new activity_multisection_toggler($this))
            ->get_current_setting();
    }

    /**
     * Registers whether activity content can be in multiple sections.
     *
     * NB: do not use activity settings to directly change the value. Use this
     * method instead; it ensures the correct business rules execute as needed.
     *
     * @param bool $new_setting new multisection value: true = multisection on
     *        false = single section.
     *
     * @return activity this object.
     */
    public function toggle_multisection_setting(bool $new_setting): activity {
        return (new activity_multisection_toggler($this))
            ->set($new_setting);
    }

    /**
     * Get the notifications for this activity.
     *
     * @return collection|notification[]
     */
    public function get_notifications(): collection {
        return $this->entity->notifications->map_to(notification::class);
    }

    /**
     * Update activity visibility condition setting
     *
     * @param int $visibility_condition
     * @return activity_setting
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function update_visibility_condition(int $visibility_condition) {
        $activity_setting = activity_setting::load_by_name_or_create(
            $this->get_id(), activity_setting::VISIBILITY_CONDITION
        );
        return $activity_setting->update($visibility_condition);
    }

    /**
     * Get all visibility condition options for activity
     *
     * @return \core\collection
     */
    public function get_visibility_condition_options() {
        return (new visibility_manager())->get_options();
    }

    /**
     * Get all sections with respondable element count, it only map to section id and
     * sections_ordered_with_respondable_element_count.
     *
     * @return collection|section[]
     */
    public function get_sections_ordered_with_respondable_element_count(): collection {
        return $this->entity->sections_ordered_with_respondable_element_count->map_to(section::class);
    }
}
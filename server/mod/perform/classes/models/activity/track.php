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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use DateTime;
use coding_exception;
use context_system;
use core\orm\collection;
use core\orm\entity\model;
use mod_perform\constants;
use mod_perform\dates\constants as date_constants;
use mod_perform\dates\date_offset;
use totara_core\advanced_feature;
use totara_core\dates\date_time_setting;
use mod_perform\dates\resolvers\date_resolver;
use mod_perform\dates\resolvers\dynamic\dynamic_date_resolver;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\fixed_range_resolver;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\track as track_model;
use mod_perform\models\activity\trigger\repeating\factory as repeating_trigger_factory;
use mod_perform\models\activity\trigger\repeating\trigger as repeating_trigger;
use mod_perform\state\activity\active;
use mod_perform\user_groups\grouping;
use moodle_exception;

/**
 * Represents a single performance activity track.
 *
 * @property-read int $id
 * @property-read int $activity_id
 * @property-read string $description
 * @property-read int $status
 * @property-read int|null $subject_instance_generation
 * @property-read bool $schedule_is_open
 * @property-read bool $schedule_is_fixed
 * @property-read int $schedule_fixed_from
 * @property-read int $schedule_fixed_to
 * @property-read string|null $schedule_fixed_timezone
 * @property-read date_offset|null $schedule_dynamic_from
 * @property-read date_offset|null $schedule_dynamic_to
 * @property-read dynamic_source|null $schedule_dynamic_source
 * @property-read bool $schedule_use_anniversary
 * @property-read bool $due_date_is_enabled
 * @property-read bool $due_date_is_fixed
 * @property-read int $due_date_fixed
 * @property-read string|null $due_date_timezone
 * @property-read date_offset|null $due_date_offset
 * @property-read bool $repeating_is_enabled
 * @property-read repeating_trigger $repeating_trigger
 * @property-read date_offset|null $repeating_offset
 * @property-read bool $repeating_is_limited
 * @property-read int $repeating_limit
 * @property-read bool $can_assign_positions
 * @property-read bool $can_assign_organisations
 * @property-read int $created_at
 * @property-read int $updated_at
 *
 * @property-read activity $activity
 * @property-read collection|track_assignment[] $assignments
 */
class track extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'activity_id',
        'description',
        'status',
        'schedule_is_open',
        'schedule_is_fixed',
        'schedule_fixed_from',
        'schedule_fixed_timezone',
        'schedule_dynamic_from',
        'schedule_dynamic_to',
        'schedule_dynamic_source',
        'schedule_use_anniversary',
        'due_date_is_enabled',
        'due_date_is_fixed',
        'due_date_fixed',
        'due_date_fixed_timezone',
        'due_date_offset',
        'repeating_is_enabled',
        'repeating_trigger',
        'repeating_offset',
        'repeating_is_limited',
        'repeating_limit',
        'created_at',
        'updated_at',
    ];

    protected $model_accessor_whitelist = [
        'activity',
        'assignments',
        'subject_instance_generation',
        'schedule_fixed_to',
        'repeating_type',   // @deprecated since totara 16
        'schedule_fixed_from_setting',
        'schedule_fixed_to_setting',
        'due_date_fixed_setting',
        'can_assign_positions',
        'can_assign_organisations'
    ];

    /**
     * @var track_entity
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected static function get_entity_class(): string {
        return track_entity::class;
    }

    /**
     * Creates a new track instance.
     *
     * @param activity $parent parent activity.
     * @param string $description track description.
     *
     * @return track the newly created track.
     */
    public static function create(activity $parent, string $description = ''): track {
        if (!$parent->can_manage()) {
            throw new moodle_exception('nopermissions', '', '', 'create track');
        }

        $now = (new DateTime())
            ->setTime(0, 0, 0, 0)
            ->getTimestamp();

        $entity = new track_entity();
        $entity->activity_id = $parent->get_id();
        $entity->description = $description;
        $entity->status = track_status::ACTIVE;
        $entity->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;
        $entity->schedule_is_open = true;
        $entity->schedule_is_fixed = true;
        $entity->schedule_fixed_from = $now;
        $entity->schedule_fixed_to = null;
        $entity->schedule_dynamic_from = null;
        $entity->schedule_dynamic_to = null;
        $entity->schedule_dynamic_source = null;
        $entity->due_date_is_enabled = false;
        $entity->due_date_is_fixed = null;
        $entity->due_date_fixed = null;
        $entity->due_date_offset = null;
        $entity->repeating_is_enabled = false;
        $entity->repeating_trigger = null;
        $entity->repeating_type = null;
        $entity->repeating_offset = null;
        $entity->repeating_is_limited = null;
        $entity->repeating_limit = null;
        $entity->save();

        return new track($entity);
    }

    /**
     * Retrieves tracks by their parent activity.
     *
     * @param activity $parent parent activity.
     *
     * @return collection retrieved tracks.
     */
    public static function load_by_activity(activity $parent): collection {
        if (!$parent->can_manage()) {
            throw new moodle_exception('nopermissions', '', '', 'load track by activity');
        }

        return track_entity::repository()
            ->where('activity_id', $parent->get_id())
            ->get()
            ->map_to(static::class);
    }

    /**
     * Get the activity model that this track belongs to
     *
     * @return activity
     */
    public function get_activity(): activity {
        return activity::load_by_entity($this->entity->activity);
    }

    /**
     * Get all assignment models that belong to this track
     *
     * @return collection|track_assignment[]
     */
    public function get_assignments(): collection {
        // Currently, this query cannot easily be sorted by name of the user group type
        // so sorting this by ids for now to get predictable results at least
        return $this->entity->assignments()
            ->order_by('user_group_type')
            ->order_by('user_group_id')
            ->get()
            ->map_to(track_assignment::class);
    }

    /**
     * Activates the track.
     *
     * @return track the activated track.
     */
    public function activate(): track {
        $this->require_manage('change track status to active');

        $allowed_transitions = [
            track_status::PAUSED
        ];

        if (in_array($this->entity->status, $allowed_transitions)) {
            $this->entity->status = track_status::ACTIVE;
            $this->entity->save();
        }

        return $this;
    }

    /**
     * Pauses the track.
     *
     * @return track the paused track.
     */
    public function pause(): track {
        $this->require_manage('change track status to pause');

        $allowed_transitions = [
            track_status::ACTIVE
        ];

        if (in_array($this->entity->status, $allowed_transitions)) {
            $this->entity->status = track_status::PAUSED;
            $this->entity->save();
        }

        return $this;
    }

    /**
     * Returns whether this track has assignments
     *
     * @return bool
     */
    public function has_assignments(): bool {
        if ($this->entity->relation_loaded('assignments')) {
            return $this->entity->assignments->count() > 0;
        }

        return $this->entity->assignments()->exists();
    }

    /**
     * Adds a user group assignment to this track if it doesn't already exist.
     *
     * NB: no check is done to see if this incoming group id really corresponds
     *     to the given user group type. Neither is there a check to see if the
     *     group record really exists.
     *
     * @param int $assignment_type assignment type.
     * @param grouping $group user grouping.
     *
     * @return track this instance.
     */
    public function add_assignment(int $assignment_type, grouping $group): track {
        if (!in_array($assignment_type, track_assignment_type::get_allowed(), true)) {
            throw new coding_exception("unknown assignment type: '$assignment_type'");
        }

        $this->require_manage('add track assignment');

        $existing = track_assignment::load_by_track($this, $assignment_type, $group);
        if ($existing->count() === 0) {
            track_assignment::create($this, $assignment_type, $group);
        }

        return $this->refresh();
    }

    /**
     * Removes a user group assignment from this track if it exists.
     *
     * @param int $assignment_type assignment type.
     * @param grouping $group user grouping.
     *
     * @return track this instance.
     */
    public function remove_assignment(int $assignment_type, grouping $group): track {
        if (!in_array($assignment_type, track_assignment_type::get_allowed(), true)) {
            throw new coding_exception("unknown assignment type: '$assignment_type'");
        }

        $this->require_manage('remove track assignment');

        $existing = track_assignment::load_by_track($this, $assignment_type, $group);
        foreach ($existing as $assignment) {
            $assignment->remove();
        }

        return $this->refresh();
    }

    /**
     * Returns true if the current user can assign positions
     *
     * @return bool
     */
    public function get_can_assign_positions(): bool {
        $has_capabilities = has_all_capabilities(
            [
                'totara/hierarchy:viewpositionframeworks',
                'totara/hierarchy:viewposition',
            ],
            context_system::instance()
        );
        return advanced_feature::is_enabled('positions') && $has_capabilities;
    }

    /**
     * Returns true if the current user can assign organisations
     *
     * @return bool
     */
    public function get_can_assign_organisations(): bool {
        return advanced_feature::is_enabled('organisations') && has_all_capabilities(
            [
                'totara/hierarchy:vieworganisationframeworks',
                'totara/hierarchy:vieworganisation',
            ],
            context_system::instance()
        );
    }

    /**
     * Forces the model to reload its data from the repository.
     *
     * @return track this track.
     */
    public function refresh(): track {
        $this->entity->refresh();
        $this->entity->load_relation('assignments');

        return $this;
    }

    /**
     * Checks if the current user has the rights to execute the given operation.
     * Throws a moodle_exception if the user is not allowed.
     *
     * @param string $op target operation.
     */
    public function require_manage(string $op): void {
        $parent = activity::load_by_entity($this->entity->activity);
        if (!$parent->can_manage()) {
            throw new moodle_exception('nopermissions', '', '', $op);
        }
    }

    /**
     * Set the schedule to be closed with fixed dates
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_time_setting $from
     * @param date_time_setting $to
     * @return track
     * @throws moodle_exception
     */
    public function set_schedule_closed_fixed(date_time_setting $from, date_time_setting $to): self {
        if ($to < $from) {
            throw new moodle_exception('fixed_date_selector_error_range', 'mod_perform');
        }

        $properties_to_update = [
            'schedule_is_open' => false,
            'schedule_is_fixed' => true,
            'schedule_fixed_from' => $from->get_timestamp(),
            'schedule_fixed_to' => $to->get_timestamp(),
            'schedule_fixed_timezone' => $to->get_timezone(),
        ];

        $this->set_schedule_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the schedule to be open ended with fixed dates
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_time_setting $from
     * @return track
     */
    public function set_schedule_open_fixed(date_time_setting $from): self {
        $properties_to_update = [
            'schedule_is_open' => true,
            'schedule_is_fixed' => true,
            'schedule_fixed_from' => $from->get_timestamp(),
            'schedule_fixed_timezone' => $from->get_timezone(),
        ];

        $this->set_schedule_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the schedule to be closed with dynamic dates
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_offset $from
     * @param date_offset $to
     * @param dynamic_source $dynamic_source
     * @param bool $use_anniversary
     * @return track
     */
    public function set_schedule_closed_dynamic(
        date_offset $from,
        date_offset $to,
        dynamic_source $dynamic_source,
        bool $use_anniversary = false
    ): self {
        $now = time();
        if ($from->apply($now) > $to->apply($now)) {
            throw new coding_exception('"from" must not be after "to"');
        }

        if (!$dynamic_source->is_available()) {
            throw new coding_exception('Dynamic source must be available');
        }

        $properties_to_update = [
            'schedule_is_open' => false,
            'schedule_is_fixed' => false,
            'schedule_dynamic_from' => $from,
            'schedule_dynamic_to' => $to,
            'schedule_dynamic_source' => $dynamic_source,
            'schedule_use_anniversary' => $use_anniversary,
        ];

        $this->set_schedule_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the schedule to be open ended with dynamic dates
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_offset $from
     * @param dynamic_source $dynamic_source
     * @param bool $use_anniversary
     * @return track
     */
    public function set_schedule_open_dynamic(
        date_offset $from,
        dynamic_source $dynamic_source,
        bool $use_anniversary = false
    ): self {
        if (!$dynamic_source->is_available()) {
            throw new coding_exception('Dynamic source must be available');
        }

        $properties_to_update = [
            'schedule_is_open' => true,
            'schedule_is_fixed' => false,
            'schedule_dynamic_from' => $from,
            'schedule_dynamic_source' => $dynamic_source,
            'schedule_use_anniversary' => $use_anniversary,
        ];

        $this->set_schedule_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the subject instance generation method
     *
     * @param int $method
     */
    public function set_subject_instance_generation(int $method): void {
        if (!isset(self::get_subject_instance_generation_methods()[$method])) {
            throw new coding_exception('Invalid subject instance generation method');
        }

        $entity = $this->entity;

        $entity->subject_instance_generation = $method;
    }

    /**
     * Set the creation range properties.
     * This function resets scheduling properties that are not provided.
     * After calling, use track::update to save the changes to the DB
     *
     * @param array $properties containing at least schedule_is_open and schedule_is_fixed
     */
    private function set_schedule_properties(array $properties): void {
        $entity = $this->entity;
        $entity_before_changes = clone $entity;

        $entity->schedule_is_open = $properties['schedule_is_open'];
        $entity->schedule_is_fixed = $properties['schedule_is_fixed'];
        $entity->schedule_fixed_from = $properties['schedule_fixed_from'] ?? null;
        $entity->schedule_fixed_to = $properties['schedule_fixed_to'] ?? null;
        $entity->schedule_fixed_timezone = $properties['schedule_fixed_timezone'] ?? null;
        $entity->schedule_dynamic_from = $properties['schedule_dynamic_from'] ?? null;
        $entity->schedule_dynamic_to = $properties['schedule_dynamic_to'] ?? null;
        $entity->schedule_dynamic_source = $properties['schedule_dynamic_source'] ?? null;
        $entity->schedule_use_anniversary = $properties['schedule_use_anniversary'] ?? false;

        if ($this->get_activity()->get_status_state() instanceof active
            && $this->do_schedule_changes_need_sync($entity_before_changes)) {
            $entity->schedule_needs_sync = true;
        }
    }

    /**
     * Detect if schedule changes have happened that require the sync flag to be set.
     *
     * @param track_entity $entity_before_changes
     * @return bool
     */
    private function do_schedule_changes_need_sync(track_entity $entity_before_changes): bool {
        if ($this->entity->schedule_dynamic_source != $entity_before_changes->schedule_dynamic_source) {
            return true;
        }

        if ($this->entity->schedule_dynamic_from != $entity_before_changes->schedule_dynamic_from) {
            return true;
        }

        if ($this->entity->schedule_dynamic_to != $entity_before_changes->schedule_dynamic_to) {
            return true;
        }

        if ($this->entity->schedule_fixed_timezone !== $entity_before_changes->schedule_fixed_timezone) {
            return true;
        }

        // All int and bool fields.
        foreach ([
            'schedule_is_open',
            'schedule_is_fixed',
            'schedule_fixed_from',
            'schedule_fixed_to',
            'schedule_use_anniversary',
        ] as $relevant_field) {
            if ((int)$this->entity->{$relevant_field} !== (int)$entity_before_changes->{$relevant_field}) {
                return true;
            }
        }
        return false;
    }

    /**
     * Disable repeating
     *
     * Clears all repeating related fields.
     * After calling, use track::update to save the changes to the DB
     *
     * @return track
     */
    public function set_repeating_disabled(): self {
        $this->set_repeating_properties([
            'repeating_is_enabled' => false,
            'repeating_trigger' => null,
            'repeating_type' => null,
            'repeating_offset' => null,
            'repeating_is_limited' => false,
            'repeating_limit' => null,
        ]);

        return $this;
    }

    /**
     * Set repeating to enabled
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param int $type deprecated and unused since Totara 16. Use the $repeating_trigger
     *        parameter instead.
     * @param date_offset $offset
     * @param int|null $limit
     * @param ?repeating_trigger $trigger the repeating trigger. If unspecified,
     *        derives the trigger from $type.
     *
     * @return track
     */
    public function set_repeating_enabled(
        int $type,
        date_offset $offset,
        ?int $limit = null,
        ?repeating_trigger $trigger = null
    ): self {
        $trigger_factory = repeating_trigger_factory::get_instance();

        [$actual_type, $actual_trigger] = $trigger
            ? [$trigger_factory->create_repeating_type_from_trigger($trigger), $trigger]
            : [$type, $trigger_factory->create_trigger_from_repeating_type($type)];

        $properties_to_update = [
            'repeating_is_enabled' => true,
            'repeating_trigger' => $actual_trigger,
            'repeating_type' => $actual_type,
            'repeating_offset' => $offset,
            'repeating_is_limited' => !is_null($limit),
            'repeating_limit' => $limit,
        ];

        $this->set_repeating_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the repeating properties.
     * This function resets repeating properties that are not provided.
     * After calling, use track::update to save the changes to the DB
     *
     * @param array $properties containing at least repeating_is_enabled
     */
    private function set_repeating_properties(array $properties): void {
        $entity = $this->entity;

        $entity->repeating_is_enabled = $properties['repeating_is_enabled'];
        $entity->repeating_trigger = $properties['repeating_trigger'] ?? null;
        $entity->repeating_type = $properties['repeating_type'] ?? null;
        $entity->repeating_offset = $properties['repeating_offset'] ?? null;
        $entity->repeating_is_limited = $properties['repeating_is_limited'] ?? false;
        $entity->repeating_limit = $properties['repeating_limit'] ?? null;
    }

    /**
     * Disable the due date
     *
     * Clears all due date related fields.
     *
     * @return track
     */
    public function set_due_date_disabled(): self {
        $this->set_due_date_properties(['due_date_is_enabled' => false]);

        return $this;
    }

    /**
     * Update the due date to fixed (and enabled)
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_time_setting $fixed_due_date
     * @return track
     */
    public function set_due_date_fixed(date_time_setting $fixed_due_date): self {
        $properties_to_update = [
            'due_date_is_enabled' => true,
            'due_date_is_fixed' => true,
            'due_date_fixed' => $fixed_due_date->get_timestamp(),
            'due_date_fixed_timezone' => $fixed_due_date->get_timezone(),
        ];

        $this->set_due_date_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the due date to relative (and enabled)
     *
     * After calling, use track::update to save the changes to the DB
     *
     * @param date_offset $offset
     * @return track
     */
    public function set_due_date_relative(date_offset $offset): self {
        $properties_to_update = [
            'due_date_is_enabled' => true,
            'due_date_is_fixed' => false,
            'due_date_offset' => $offset,
        ];

        $this->set_due_date_properties($properties_to_update);

        return $this;
    }

    /**
     * Set the due date properties.
     * This function resets due date properties that are not provided.
     * After calling, use track::update to save the changes to the DB
     *
     * @param array $properties containing at least due_date_is_enabled
     */
    private function set_due_date_properties(array $properties): void {
        $entity = $this->entity;

        $entity->due_date_is_enabled = $properties['due_date_is_enabled'];
        $entity->due_date_is_fixed = $properties['due_date_is_fixed'] ?? null;
        $entity->due_date_fixed = $properties['due_date_fixed'] ?? null;
        $entity->due_date_fixed_timezone = $properties['due_date_fixed_timezone'] ?? null;
        $entity->due_date_offset = $properties['due_date_offset'] ?? null;
    }

    /**
     * @deprecated Since Totara 16
     */
    public static function get_repeating_types(): array {
        if (!defined('BEHAT_SITE_RUNNING')) {
            debugging(
                __METHOD__ . ' is deprecated, use models\activity\trigger\repeating subclasses instead.',
                DEBUG_DEVELOPER
            );
        }
        return [
            track_entity::SCHEDULE_REPEATING_TYPE_AFTER_CREATION => constants::SCHEDULE_REPEATING_AFTER_CREATION,
            track_entity::SCHEDULE_REPEATING_TYPE_AFTER_CREATION_WHEN_COMPLETE => constants::SCHEDULE_REPEATING_AFTER_CREATION_WHEN_COMPLETE,
            track_entity::SCHEDULE_REPEATING_TYPE_AFTER_COMPLETION => constants::SCHEDULE_REPEATING_AFTER_COMPLETION,
            track_entity::SCHEDULE_REPEATING_TYPE_UNSET => constants::SCHEDULE_REPEATING_UNSET
        ];
    }

    public static function get_subject_instance_generation_methods(): array {
        return [
            track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT,
            track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB => constants::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB,
        ];
    }

    /**
     * Maps a mapped int constant to the string representation.
     *
     * @param int|null $value
     * @param array $map
     * @param string $value_description Used in the exception
     * @return string|null
     */
    public static function mapped_value_to_string(?int $value, array $map, string $value_description): ?string {
        if ($value === null) {
            return null;
        }

        if (!array_key_exists($value, $map)) {
            throw new coding_exception(sprintf("Unknonw %s: %s", $value_description, $value));
        }

        return $map[$value];
    }

    /**
     * Maps a string representation to the mapped int constant
     *
     * @param string|null $string_value
     * @param array $map
     * @param string $exception_message
     * @return int|null
     */
    public static function mapped_value_from_string(?string $string_value, array $map, string $exception_message): ?int {
        if ($string_value === null) {
            return null;
        }

        $string_map = array_flip($map);

        if (!array_key_exists($string_value, $string_map)) {
            throw new coding_exception(sprintf($exception_message, $string_value));
        }

        return $string_map[$string_value];
    }

    /**
     * Get the string representation of the subject instance generation method.
     *
     * @return string|null
     */
    protected function get_subject_instance_generation(): ?string {
        return track_model::mapped_value_to_string(
            $this->entity->subject_instance_generation,
            track_model::get_subject_instance_generation_methods(),
            'subject instance generation method'
        );
    }

    /**
     * Get the string representation of the relative repeating type.
     *
     * @return string|null
     *
     * @deprecated Since Totara 16
     */
    protected function get_repeating_type(): ?string {
        if (!defined('BEHAT_SITE_RUNNING')) {
            debugging(
                __METHOD__ . ' is deprecated, use models\activity\trigger\repeating subclasses instead.',
                DEBUG_DEVELOPER
            );
        }

        return track_model::mapped_value_to_string(
            $this->entity->repeating_type,
            track_model::get_repeating_types(),
            'repeating relative type'
        );
    }

    /**
     * @return date_time_setting|null
     */
    public function get_schedule_fixed_from_setting(): ?date_time_setting {
        if ($this->entity->schedule_fixed_from === null) {
            return null;
        }

        return new date_time_setting($this->entity->schedule_fixed_from, $this->entity->schedule_fixed_timezone);
    }

    /**
     * @return date_time_setting|null
     */
    public function get_schedule_fixed_to_setting(): ?date_time_setting {
        if ($this->entity->schedule_fixed_to === null) {
            return null;
        }

        return new date_time_setting($this->entity->schedule_fixed_to, $this->entity->schedule_fixed_timezone);
    }

    /**
     * @return date_time_setting|null
     */
    public function get_due_date_fixed_setting(): ?date_time_setting {
        if ($this->entity->due_date_fixed === null) {
            return null;
        }

        return new date_time_setting($this->entity->due_date_fixed, $this->entity->due_date_fixed_timezone);
    }

    /**
     * Get the date resolver for this track and a given set of users.
     *
     * @param collection $user_assignments - Collection of objects similar to track_user_assignments expected
     * @return date_resolver|dynamic_date_resolver
     */
    public function get_date_resolver(collection $user_assignments): date_resolver {
        if ($this->schedule_is_fixed) {
            return new fixed_range_resolver($this->schedule_fixed_from, $this->get_schedule_fixed_to());
        }

        $dynamic_source = $this->entity->schedule_dynamic_source;

        if ($dynamic_source === null) {
            throw new coding_exception('Dynamic date resolver not set');
        }

        $resolver = $dynamic_source->get_resolver();

        if ($resolver === null) {
            throw new coding_exception('Dynamic date resolver not set');
        }

        $bulk_fetch_keys = [];
        switch ($resolver->get_resolver_base()) {
            case date_constants::DATE_RESOLVER_JOB_BASED:
                $bulk_fetch_keys = $user_assignments->pluck('job_assignment_id');
                if (!empty($bulk_fetch_keys)) {
                    $bulk_fetch_keys = array_filter($bulk_fetch_keys,
                        function ($key) {
                            return $key !== null;
                        }
                    );
                }
                break;

            case date_constants::DATE_RESOLVER_USER_BASED:
                $bulk_fetch_keys = $user_assignments->pluck('subject_user_id');
                break;

            default:
                // Fixed doesn't really need any fetch keys
                break;
        }

        return $resolver->set_parameters(
            $this->schedule_dynamic_from,
            $this->schedule_dynamic_to,
            $dynamic_source->get_option_key(),
            $bulk_fetch_keys
        );
    }

    /**
     * Checks that the properties of this model are valid
     *
     * If validation fails, an exception is thrown.
     *
     * Schedule, due date and repeating field validation is not required, because schedules can only be
     * set using the methods provided in this class. We only need to check the interdependencies between
     * these sets of properties.
     */
    public function validate(): void {
        $entity = $this->entity;

        if ($entity->due_date_is_fixed) {
            // Check that due date type is valid given schedule open/fixed.
            if ($entity->schedule_is_open || !$entity->schedule_is_fixed) {
                throw new coding_exception('Cannot set due date to fixed except when schedule is not open and fixed');
            }

            // Check that due date is after schedule end date.
            if ($entity->due_date_fixed <= $entity->schedule_fixed_to) {
                throw new coding_exception('Cannot set fixed due date earlier than the schedule end date');
            }
        }
    }

    /**
     * Saves changes to this model to the database
     *
     * Validation is performed before saving occurs. If validation fails, an exception is thrown.
     *
     * @return track
     */
    public function update(): self {
        $this->validate();

        $this->entity->update();

        return $this;
    }

    /**
     * Should one subject instance be created for each users job assignments, regardless of job assignment configuration.
     *
     * @return bool
     */
    public function is_per_job_subject_instance_generation(): bool {
        return (int) $this->entity->subject_instance_generation === track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB;
    }

    public function get_subject_instance_generation_control_is_enabled(): bool {
        return !empty(get_config(null, 'totara_job_allowmultiplejobs'));
    }

    public function get_schedule_fixed_to() :?int {
        return $this->entity->schedule_is_open ? null : $this->entity->schedule_fixed_to;
    }

}

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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\testing;

use coding_exception;
use container_perform\perform as perform_container;
use context_coursecat;
use core\collection;
use core\entity\cohort;
use core\entity\user;
use core\orm\query\builder;
use core_container\module\module;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use invalid_parameter_exception;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\manual_relationship_selection_progress;
use mod_perform\entity\activity\manual_relationship_selector;
use mod_perform\entity\activity\notification_recipient as notification_recipient_entity;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\expand_task;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\activity_type;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_identifier as element_identifier_model;
use mod_perform\models\activity\notification;
use mod_perform\models\activity\notification_recipient;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_relationship as section_relationship_model;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\models\response\section_element_response;
use mod_perform\notification\factory;
use mod_perform\state\activity\active;
use mod_perform\state\activity\activity_state;
use mod_perform\state\activity\draft;
use mod_perform\state\participant_instance\availability_not_applicable as participant_instance_availability_not_applicable;
use mod_perform\state\participant_instance\complete;
use mod_perform\state\participant_instance\open;
use mod_perform\state\participant_instance\progress_not_applicable as participant_instance_progress_not_applicable;
use mod_perform\state\participant_section\availability_not_applicable as participant_section_availability_not_applicable;
use mod_perform\state\participant_section\complete as participant_section_complete;
use mod_perform\state\participant_section\in_progress as participant_section_in_progress;
use mod_perform\state\participant_section\not_started as participant_section_not_started;
use mod_perform\state\participant_section\open as participant_section_open;
use mod_perform\state\participant_section\progress_not_applicable as participant_section_progress_not_applicable;
use mod_perform\state\subject_instance\pending;
use mod_perform\task\service\manual_participant_progress;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\util as testing_util;
use mod_perform\user_groups\grouping;
use mod_perform\util;
use performelement_aggregation\aggregation;
use performelement_aggregation\aggregation_response_calculator;
use performelement_aggregation\calculations\average;
use performelement_linked_review\testing\generator as linked_review_generator;
use performelement_numeric_rating_scale\numeric_rating_scale;
use stdClass;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as core_relationship;
use totara_core\relationship\relationship as relationship_model;
use totara_core\relationship\relationship_provider as core_relationship_provider;
use totara_job\entity\job_assignment as job_assignment_entity;
use totara_job\job_assignment;

/**
 * Perform generator
 */
final class generator extends \core\testing\component_generator {

    /**
     * Create a performance activity and a performance container to contain it
     *
     * @param array $data
     * @return activity
     */
    public function create_activity_in_container($data = []): activity {
        global $DB;

        $container_data = new stdClass();
        $container_data->fullname = $data['container_name'] ?? $data['activity_name'] ?? "test performance container";
        $container_data->category = $data['category'] ?? util::get_default_category_id();

        return $DB->transaction(function () use ($data, $container_data) {
            $container = perform_container::create($container_data);

            // Create a performance activity inside the new performance container.
            $name = $data['activity_name'] ?? "test performance activity";
            $description = $data['description'] ?? "test description";
            if (isset($data['activity_status'])) {
                $status = $this->elevate_activity_status_to_code($data['activity_status']);
            } else {
                $status = active::get_code();
            }

            $type = $data['activity_type'] ?? 'appraisal';
            $type_model = activity_type::load_by_name($type);
            if (!$type_model) {
                throw new coding_exception("Unknown activity type: '$type'");
            }

            /** @var perform_container $container */
            $activity = activity::create($container, $name, $type_model, $description, $status);

            if (isset($data['anonymous_responses']) &&
                ($data['anonymous_responses'] === true || $data['anonymous_responses'] === 'true')) {
                /** @var activity_entity $entity */
                $entity = activity_entity::repository()->find($activity->id);
                $entity->anonymous_responses = true;
                $entity->save();
            }

            if (isset($data['create_track']) && $data['create_track'] == 'true') {
                track::create($activity);
            }

            if (!array_key_exists('create_section', $data) || $data['create_section'] == 'true') {
                section::create($activity);
            }

            if (isset($data['manual_relationships'])) {
                $this->create_manual_relationships_for_activity($activity->id, $data['manual_relationships']);
            }

            if (isset($data['created_at'])) {
                activity_entity::repository()->update_record([
                    'id' => $activity->id,
                    'created_at' => strtotime($data['created_at']),
                ]);
                $activity->refresh();
            }

            return $activity;
        });
    }

    protected function elevate_activity_status_to_code($state_value) {
        $states = [draft::class, active::class];

        foreach ($states as $state) {
            /** @var activity_state $state */
            if ($state_value === $state::get_name() || $state_value === $state::get_display_name()) {
                return $state::get_code();
            }
        }

        return $state_value;
    }

    /**
     * Creates only a performance activity module
     *
     * This function is required by module generators.
     *
     * @param array $data
     * @return module
     */
    public function create_instance($data = []): module {
        $name = $data['name'] ?? "test performance activity";
        $description = $data['description'] ?? "test description";
        $status = $data['status'] ?? active::get_code();

        $type = $data['activity_type'] ?? 'appraisal';
        $type_model = activity_type::load_by_name($type);
        if (!$type_model) {
            throw new coding_exception("Unknown activity type: '$type'");
        }

        $container = perform_container::from_id($data['course']);

        /** @var perform_container $container */
        activity::create($container, $name, $type_model, $description, $status);

        $modules = $container->get_section(0)->get_all_modules();
        $module = reset($modules);

        return $module;
    }

    /**
     * Wrapper for behat
     *
     * @param array $data
     */
    public function create_activity_section(array $data): void {
        $this->create_section($this->get_activity_from_name($data['activity_name']), ['title' => $data['section_name']]);
    }

    public function create_section(activity $activity, $data = []): section {
        $title = $data['title'] ?? "test Section";
        return section::create($activity, $title);
    }

    public function find_or_create_section(activity $activity, $data = []): section {
        /** @var section_entity $section_entity */
        $section_entity = section_entity::repository()
            ->where('activity_id', $activity->id)
            ->order_by('id')
            ->first();

        if ($section_entity === null) {
            return $this->create_section($activity, $data);
        }

        $title = $data['title'] ?? "test Section";
        $section_entity->title = $title;
        $section_entity->save();

        return new section($section_entity);
    }

    /**
     * Creates activity settings.
     *
     * @param array $data
     * @return void
     */
    public function create_activity_settings(array $data): void {
        $activity = $this->get_activity_from_name($data['activity_name']);

        if (isset($data[activity_setting::CLOSE_ON_COMPLETION])) {
            activity_setting::create(
                $activity,
                activity_setting::CLOSE_ON_COMPLETION,
                $data[activity_setting::CLOSE_ON_COMPLETION] === 'yes'
            );
        }
        if (isset($data[activity_setting::CLOSE_ON_DUE_DATE])) {
            activity_setting::create(
                $activity,
                activity_setting::CLOSE_ON_DUE_DATE,
                $data[activity_setting::CLOSE_ON_DUE_DATE] === 'yes'
            );
        }
        if (isset($data[activity_setting::MULTISECTION])) {
            activity_setting::create(
                $activity,
                activity_setting::MULTISECTION,
                $data[activity_setting::MULTISECTION] === 'yes'
            );
        }
        if (isset($data[activity_setting::VISIBILITY_CONDITION])) {
            activity_setting::create(
                $activity,
                activity_setting::VISIBILITY_CONDITION,
                $data[activity_setting::VISIBILITY_CONDITION]
            );
        }
        if (isset($data[activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS])) {
            activity_setting::create(
                $activity,
                activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS,
                $data[activity_setting::OVERRIDE_GLOBAL_PARTICIPATION_SETTINGS] === 'yes'
            );
        }
        if (isset($data[activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION])) {
            activity_setting::create(
                $activity,
                activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION,
                $data[activity_setting::SYNC_PARTICIPANT_INSTANCE_CREATION] === 'yes'
            );
        }
        if (isset($data[activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE])) {
            activity_setting::create(
                $activity,
                activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE,
                $data[activity_setting::SYNC_PARTICIPANT_INSTANCE_CLOSURE] === 'yes'
            );
        }
    }

    /**
     * @param string $activity_name
     * @return activity
     */
    private function get_activity_from_name(string $activity_name): activity {
        /** @var activity_entity $activity */
        $activity = activity_entity::repository()
            ->where('name', $activity_name)
            ->one(true);
        return activity::load_by_entity($activity);
    }

    /**
     * Wrapper for behat
     *
     * @param array $data
     */
    public function create_section_element_from_name(array $data): void {
        $section = $this->get_section_from_title($data['section_name']);
        $data['plugin_name'] = $data['element_name'];
        $data['context'] = $section->get_activity()->get_context();

        if (array_key_exists('data', $data) && $data['plugin_name'] === aggregation::get_plugin_name()) {
            $data['data'] = json_decode($data['data'], true, 512, JSON_THROW_ON_ERROR);

            $data['data'][aggregation::SOURCE_SECTION_ELEMENT_IDS] = array_map(function (string $title) {
                /** @var element_entity $element */
                $element = element_entity::repository()->where('title', $title)->one();
                return $element->section_element->id;
            }, $data['data']['sourceSectionElementTitles']);

            $data['data'] = json_encode($data['data'], JSON_THROW_ON_ERROR);
        }

        $element = $this->create_element($data);

        $this->create_section_element($section, $element);
    }

    public function create_section_element(section $section, element $element, $sort_order = null): section_element {
        if (is_null($sort_order)) {
            $sort_order = section_element_entity::repository()->where('section_id', $section->id)->get()->count() + 1;
        }
        return section_element::create($section, $element, $sort_order);
    }

    public function create_participant_section(
        activity $activity,
        participant_instance_entity $participant_instance,
        $add_elements = true,
        section $section = null
    ): participant_section_entity {
        if ($section === null) {
            $section = $this->create_section($activity, ['title' => 'Part one']);
        }

        $participant_section = new participant_section_entity();
        $participant_section->section_id = $section->id;
        $participant_section->participant_instance_id = $participant_instance->id;
        if ($participant_instance->progress === participant_instance_progress_not_applicable::get_code()) {
            $participant_section->progress = participant_section_progress_not_applicable::get_code();
        } else {
            $participant_section->progress = participant_section_not_started::get_code();
        }
        if ($participant_instance->availability === participant_instance_availability_not_applicable::get_code()) {
            $participant_section->availability = participant_section_availability_not_applicable::get_code();
        } else {
            $participant_section->availability = participant_section_open::get_code();
        }
        $participant_section->save();

        if ($add_elements) {
            $element = $this->create_element(['title' => 'Question one']);
            $this->create_section_element($section, $element);

            $element2 = $this->create_element(['title' => 'Question two']);
            $this->create_section_element($section, $element2, 2);
        }

        return $participant_section;
    }

    /**
     * @param array $data
     *
     * @return element
     */
    public function create_element(array $data = []): element {
        return element::create(
            $data['context'] ?? \context_coursecat::instance(perform_container::get_default_category_id()),
            $data['plugin_name'] ?? 'short_text',
            $data['title'] ?? 'test element title',
            $data['identifier'] ?? '',
            $data['data'] ?? null,
            $data['is_required'] ?? false,
            $data['parent'] ?? null,
            $data['sort_order'] ?? null
        );
    }

    /**
     * Update existing element
     *
     * @param element $element
     * @param array $data
     */
    public function update_element(element $element, array $data = []): void {
        $element->update_details(
            $data['title'] ?? $element->title,
            $data['data'] ?? $element->data,
            $data['is_required']
        );
    }

    /**
     * Wrapper for Behat
     *
     * @param array $data required: 'section_name' (should be unique) and 'relationship'
     */
    public function create_section_relationship_from_name(array $data): void {
        $relationship_name = strtolower($data['relationship']);
        $relationships = (new core_relationship_provider())->get();
        $can_view = isset($data['can_view']) ? $data['can_view'] === 'yes' : true;
        $can_answer = isset($data['can_answer']) ? $data['can_answer'] === 'yes' : true;
        foreach ($relationships as $relationship) {
            if (strtolower($relationship->get_name()) === $relationship_name) {
                $this->create_section_relationship(
                    $this->get_section_from_title($data['section_name']),
                    ['relationship' => $relationship->idnumber],
                    $can_view,
                    $can_answer
                );
                return;
            }
        }
        throw new coding_exception("Could not find relationship '{$relationship_name}'");
    }

    /**
     * Create an element that is a child of another element (linked_review)
     * Fully compatible with behat.
     *
     * @param array $data Inputs: parent_element, after_element, element_plugin, element_title, data, title, is_required
     * @return element
     */
    public function create_child_element(array $data): element {
        if ($data['parent_element'] instanceof element) {
            $parent_element = $data['parent_element'];
        } else {
            $parent_element = element::load_by_entity(element_entity::repository()->where('title', $data['parent_element'])->one());
        }

        if (empty($data['after_element'])) {
            $after_element_id = null;
        } else if ($data['after_element'] instanceof element) {
            $after_element_id = $data['after_element']->id;
        } else {
            $after_element_id = element_entity::repository()->where('title', $data['after_element'])->one()->id;
        }

        $element_plugin = $data['element_plugin'] ?? 'short_text';
        $element_data = !empty($data['data']) ? trim($data['data']) : json_encode([]);
        $is_required = !empty($data['is_required']) ? filter_var($data['is_required'], FILTER_VALIDATE_BOOLEAN) : null;

        $child_element = $parent_element->get_child_element_manager()->create_child_element([
            'title' => $data['element_title'] ?? "$element_plugin child question",
            'data' => $element_data,
            'is_required' => $is_required,
        ], $element_plugin, $after_element_id);

        return $child_element;
    }

    /**
     * @param string $section_name
     * @return section
     */
    private function get_section_from_title(string $section_name): section {
        /** @var section_entity $section */
        $section = section_entity::repository()
            ->where('title', $section_name)
            ->one(true);
        return section::load_by_entity($section);
    }

    /**
     * Add relationship for a section.
     *
     * @param section $section
     * @param array $data containing the relationship key as the relationship idnumber.
     * @param bool $can_view
     * @param bool $can_answer
     * @return section_relationship_model
     */
    public function create_section_relationship(
        section $section,
        array $data,
        $can_view = true,
        $can_answer = true
    ): section_relationship_model {
        if ($data['relationship'] instanceof relationship || $data['relationship'] instanceof relationship_model) {
            $relationship_id = $data['relationship']->id;
        } else if (is_numeric($data['relationship'])) {
            $relationship_id = $data['relationship'];
        } else {
            $relationship_id = $this->get_core_relationship($data['relationship'])->id;
        }
        return section_relationship_model::create(
            $section->get_id(),
            $relationship_id,
            $can_view,
            $can_answer
        );
    }

    /**
     * Get the relationship tagged by the idnumber.
     *
     * @param string $idnumber
     * @return core_relationship
     */
    public function get_core_relationship(string $idnumber): core_relationship {
        return core_relationship::load_by_idnumber($idnumber);
    }

    /**
     * Get the notification recipient model instance for the specified notification.
     *
     * @param notification $notification
     * @param array $data Relationship data, e.g. ['idnumber' => relationship idnumber]
     * @param bool $active Should the recipient be active?
     * @return notification_recipient
     */
    public function create_notification_recipient(
        notification $notification,
        array $data,
        bool $active = true
    ): notification_recipient {
        $entity = notification_recipient_entity::repository()
            ->join([relationship::TABLE, 'relationship'], 'core_relationship_id', 'id')
            ->where('notification_id', $notification->id)
            ->where('relationship.idnumber', $data['idnumber'])
            ->one(true);
        $model = notification_recipient::load_by_entity($entity);
        if ($active != $model->active) {
            $model->toggle($active);
        }
        return $model;
    }

    /**
     * Creates a set of tracks for the given activity.
     *
     * @param activity $activity parent activity.
     * @param int $track_count no of tracks to generate
     *
     * @return collection|track[] $tracks the generated tracks.
     */
    public function create_activity_tracks(activity $activity, int $track_count = 1): collection {
        return collection::new(range(0, $track_count - 1))
            ->map_to(
                function (int $i) use ($activity): track {
                    return track::create($activity, "track #$i");
                }
            );
    }

    /**
     * Creates one track with one cohort assignment for the given activity.
     *
     * @param activity $activity parent activity.
     *
     * @return track $track the generated track.
     */
    public function create_single_activity_track_and_assignment(activity $activity): track {
        $track = track::create($activity, "test track");
        return $this->create_track_assignments($track, 1, 0, 0, 0);
    }

    /**
     * Creates a set of track assignments for the given track.
     *
     * @param track $track parent track.
     * @param int $cohort_count no of cohorts to generate for assignments.
     * @param int $org_count no of organizations to generate for assignments.
     * @param int $pos_count no of positions to generate for assignments.
     * @param int $user_count no of users to generate for assignments.
     *
     * @return track the updated track.
     */
    public function create_track_assignments(
        track $track,
        int $cohort_count = 1,
        int $org_count = 1,
        int $pos_count = 1,
        int $user_count = 1
    ): track {
        $pos = [];
        $hierarchies = \totara_hierarchy\testing\generator::instance();
        if ($pos_count > 0) {
            $data = ['frameworkid' => $hierarchies->create_pos_frame([])->id];

            foreach (range(0, $pos_count - 1) as $unused) {
                $pos[] = $hierarchies->create_pos($data)->id;
            }
        }

        $orgs = [];
        if ($org_count > 0) {
            $data = ['frameworkid' => $hierarchies->create_org_frame([])->id];

            foreach (range(0, $org_count - 1) as $unused) {
                $orgs[] = $hierarchies->create_org($data)->id;
            }
        }

        $cohorts = [];
        if ($cohort_count > 0) {
            foreach (range(0, $cohort_count - 1) as $unused) {
                $cohorts[] = $this->datagenerator->create_cohort()->id;
            }
        }

        $users = [];
        if ($user_count > 0) {
            foreach (range(0, $user_count - 1) as $unused) {
                $users[] = $this->datagenerator->create_user()->id;
            }
        }

        return $this->create_track_assignments_with_existing_groups($track, $cohorts, $orgs, $pos, $users);
    }

    /**
     * Creates a set of track assignments (of admin type) for the given track.
     *
     * @param track $track parent track.
     * @param int[] $cohorts cohort ids to assign.
     * @param int[] $orgs organization ids to assign.
     * @param int[] $pos position ids to assign.
     * @param int[] $users user ids to assign.
     *
     * @return track the updated track.
     */
    public function create_track_assignments_with_existing_groups(
        track $track,
        array $cohorts = [],
        array $orgs = [],
        array $pos = [],
        array $users = []
    ): track {
        $assignments = [];
        foreach ($cohorts as $id) {
            $assignments[] = grouping::cohort($id);
        }
        foreach ($orgs as $id) {
            $assignments[] = grouping::org($id);
        }
        foreach ($pos as $id) {
            $assignments[] = grouping::pos($id);
        }
        foreach ($users as $id) {
            $assignments[] = grouping::user($id);
        }

        $assign_type = track_assignment_type::ADMIN;
        return collection::new($assignments)
            ->reduce(
                function (track $interim, grouping $group) use ($assign_type): track {
                    return $interim->add_assignment($assign_type, $group);
                },
                $track
            );
    }

    /**
     * Create full activities including assignments, subject, participant instances and notifications
     *
     * @param activity_generator_configuration|null $configuration
     * @return collection|activity[]
     */
    public function create_full_activities(activity_generator_configuration $configuration = null) {
        // For the activity generation we need to make sure the admin user is set
        if (!is_siteadmin()) {
            throw new coding_exception('perform generator requires active user to be an administrator');
        }

        // Create a default configuration if it wasn't provided
        if ($configuration === null) {
            $configuration = activity_generator_configuration::new();
        }

        $tenant_id = $configuration->get_tenant_id();
        $category_id = $configuration->get_category_id() ?? util::get_default_category_id();

        $manual_idnumbers = relationship::repository()
            ->where('type', relationship::TYPE_MANUAL)
            ->get()
            ->pluck('idnumber');
        $manual_relationships = [];

        $activity_name_generator = new activity_name_generator();

        $activities = [];
        for ($i = 0; $i < $configuration->get_number_of_activities(); $i++) {
            [$name, $type] = $activity_name_generator->generate();
            if ($configuration->should_use_multilang_filter()) {
                $name = testing_util::generate_multilang_string($name);
            }

            $data = [
                'activity_name' => $name,
                'activity_type' => $type,
                'create_section' => false,
                'activity_status' => $configuration->get_activity_status(),
                'anonymous_responses' => $configuration->get_anonymous_responses_setting(),
            ];

            if (isset($category_id)) {
                $data['category'] = $category_id;
            }

            $activity = $this->create_activity_in_container($data);

            // Create all notifications.
            $notifications = array_map(function ($class_key) use ($activity) {
                return notification::load_by_activity_and_class_key($activity, $class_key)->activate();
            }, factory::create_loader()->get_class_keys());

            if ($configuration->get_number_of_sections_per_activity() > 1) {
                $activity->get_settings()->update([activity_setting::MULTISECTION => true]);
            }

            $relationships = $configuration->get_relationships_per_section();

            // Add notification recipient for each relationship.
            foreach ($relationships as $relationship_idnumber) {
                foreach ($notifications as $notification) {
                    // Not all notifications support all recipient roles.
                    // Ideally, we should filter out which relationships to be added based on each notification
                    // rather than the dreadful try-catch-swallow pattern.
                    try {
                        $this->create_notification_recipient($notification, ['idnumber' => $relationship_idnumber], true);
                    } catch (invalid_parameter_exception $ex) {
                        // Good bye, exception.
                    }
                }
            }

            $view_only_relationships = $configuration->get_view_only_relationships();

            for ($k = 0; $k < $configuration->get_number_of_sections_per_activity(); $k++) {
                $section_title = 'activity '.$activity->id . ' section ' . $k;
                if ($configuration->should_use_multilang_filter()) {
                    $section_title = testing_util::generate_multilang_string($section_title);
                }
                $section = $this->create_section($activity, ['title' => $section_title]);

                $relationships_for_section = $configuration->get_relationships_for_section($k + 1);
                if ($relationships_for_section !== null) {
                    $relationships = $relationships_for_section;
                }
                foreach ($relationships as $relationship_idnumber) {
                    if (in_array($relationship_idnumber, $manual_idnumbers, true)) {
                        $manual_relationships[] = $relationship_idnumber;
                    }

                    $can_view = true;
                    $can_answer = true;
                    if (in_array($relationship_idnumber, $view_only_relationships)) {
                        $can_answer = false;
                    }

                    $this->create_section_relationship($section, ['relationship' => $relationship_idnumber], $can_view, $can_answer);
                }
                for ($j = 1; $j <= $configuration->get_number_of_elements_per_section(); $j++) {
                    $element_title = "Section {$section->id} element{$j}";
                    if ($configuration->should_use_multilang_filter()) {
                        $element_title = testing_util::generate_multilang_string($element_title);
                    }
                    $element = $this->create_element(['title' => $element_title]);

                    section_element::create($section, $element, $j);
                }
            }
            $this->create_activity_tracks($activity, $configuration->get_number_of_tracks_per_activity());
            $activities[] = $activity;
        }

        $context = \context_system::instance();
        if (!empty($category_id)) {
            $context = context_coursecat::instance($category_id);
        }
        $user_data = [];
        if ($tenant_id) {
            $user_data['tenantid'] = $tenant_id;
        }

        $current_language = current_language();
        $language_per_relationship = $configuration->get_language_per_relationship();

        foreach ($activities as $activity) {
            $cohorts = [];
            for ($i = 0; $i < $configuration->get_cohort_assignments_per_activity(); $i++) {
                $cohort = $this->datagenerator->create_cohort(['contextid' => $context->id]);
                $cohorts[] = $cohort->id;
                for ($k = 0; $k < $configuration->get_number_of_users_per_user_group_type(); $k++) {
                    $user_data['lang'] = $language_per_relationship[constants::RELATIONSHIP_SUBJECT] ?? $current_language;
                    $user = $this->datagenerator->create_user($user_data);
                    cohort_add_member($cohort->id, $user->id);

                    if ($configuration->should_create_appraiser_for_each_subject_user()) {
                        $user_data['lang'] = $language_per_relationship[constants::RELATIONSHIP_APPRAISER] ?? $current_language;
                        $appraiser = $this->datagenerator->create_user($user_data);
                        job_assignment::create([
                            'userid' => $user->id,
                            'idnumber' => 'app/' . $cohort->id . '/' . $user->id,
                            'appraiserid' => $appraiser->id,
                        ]);
                    }
                    if ($configuration->should_create_manager_for_each_subject_user()) {
                        $user_data['lang'] = $language_per_relationship[constants::RELATIONSHIP_MANAGER] ?? $current_language;
                        $manager = $this->datagenerator->create_user($user_data);
                        job_assignment::create([
                            'userid' => $user->id,
                            'idnumber' => 'man/' . $cohort->id . '/' . $user->id,
                            'managerjaid' => job_assignment::create_default($manager->id)->id,
                        ]);
                    }
                }
            }

            foreach (track::load_by_activity($activity) as $track) {
                $this->create_track_assignments_with_existing_groups($track, $cohorts);
            }
        }

        // Expand assignments to user assignments
        if ($configuration->should_generate_user_assignments()) {
            expand_task::create()->expand_all();
        }
        if ($configuration->should_generate_subject_instances()) {
            // Create subject instances for all user assignments
            $this->generate_subject_instances();
        }

        if (!empty($manual_relationships)) {
            // Make sure the progress records are there
            (new manual_participant_progress())->generate();

            if ($configuration->should_create_manual_participants()) {
                foreach ($activities as $activity) {
                    $this->create_manual_users_for_activity($activity, $manual_relationships);
                }
            }
        }

        if ($configuration->get_aggregation() !== null) {
            [$aggregation_display_section, $aggregation_source_sections, $aggregation_source_answer_map] = $configuration->get_aggregation();

            foreach ($activities as $activity) {
                $this->create_aggregation_in_activity(
                    $activity->get_id(),
                    $aggregation_display_section,
                    $aggregation_source_sections,
                    $aggregation_source_answer_map
                );
            }
        }

        return collection::new($activities);
    }

    /**
     * Create manual relationships for given activity
     *
     * @param activity $activity
     * @param array $manual_relationships array of relationship idnumbers
     * @param int $number_of_users
     */
    public function create_manual_users_for_activity(activity $activity, array $manual_relationships, int $number_of_users = 1) {
        /** @var subject_instance[] $subject_instances */
        $subject_instances = subject_instance_entity::repository()
            ->filter_by_activity_id($activity->id)
            ->get()
            ->map_to(subject_instance::class);
        foreach ($subject_instances as $subject_instance) {
            foreach ($manual_relationships as $manual_relationship) {
                if ($manual_relationship === constants::RELATIONSHIP_EXTERNAL) {
                    $relationship = $this->get_core_relationship($manual_relationship);
                    $manual_users = [];
                    for ($i = 0; $i < $number_of_users; $i ++) {
                        $fullname = $this->generate_fullname();
                        $manual_users[] = [
                            'name' => $fullname,
                            'email' => $this->generate_email($fullname),
                        ];
                    }
                    $data = [
                        [
                            'manual_relationship_id' => $relationship->id,
                            'users' => $manual_users
                        ]
                    ];
                    $subject_instance->set_participant_users($subject_instance->subject_user_id, $data);
                }
            }
        }
    }

    /**
     * Generate subject instance.
     *
     * NOTE: this used to have illegal dependency on PHPUnit message redirection,
     *       use message sink in tests if necessary.
     *
     * @return void
     */
    public function generate_subject_instances(): void {
        // Create subject instances for all user assignments
        (new subject_instance_creation())->generate_instances();
    }

    private function generate_fullname(): string {
        // Totara: Make sure that the random full user names are unique.
        $firstname = 'first' . substr(md5(mt_rand()), -8);
        $lastname = 'last' . substr(md5(mt_rand()), -8);
        return $firstname . ' ' . $lastname;
    }

    private function generate_email(string $fullname): string {
        return strtolower(str_replace(' ', '.', $fullname)) . '@example.com';
    }

    /**
     * Set the manual relationships for an activity.
     *
     * @param activity|int $activity Activity model or ID
     * @param array[] $relationships Array of ['selector' => $selector_relationship_id, 'manual' => $manual_relationship_id]
     * @return array
     */
    public function create_manual_relationships_for_activity($activity, array $relationships): array {
        $activity_id = is_numeric($activity) ? $activity : $activity->id;

        return builder::get_db()->transaction(static function () use ($activity_id, $relationships) {
            // By default all the relationships are set to subject.
            // But we want to set our own values here so we delete them.
            manual_relationship_selection::repository()
                ->where('activity_id', $activity_id)
                ->delete();

            $selections = [];
            foreach ($relationships as $relationship) {
                $selection_entity = new manual_relationship_selection();
                $selection_entity->activity_id = $activity_id;

                if (is_numeric($relationship['selector'])) {
                    $selection_entity->selector_relationship_id = $relationship['selector'];
                } else {
                    $selection_entity->selector_relationship_id = $relationship['selector']->id;
                }

                if (is_numeric($relationship['manual'])) {
                    $selection_entity->manual_relationship_id = $relationship['manual'];
                } else {
                    $selection_entity->manual_relationship_id = $relationship['manual']->id;
                }

                $selection_entity->save();
                $selections[] = $selection_entity;
            }

            return $selections;
        });
    }

    /**
     * Creates a user activity (subject_instance) with one participant and optionally the subject participating too.
     *
     * The top level perform activity is created if a name (activity_name) or id (activity_id) is not supplied,
     * otherwise the perform row will be looked id or name if supplied.
     *
     * The subject can either be identified by 'subject_user_id' (user.id) or 'subject_username' (user.username).
     *
     * The (other) participant can either be identified by 'other_participant_id' (user.id) or
     * 'other_participant_username' (user.username) or left out.
     *
     * @param array $data
     * @return subject_instance_entity
     */
    public function create_subject_instance(array $data): subject_instance_entity {
        $activity_id = $data['activity_id'] ?? null;

        if ($activity_id) {
            $activity = activity::load_by_id($activity_id);
        } else {
            $name = $data['activity_name'] ?? null;
            $type = $data['activity_type'] ?? 'appraisal';
            $status = $this->elevate_activity_status_to_code($data['activity_status'] ?? 'Active');

            $anonymous_responses = $data['anonymous_responses'] ?? 'false';
            $anonymous_responses = $anonymous_responses === 'true' || $anonymous_responses === true;

            $activity = $this->find_or_make_perform_activity($name, $type, $status, $anonymous_responses);
        }

        $subject_id = $data['subject_user_id'] ?? null;

        if ($subject_id) {
            $subject = user::repository()->find($subject_id);
        } else {
            /** @var user $subject */
            $subject = user::repository()
                ->where('username', $data['subject_username'])
                ->order_by('id')
                ->first();
        }

        $other_participant_id = $data['other_participant_id'] ?? null;
        $other_participant_username = $data['other_participant_username'] ?? null;
        $other_participant = null;

        if ($other_participant_id) {
            $other_participant = user::repository()->find($other_participant_id);
        } else if ($other_participant_username) {
            /** @var user $other_participant */
            $other_participant = user::repository()
                ->where('username', $other_participant_username)
                ->order_by('id')
                ->first();
        }

        $third_participant_username = $data['third_participant_username'] ?? null;
        $third_participant = null;

        if ($other_participant_username) {
            /** @var user $other_participant */
            $third_participant = user::repository()
                ->where('username', $third_participant_username)
                ->order_by('id')
                ->first();
        }

        $relationships_can_view = $data['relationships_can_view'] ?? 'subject, manager, appraiser';
        $relationships_can_view = explode(', ', $relationships_can_view);
        $subject_can_view = in_array('subject', $relationships_can_view, true);
        $manager_can_view = in_array('manager', $relationships_can_view, true);
        $appraiser_can_view = in_array('appraiser', $relationships_can_view, true);

        $relationships_can_answer = $data['relationships_can_answer'] ?? '';
        $relationships_can_answer = empty($relationships_can_answer) ? [] : explode(', ', $relationships_can_answer);
        $subject_can_answer = empty($relationships_can_answer) || in_array('subject', $relationships_can_answer, true);
        $manager_can_answer = empty($relationships_can_answer) || in_array('manager', $relationships_can_answer, true);
        $appraiser_can_answer = empty($relationships_can_answer) || in_array('appraiser', $relationships_can_answer, true);

        if (!empty($data['track'])) {
            $track = $data['track'];
        } else {
            $track = "track for {$activity->name}";
        }

        $track = track::create($activity, $track);

        $user_assignment = new track_user_assignment();
        $user_assignment->track_id = $track->id;
        $user_assignment->subject_user_id = $subject->id;
        $user_assignment->deleted = false;
        $user_assignment->save();

        // Simulate repeating
        $num_instances = $data['number_repeated_instances'] ?? 1;
        $subject_instances = [];
        $now = time();
        for ($i = 1; $i <= $num_instances; $i++) {
            // If repeating, we need the create dates to be different
            $subject_instance = new subject_instance_entity();
            $subject_instance->track_user_assignment_id = $user_assignment->id;
            $subject_instance->subject_user_id = $user_assignment->subject_user_id; // Purposeful denormalization
            $subject_instance->created_at = $now + $i;
            $subject_instance->status = $data['status'] ?? active::get_code();
            $subject_instance->due_date = $data['due_date'] ?? null;
            $subject_instance->save();

            $subject_is_participating = $data['subject_is_participating'] ?? false;
            // String conversion for behat, defaulting to false.
            if (is_string($subject_is_participating) && $subject_is_participating !== 'true') {
                $subject_is_participating = false;
            }

            $is_active = (int) $subject_instance->status === active::get_code();

            $subjects_participant_instance = null;
            if ($subject_is_participating && $is_active) {
                $subjects_participant_instance = new participant_instance_entity();
                $subjects_participant_instance->core_relationship_id = core_relationship::load_by_idnumber('subject')->id;
                $subjects_participant_instance->participant_id = $subject->id; // Answering on activity about them self
                $subjects_participant_instance->participant_source = participant_source::INTERNAL;
                $subjects_participant_instance->subject_instance_id = $subject_instance->id;
                $this->set_participant_instance_progress_and_availability($subjects_participant_instance,
                    $subject_can_answer, $subject_can_view
                );
                $subjects_participant_instance->save();
            }

            $other_participant_instance = null;
            if ($other_participant && $is_active) {
                $other_participant_instance = new participant_instance_entity();
                $other_participant_instance->core_relationship_id = core_relationship::load_by_idnumber('manager')->id;
                $other_participant_instance->participant_id = $other_participant->id;
                $other_participant_instance->participant_source = participant_source::INTERNAL;
                $other_participant_instance->subject_instance_id = $subject_instance->id;
                $this->set_participant_instance_progress_and_availability($other_participant_instance,
                    $manager_can_answer, $manager_can_view
                );
                $other_participant_instance->save();
            }

            $third_participant_instance = null;
            if ($third_participant && $is_active) {
                $third_participant_instance = new participant_instance_entity();
                $third_participant_instance->core_relationship_id = core_relationship::load_by_idnumber('appraiser')->id;
                $third_participant_instance->participant_id = $third_participant->id;
                $third_participant_instance->participant_source = participant_source::INTERNAL;
                $third_participant_instance->subject_instance_id = $subject_instance->id;
                $this->set_participant_instance_progress_and_availability($third_participant_instance,
                    $appraiser_can_answer, $appraiser_can_view
                );
                $third_participant_instance->save();
            }
            $subject_instances[] = $subject_instance;
        }

        $include_questions = $data['include_questions'] ?? true;

        // String conversion for behat, defaulting to true.
        if ($include_questions === 'false') {
            $include_questions = false;
        }

        if ($include_questions) {
            $section1 = $this->include_elements_in_subject_instance($data, $activity);

            $participant_instances = [$subjects_participant_instance, $other_participant_instance, $third_participant_instance];
            $participant_sections = [];
            foreach ($participant_instances as $participant_instance) {
                if ($participant_instance === null) {
                    continue;
                }

                $participant_sections[] = $this->create_participant_section($activity, $participant_instance, false, $section1);
            }

            if ($subject_is_participating) {
                $subject_relationship = $this->create_section_relationship(
                    $section1,
                    ['relationship' => constants::RELATIONSHIP_SUBJECT],
                    $subject_can_view,
                    $subject_can_answer
                );
                $subjects_participant_instance->core_relationship_id = $subject_relationship->core_relationship_id;
                $subjects_participant_instance->save();
            }

            if ($other_participant) {
                $manager_relationship = $this->create_section_relationship(
                    $section1,
                    ['relationship' => constants::RELATIONSHIP_MANAGER],
                    $manager_can_view,
                    $manager_can_answer
                );
                $other_participant_instance->core_relationship_id = $manager_relationship->core_relationship_id;
                $other_participant_instance->save();
            }

            if ($third_participant) {
                $appraiser_relationship = $this->create_section_relationship(
                    $section1,
                    ['relationship' => constants::RELATIONSHIP_APPRAISER],
                    $appraiser_can_view,
                    $appraiser_can_answer
                );
                $third_participant_instance->core_relationship_id = $appraiser_relationship->core_relationship_id;
                $third_participant_instance->save();
            }
            $update_participant_sections_status = $data['update_participant_sections_status'] ?? false;
            if ($update_participant_sections_status) {
                foreach ($participant_sections as $participant_section) {
                    $status = ($update_participant_sections_status == 'draft') ? participant_section_in_progress::get_code() : participant_section_complete::get_code();

                    $participant_section->progress = $status;
                    $participant_section->save();
                }
            }
        }
        // Returning the last subject instance when repeating
        return end($subject_instances);
    }

    /**
     * Create a subject instance that have pending participant selections.
     *
     * @param activity|int $activity Activity model or ID
     * @param object|user|int $subject_user Subject user entity, record or ID
     * @param core_relationship[] $manual_relationships Manual relationships for the section.
     *                                                  (in order to create participant instances)
     * @param collection|manual_relationship_selection[] $selections Array/collection of selections to override.
     *                                                               Defaults to all the selections specified for the activity.
     *
     * @return subject_instance_entity
     */
    public function create_subject_instance_with_pending_selections(
        $activity,
        $subject_user,
        array $manual_relationships,
        array $selections = null
    ): subject_instance_entity {
        if (!$activity instanceof activity) {
            $activity = activity::load_by_id($activity);
        }
        if (!$subject_user instanceof user) {
            $subject_user = new user($subject_user);
        }
        if ($selections === null) {
            $selections = manual_relationship_selection::repository()
                ->where('activity_id', $activity->id)
                ->get();
        }

        $element = $this->create_element(['title' => 'An important question!', 'is_required' => true]);
        foreach ($manual_relationships as $i => $relationship) {
            $section = $this->create_section($activity, ['title' => "Section {$i}"]);
            $this->create_section_element($section, $element);
            $this->create_section_relationship($section, ['relationship' => $relationship->idnumber]);
        }

        $track = track::create($activity);

        $user_assignment = new track_user_assignment();
        $user_assignment->track_id = $track->id;
        $user_assignment->subject_user_id = $subject_user->id;
        $user_assignment->deleted = false;
        $user_assignment->save();

        $subject_instance = new subject_instance_entity();
        $subject_instance->track_user_assignment_id = $user_assignment->id;
        $subject_instance->subject_user_id = $user_assignment->subject_user_id; // Purposeful denormalization
        $subject_instance->status = pending::get_code();
        $subject_instance->save();

        $subject_instance->refresh();

        foreach ($selections as $selection) {
            $progress_entity = new manual_relationship_selection_progress();
            $progress_entity->subject_instance_id = $subject_instance->id;
            $progress_entity->manual_relation_selection_id = $selection->id;
            $progress_entity->status = manual_relationship_selection_progress::STATUS_PENDING;
            $progress_entity->save();

            $relationship = core_relationship::load_by_entity($selection->selector_relationship);
            $users = $relationship->get_users(
                ['user_id' => $subject_user->id],
                \context_user::instance($subject_user->id)
            );
            foreach ($users as $user_dto) {
                $selector = new manual_relationship_selector();
                $selector->user_id = $user_dto->get_user_id();
                $selector->manual_relation_select_progress_id = $progress_entity->id;
                $selector->save();
            }
        }

        return $subject_instance;
    }

    public function create_section_with_combined_manager_appraiser_for_behat(array $data): void {
        $subject_user = user::repository()->where('username', $data['subject_username'])->one();
        $manager_appraiser_user = user::repository()->where('username', $data['manager_appraiser_username'])->one();

        $this->create_section_with_combined_manager_appraiser($subject_user, $manager_appraiser_user, $data['activity_name'], $data['track'] ?? null);
    }

    private function find_or_make_perform_activity($name, $type, $status = null, $anonymous_responses = false): activity {
        $data = [
            'activity_type' => $type,
            'anonymous_responses' => $anonymous_responses,
        ];
        if (isset($status)) {
            $data['activity_status'] = $status;
        }

        if (!$name) {
            return $this->create_activity_in_container($data);
        }

        /** @var activity_entity $activity_entity */
        $activity_entity = activity_entity::repository()->where('name', $name)->order_by('id')->first();

        if ($activity_entity === null) {
            if (!$anonymous_responses) {
                return $this->create_activity_in_container(
                    [
                        'activity_name' => $name,
                        'activity_type' => $type,
                        'create_section' => false,
                    ]
                );
            }

            $activity = $this->create_activity_in_container(
                [
                    'activity_name' => $name,
                    'activity_type' => $type,
                    'create_section' => false,
                    'activity_status' => 'DRAFT',
                ]
            );
            $activity->set_anonymous_setting(true)->update();

            if ($status !== draft::get_code()) {
                $activity->activate();
            }
            return $activity;
        }

        if ($anonymous_responses) {
            $activity_entity->anonymous_responses = true;
            $activity_entity->save();
        }

        return activity::load_by_entity($activity_entity);
    }

    /**
     * Create a single participant instance for a user.
     *
     * @param user|object $participant_user
     * @param int $subject_instance_id
     * @param int|string $core_relationship core_relationship id or idnumber
     * @return participant_instance_entity
     */
    public function create_participant_instance(
        $participant_user,
        int $subject_instance_id,
        $core_relationship
    ): participant_instance_entity {
        if (!is_numeric($core_relationship)) {
            $core_relationship = core_relationship::load_by_idnumber($core_relationship)->id;
        }

        $participant_instance = new participant_instance_entity();
        $participant_instance->core_relationship_id = $core_relationship;
        $participant_instance->participant_source = participant_source::INTERNAL;
        $participant_instance->participant_id = $participant_user->id;
        $participant_instance->subject_instance_id = $subject_instance_id;
        $participant_instance->progress = participant_section_not_started::get_code();
        $participant_instance->availability = open::get_code();
        return $participant_instance->save();
    }

    /**
     * Create a participant instance and section for a user.
     *
     * @param activity $activity
     * @param stdClass|user $participant_user
     * @param int $subject_instance_id
     * @param section $section
     * @param int|string $core_relationship_id
     * @return participant_section_entity
     */
    public function create_participant_instance_and_section(
        activity $activity,
        $participant_user,
        int $subject_instance_id,
        section $section,
        $core_relationship_id
    ): participant_section_entity {
        if (is_numeric($core_relationship_id)) {
            $core_relationship_id = (int) $core_relationship_id;
        }

        $participant_instance = $this->create_participant_instance(
            $participant_user, $subject_instance_id, $core_relationship_id
        );

        return $this->create_participant_section($activity, $participant_instance, false, $section);
    }

    /**
     * Create a cohort and add the specified users to it.
     *
     * @param array $user_ids
     * @param array|object $record
     * @return cohort
     */
    public function create_cohort_with_users(array $user_ids, $record = []): cohort {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohort = $this->datagenerator->create_cohort($record);

        foreach ($user_ids as $user_id) {
            cohort_add_member($cohort->id, $user_id);
        }

        return new cohort($cohort);
    }

    /**
     * Create an organisation and add the specified users to it.
     *
     * @param array $user_ids
     * @param array|object $record
     * @return organisation
     */
    public function create_organisation_with_users(array $user_ids, $record = []): organisation {
        $generator = \totara_hierarchy\testing\generator::instance();

        $record = (array) $record;
        if (!isset($record['frameworkid'])) {
            $record['frameworkid'] = $generator->create_org_frame([])->id;
        }

        $organisation = $generator->create_org($record);

        $ja_idnumber = $this->get_last_job_assignment_idnumber();
        foreach ($user_ids as $user_id) {
            job_assignment::create(['userid' => $user_id, 'organisationid' => $organisation->id, 'idnumber' => ++$ja_idnumber]);
        }

        return new organisation($organisation);
    }

    /**
     * Create a position and add the specified users to it.
     *
     * @param array $user_ids
     * @param array|object $record
     * @return position
     */
    public function create_position_with_users(array $user_ids, $record = []): position {
        $generator = \totara_hierarchy\testing\generator::instance();

        $record = (array) $record;
        if (!isset($record['frameworkid'])) {
            $record['frameworkid'] = $generator->create_pos_frame([])->id;
        }

        $position = $generator->create_pos($record);

        $ja_idnumber = $this->get_last_job_assignment_idnumber();
        foreach ($user_ids as $user_id) {
            job_assignment::create(['userid' => $user_id, 'positionid' => $position->id, 'idnumber' => ++$ja_idnumber]);
        }

        return new position($position);
    }

    private function get_last_job_assignment_idnumber(): int {
        $last_record = job_assignment_entity::repository()
            ->order_by('id', 'desc')
            ->select('id')
            ->first();
        if ($last_record) {
            return $last_record->id;
        }
        return 0;
    }

    /**
     * Creates a subject instance/participant instances with a one section that has a combined manager-appraiser.
     * Combined manager-appraiser means a manager and appraiser linked to the same single end user.
     *
     * @param stdClass|user $subject_user
     * @param stdClass|user $manager_appraiser_user
     * @param null $activity_name
     * @return participant_section_entity[] [$subject_section, $manager_section, $appraiser_section]
     * @throws coding_exception
     */
    public function create_section_with_combined_manager_appraiser(
        $subject_user,
        $manager_appraiser_user,
        $activity_name = null,
        $track = null
    ): array {
        $subject_instance = $this->create_subject_instance([
            'activity_name' => $activity_name,
            'subject_is_participating' => false, // The subject actually is participating, but we will create the instance below.
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => null,
            'include_questions' => false,
            'track' => $track,
        ]);

        $activity = new activity($subject_instance->activity());

        $section = $this->find_or_create_section($activity, ['title' => 'Part one']);

        $manager_section_relationship = $this->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_MANAGER]);
        $appraiser_section_relationship = $this->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_APPRAISER]);
        $subject_section_relationship = $this->create_section_relationship($section, ['relationship' => constants::RELATIONSHIP_SUBJECT]);

        $element = $this->create_element(['title' => 'Question one']);
        $this->create_section_element($section, $element);

        $manager_section = $this->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $manager_section_relationship->core_relationship_id
        );

        $appraiser_section = $this->create_participant_instance_and_section(
            $activity,
            $manager_appraiser_user,
            $subject_instance->id,
            $section,
            $appraiser_section_relationship->core_relationship_id
        );

        $subject_section = $this->create_participant_instance_and_section(
            $activity,
            $subject_user,
            $subject_instance->id,
            $section,
            $subject_section_relationship->core_relationship_id
        );

        return [$subject_section, $manager_section, $appraiser_section];
    }

    /**
     * Wrapper for behat
     *
     * @param array $data
     */
    public function create_activity_track(array $data): void {
        $activity = $this->get_activity_from_name($data['activity_name']);
        $track = track::create($activity, $data['track_description']);

        if (isset($data['due_date_offset'])) {
            $due_date_params = explode(',', $data['due_date_offset']);

            if (count($due_date_params) > 0) {
                $data = [
                    'count' => trim($due_date_params[0]),
                    'unit' => isset($due_date_params[1])
                        ? trim($due_date_params[1])
                        : date_offset::UNIT_DAY,
                    'direction' => isset($due_date_params[2])
                        ? trim($due_date_params[2])
                        : date_offset::DIRECTION_AFTER,
                ];
                $due_date_offset = date_offset::create_from_json($data);
                $track->set_due_date_relative($due_date_offset);
                $track->update();
            }
        }

        if (!empty($data['subject_instance_generation'])) {
            $generation_methods = track::get_subject_instance_generation_methods();
            $value = array_search($data['subject_instance_generation'], $generation_methods);

            if ($value === false) {
                throw new coding_exception('unknown subject instance generation value');
            }
            $track->set_subject_instance_generation($value);
            $track->update();
        }
    }

    /**
     * Wrapper for behat
     *
     * @param array $data
     */
    public function create_track_assignment(array $data): void {
        global $DB;
        $type = $data['assignment_type'];
        /** @var track_entity $track */
        $track = track_entity::repository()
            ->where('description', $data['track_description'])
            ->one(true);

        $cohort_ids = [];
        $org_ids = [];
        $pos_ids = [];
        switch ($type) {
            case 'cohort':
                $cohort_ids[] = $DB->get_field('cohort', 'id', ['name' => $data['assignment_name']], MUST_EXIST);
                break;
            case 'organisation':
                $org_ids[] = $DB->get_field('org', 'id', ['fullname' => $data['assignment_name']], MUST_EXIST);
                break;
            case 'position':
                $pos_ids[] = $DB->get_field('pos', 'id', ['fullname' => $data['assignment_name']], MUST_EXIST);
                break;
            default:
                throw new coding_exception("creating track assignment not yet implemented for {$type}");
        }
        $this->create_track_assignments_with_existing_groups(track::load_by_entity($track), $cohort_ids, $org_ids, $pos_ids);
    }

    /**
     * Given a subject instance generated by $this->create_subject_instance(), generate responses to the questions by
     * the participants. Currently response data is empty - it only creates the record.
     *
     * By default this will create one response per participant and element in the subject instance.
     *
     * @param subject_instance_entity $subject_instance
     * @param int|null $max_responses Maximum number of response records to create. Will exit after saving this many.
     */
    public function create_responses(subject_instance_entity $subject_instance, int $max_responses = null): void {
        $activity = $subject_instance->activity();
        $count = 1;
        /** @var section_entity $section */
        foreach ($activity->sections as $section) {
            $section_elements = $section->section_elements;
            /** @var section_element_entity $section_element */
            foreach ($section_elements as $section_element) {
                $element_type = $section_element->element->plugin_name;
                $element_plugin = \mod_perform\models\activity\element_plugin::load_by_plugin($element_type);
                if (!$element_plugin->get_is_respondable()) {
                    // Don't create responses for non-respondable elements.
                    continue;
                }

                $participant_instances = $subject_instance->participant_instances;
                $subject_as_participant = null;
                $normal_participants = [];

                /** @var participant_instance_entity $participant_instance */
                foreach ($participant_instances as $participant_instance) {
                    $element_response_entity = new element_response();
                    $element_response_entity->participant_instance_id = $participant_instance->id;
                    $element_response_entity->section_element_id = $section_element->id;
                    $element_response_entity->response_data = $element_plugin->get_example_response_data();
                    $element_response_entity->save();

                    if ($participant_instance->participant_user->id === $subject_instance->subject_user_id) {
                        $subject_as_participant = $participant_instance;
                    } else {
                        $normal_participants[] = $participant_instance;
                    }

                    if (!is_null($max_responses) && $count >= $max_responses) {
                        return;
                    }
                    $count++;
                }

                if ($element_plugin->get_plugin_name() == 'linked_review') {
                    linked_review_generator::instance()->create_review_element_responses(
                        $element_plugin, $section_element, $subject_as_participant, $normal_participants
                    );
                }
            }
        }
    }

    /**
     * Create element identifier
     *
     * @param string $identifier
     *
     * @return element_identifier_model
     */
    public function create_element_identifier(string $identifier): element_identifier_model {
        return element_identifier_model::create($identifier);
    }

    /**
     * @param array $data
     * @param activity $activity
     * @return section
     */
    private function include_elements_in_subject_instance(array $data, activity $activity): section {
        $required_question = $data['include_required_questions'] ?? false;

        $include_reporting_ids = $data['include_reporting_ids'] ?? false;
        $reporting_id1 = null;
        $reporting_id2 = null;

        if ($include_reporting_ids) {
            $reporting_id1 = $activity->name . '-id-1';
            $reporting_id2 = $activity->name . '-id-2';
        }

        // String conversion for behat, defaulting to false.
        if (is_string($required_question) && $required_question !== 'true') {
            $required_question = false;
        }

        $section1 = $this->find_or_create_section($activity, ['title' => 'Part one']);

        $existing_section_element_count = section_element_entity::repository()
            ->as('se')
            ->join([element_entity::TABLE, 'e'], 'se.element_id', 'e.id')
            ->where('e.title', 'Question one')
            ->where('e.is_required', (bool) $required_question)
            ->where('se.section_id', $section1->id)
            ->count();

        // Section elements already exists for this activity.
        if ($existing_section_element_count !== 0) {
            return $section1;
        }

        $element = $this->create_element([
            'context' => $activity->get_context(),
            'title' => 'Question one',
            'is_required' => (bool) $required_question,
            'identifier' => $reporting_id1,
        ]);
        $this->create_section_element($section1, $element);

        $element2 = $this->create_element([
            'context' => $activity->get_context(),
            'title' => 'Question two',
            'is_required' => (bool) $required_question,
            'identifier' => $reporting_id2
        ]);
        $this->create_section_element($section1, $element2, 2);

        if ($data['include_static_content'] ?? false) {
            $element3 = $this->create_element([
                'context' => $activity->get_context(),
                'title' => 'Static content title',
                'plugin_name' => 'static_content',
                'data' => '{"wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"This content is static\"}]}]}","format":"HTML","docFormat":"FORMAT_JSON_EDITOR","element_id":1}'
            ]);

            $this->create_section_element($section1, $element3, 3);
        }

        if ($data['include_review_element'] ?? false) {
            linked_review_generator::instance()->create_linked_review_element_in_section(
                $activity,
                $section1,
                $data['review_element_content_type'] ?? null,
                $data['review_element_subelement_plugin'] ?? 'short_text'
            );
        }

        return $section1;
    }

    private function set_participant_instance_progress_and_availability(participant_instance_entity $participant_instance,
        bool $can_answer, bool $can_view
    ) {
        // Taken from mod_perform\task\service\participant_section_creation
        if ($can_answer) {
            $participant_instance->progress = participant_section_not_started::get_code();
            $participant_instance->availability = open::get_code();
        } else if ($can_view) {
            $participant_instance->progress = participant_instance_progress_not_applicable::get_code();
            $participant_instance->availability = participant_instance_availability_not_applicable::get_code();
        } else {
            throw new \coding_exception(
                'Tried to create participant section for relationship which cannot view or answer'
            );
        }
    }

    /**
     * Creates external participant with email and fullname for a subject username.
     * Used in behat generator.
     *
     * @param array $data
     * @return array
     */
    public function create_external_participant_instances(array $data): array {
        /** @var subject_instance_entity $subject_instance_entity */
        $subject_instance_entity = subject_instance_entity::repository()
            ->join([user::TABLE, 'u'], 'subject_user_id', 'id')
            ->where('u.username', $data['subject'])
            ->one(true);
        unset($data['subject']);

        return $this->generate_external_participant_instances($subject_instance_entity->id, $data);
    }

    /**
     * Generates an external user, with corresponding participant instance and sections
     *
     * @param int $subject_instance_id
     * @param array $data E.g: ['fullname' => 'XYZ', 'email' => 'xyz@abc.com']
     * @return array [participant_instance, ]
     */
    public function generate_external_participant_instances(int $subject_instance_id, array $data): array {
        return builder::get_db()->transaction(function () use ($subject_instance_id, $data) {
            $external_relationship_id = core_relationship::load_by_idnumber(
                constants::RELATIONSHIP_EXTERNAL
            )->id;

            $subject_instance_model = subject_instance::load_by_id($subject_instance_id);

            $section_ids = section_entity::repository()
                ->select('id')
                ->where('activity_id', $subject_instance_model->get_activity()->id)
                ->join([section_relationship::TABLE, 'rel'], 'id', 'section_id')
                ->where('rel.core_relationship_id', $external_relationship_id)
                ->get()
                ->pluck('id');
            if (empty($section_ids)) {
                throw new coding_exception('There are no sections that the external respondent can participate in');
            }

            $external_user = new external_participant();
            $external_user->name = $data['fullname'] ?? random_string();
            $external_user->email = $data['email'] ?? random_string().'@example.com';
            $external_user->token = hash('sha256', microtime());
            $external_user->save();

            $participant_instance = new participant_instance_entity();
            $participant_instance->core_relationship_id = $external_relationship_id;
            $participant_instance->participant_source = participant_source::EXTERNAL;
            $participant_instance->participant_id = $external_user->id;
            $participant_instance->subject_instance_id = $subject_instance_model->id;
            $participant_instance->progress = participant_section_not_started::get_code();
            $participant_instance->availability = open::get_code();
            $participant_instance->save();

            $participant_sections = [];
            foreach ($section_ids as $section_id) {
                $participant_section = new participant_section_entity();
                $participant_section->participant_instance_id = $participant_instance->id;
                $participant_section->section_id = $section_id;
                $participant_section->progress = participant_section_not_started::get_code();
                $participant_section->availability = participant_section_open::get_code();
                $participant_sections[] = $participant_section->save();
            }

            // Ensures the external user token hash is unique
            usleep(1);

            return [$participant_instance, $external_user, $participant_sections];
        });
    }

    /**
     * Adds all things necessary to set up scenarios with aggregation sources, and calculated responses.
     * Target sections and relationships must exist.
     *
     * @param int $activity_id The activity to add
     * @param int $aggregation_display_section The section number where the aggregation element will be displayed (1 indexed not 0)
     * @param array $aggregation_source_sections The sections numbers that aggregation sources will be added to (1 indexed not 0)
     * @param array $aggregation_source_answer_map A map of relationship idnumber to an array of source responses
     * @param bool $complete_sections Force all sections to be completed and run aggregation calculations
     * @return activity_entity
     * @throws coding_exception
     */
    public function create_aggregation_in_activity(
        int $activity_id,
        int $aggregation_display_section,
        array $aggregation_source_sections,
        array $aggregation_source_answer_map,
        bool $complete_sections = true
    ): activity_entity {
        $activity_entity = new activity_entity($activity_id);

        /** @var section_entity $display_section */
        $display_section = $activity_entity->sections_ordered->all()[$aggregation_display_section - 1];

        $number_of_source_questions = count(reset($aggregation_source_answer_map));
        $source_section_element_ids = [];

        for ($source_section_element_index = 0; $source_section_element_index < $number_of_source_questions; $source_section_element_index++) {
            $source_element = element::create(
                context_coursecat::instance(perform_container::get_default_category_id()),
                numeric_rating_scale::get_plugin_name(),
                "Aggregation source " . ($source_section_element_index + 1),
                '',
                json_encode([
                    'highValue' => 1000,
                    'lowValue' => 0,
                    'defaultValue' => 50,
                ], JSON_THROW_ON_ERROR),
                false
            );

            $source_section_number = $aggregation_source_sections[$source_section_element_index];
            $source_section = new section($activity_entity->sections_ordered->all()[$source_section_number - 1]);

            $source_section_element = $this->create_section_element($source_section, $source_element);

            $source_section_element_ids[] = $source_section_element->get_id();
        }

        $aggregation_element = $this->create_element([
            'plugin_name' => 'aggregation',
            'data' => json_encode([
                aggregation::SOURCE_SECTION_ELEMENT_IDS => $source_section_element_ids,
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->create_section_element(new section($display_section), $aggregation_element);

        foreach ($aggregation_source_answer_map as $source_relationship_name => $source_responses_data) {
            /** @var subject_instance_entity[] $subject_instances */
            $subject_instances = subject_instance_entity::repository()
                ->as('si')
                ->join([track_user_assignment::TABLE, 'tua'], 'si.track_user_assignment_id', 'tua.id')
                ->join([track_entity::TABLE, 't'], 'tua.track_id', 't.id')
                ->where('t.activity_id', $activity_id)->get();

            foreach ($subject_instances as $subject_instance) {
                $participant_instance = $subject_instance->participant_instances->find(
                    function (participant_instance_entity $participant_instance) use ($source_relationship_name) {
                        return $participant_instance->core_relationship->idnumber === $source_relationship_name;
                    }
                );

                foreach ($source_section_element_ids as $source_section_element_index => $source_section_element_id) {
                    $response_data = $source_responses_data[$source_section_element_index];

                    // Nulls are skipped, including completing the section (aggregating).
                    if ($response_data !== null) {
                        $source_response = new element_response();
                        $source_response->response_data = "\"{$response_data}\"";
                        $source_response->section_element_id = $source_section_element_id;
                        $source_response->participant_instance_id = $participant_instance->id;
                        $source_response->save();
                    }
                }
            }
        }

        if (!$complete_sections) {
            return $activity_entity;
        }

        /** @var participant_instance_entity $participant_sections */
        $participant_sections = participant_section_entity::repository()->get();
        foreach ($participant_sections as $participant_section_entity) {
            // By pass the completion checks.
            $participant_section_entity->progress = complete::get_code();
            $participant_section_entity->save();

            aggregation_response_calculator::calculate_responses_effected_by($participant_section_entity);
        }

        return $activity_entity;
    }

    /**
     * Set a participant_instance to complete by completing all its sections.
     *
     * @param participant_instance_entity $participant_instance
     * @throws coding_exception
     */
    public function complete_participant_instance(participant_instance_entity $participant_instance): void {
        $participant_sections = $participant_instance->participant_sections->all();
        foreach ($participant_sections as $participant_section) {
            $participant_section_model = participant_section_model::load_by_entity($participant_section);
            $this->mark_answers_complete($participant_section_model);
            $participant_section_model->switch_state(participant_section_complete::class);
        }
    }

    private function mark_answers_complete(participant_section_model $participant_section): void {
        $section_elements = $participant_section->get_section()->get_section_elements();

        $responses = new collection();
        for ($i = 0; $i < $section_elements->count(); $i ++) {
            $responses->append($this->create_valid_element_response());
        }

        $participant_section->set_section_element_responses($responses);
    }

    private function create_valid_element_response(): section_element_response {
        return new class extends section_element_response {
            public $was_saved = false;

            public function __construct() {
            }

            public function save(): section_element_response {
                $this->was_saved = true;
                return $this;
            }

            public function validate_response($is_draft_validation = false): bool {
                $this->validation_errors = new collection();
                return true;
            }
        };
    }

}

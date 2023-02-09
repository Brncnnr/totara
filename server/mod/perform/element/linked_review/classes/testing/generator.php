<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

namespace performelement_linked_review\testing;

use coding_exception;
use context_system;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\testing\component_generator;
use core\testing\generator as core_generator;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\company_goal_assignment as company_goal_assignment_enttity;
use hierarchy_goal\entity\personal_goal;
use hierarchy_goal\performelement_linked_review\company_goal_assignment;
use hierarchy_goal\performelement_linked_review\personal_goal_assignment;
use mod_perform\constants;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_section;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\section;
use mod_perform\models\activity\subject_instance;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\entity\linked_review_content as linked_review_content_entity;
use performelement_linked_review\entity\linked_review_content_response;
use performelement_linked_review\linked_review;
use performelement_linked_review\models\linked_review_content;
use stored_file;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\expand_task;
use totara_competency\performelement_linked_review\competency_assignment;
use totara_competency\testing\generator as competency_generator;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale;
use totara_core\relationship\relationship;

/**
 * Linked review generator
 */
final class generator extends component_generator {

    /**
     * Create a linked review element.
     *
     * @param array $data
     * @param activity|null $activity
     * @return element
     */
    public function create_linked_review_element(array $data, activity $activity = null): element {
        $context = $activity ? $activity->get_context() : context_system::instance();
        return element::create($context, 'linked_review', 'title', '', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Creates a linked review element with one sub element in the given activity and section.
     *
     * @param activity $activity parent activity.
     * @param section $section section in which to create the linked review element.
     * @param string $content_type content type.
     */
    public function create_linked_review_element_in_section(
        activity $activity,
        section $section,
        ?string $content_type = null,
        string $element_plugin = 'short_text'
    ): void {
        $content_type = $content_type ?? competency_assignment::get_identifier();

        $linked_review_data = [
            'content_type' => $content_type,
            'content_type_settings' => [],
            'selection_relationships' => [
                relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT)->id
            ],
        ];

        $element = $this->create_linked_review_element($linked_review_data, $activity);

        $perform_generator = perform_generator::instance();
        $perform_generator->create_section_element($section, $element);
        $perform_generator->create_child_element([
            'parent_element' => $element,
            'element_plugin' => $element_plugin
        ]);
    }

    /**
     * Behat wrapper to use existing sections/activities if name matches are found.
     *
     * @param array $data Input: activity_name, activity_status, content_type, selection_relationships, section_title,
     * @return array Output: [activity, section, element, section element]
     */
    public function create_activity_with_section_and_review_element_for_behat(array $data = []): array {
        return $this->create_activity_with_section_and_review_element($data, true);
    }

    /**
     * Creates a performance activity with a section, a linked_review element in that section, and a corresponding section element.
     *
     * @param array $data Input: activity_name, activity_status, content_type, selection_relationships, section_title,
     * @return array Output: [activity, section, element, section element]
     */
    public function create_activity_with_section_and_review_element(array $data = [], $use_existing_instances = false): array {
        $data = array_merge([
            'content_type' => 'totara_competency',
            'selection_relationships' => constants::RELATIONSHIP_SUBJECT,
            'activity_name' => 'Test activity',
            'activity_status' => 'Active',
            'section_title' => 'Test section',
            'element_title' => 'linked review element',
            'content_type_settings' => '{}',
        ], $data);

        $activity_entity = activity_entity::repository()->where('name', $data['activity_name'])->one();
        if (!$use_existing_instances || $activity_entity === null) {
            $activity = perform_generator::instance()->create_activity_in_container([
                'activity_name' => $data['activity_name'],
                'activity_status' => $data['activity_status'],
                'create_section' => false,
            ]);
        } else {
            $activity = new activity($activity_entity);
        }

        $section_entity = section_entity::repository()->where('title', $data['section_title'])->one();
        if (!$use_existing_instances || $section_entity === null) {
            $section = perform_generator::instance()->create_section($activity, ['title' => $data['section_title']]);
        } else {
            $section = new section($section_entity);
        }

        $relationship_ids = array_map(function ($relationship_idnumber) {
            return (string) relationship::load_by_idnumber($relationship_idnumber)->id;
        }, explode(',', $data['selection_relationships']));

        $content_settings = is_string($data['content_type_settings'])
            ? json_decode($data['content_type_settings'], true, 512, JSON_THROW_ON_ERROR)
            : $data['content_type_settings'];

        if (isset($content_settings['rating_relationship'])) {
            if (is_string($content_settings['rating_relationship'])) {
                $relationship_id = relationship::load_by_idnumber($content_settings['rating_relationship'])->id;
            } else {
                $relationship_id = $content_settings['rating_relationship'];
            }
            $content_settings['rating_relationship'] = $relationship_id;
            $data['content_type_settings'] = json_encode($content_settings, JSON_THROW_ON_ERROR);
        }

        if (isset($content_settings['status_change_relationship'])) {
            if (is_string($content_settings['status_change_relationship'])) {
                $relationship_id = relationship::load_by_idnumber($content_settings['status_change_relationship'])->id;
            } else {
                $relationship_id = $content_settings['status_change_relationship'];
            }
            $content_settings['status_change_relationship'] = $relationship_id;
            $data['content_type_settings'] = json_encode($content_settings, JSON_THROW_ON_ERROR);
        }

        $element_data = json_encode([
            'content_type' => $data['content_type'],
            'content_type_settings' => json_decode($data['content_type_settings'], true, 512, JSON_THROW_ON_ERROR),
            'selection_relationships' => $relationship_ids,
        ], JSON_THROW_ON_ERROR);

        $element = element::create(
            $activity->get_context(),
            'linked_review',
            $data['element_title'],
            '',
            $element_data
        );
        $section_element = perform_generator::instance()->create_section_element($section, $element);
        return [$activity, $section, $element, $section_element];
    }

    /**
     * Create a linked content record (simulates a user picking a content item to use an activity)
     *
     * @param array $data Inputs: subject_user, selector_user, element, content_name
     * @return linked_review_content[]
     */
    public function create_content_selection(array $data): array {
        global $DB;
        if (is_string($data['subject_user'])) {
            $subject_user = $DB->get_record('user', ['username' => $data['subject_user']]);
        } else {
            $subject_user = $data['subject_user'];
        }
        if (is_string($data['selector_user'])) {
            $selector_user = $DB->get_record('user', ['username' => $data['selector_user']]);
        } else {
            $selector_user = $data['selector_user'];
        }

        if ($data['element'] instanceof element) {
            $element = $data['element'];
        } else {
            $element_entity = element_entity::repository()->where('title', $data['element'])->one();
            if ($element_entity === null) {
                throw new coding_exception("Element with title '{$data['element']}' doesn't exist.");
            }
            $element = element::load_by_entity($element_entity);
        }
        $element_plugin = $element->get_element_plugin();
        if (!$element_plugin instanceof linked_review) {
            throw new coding_exception('The specified element was not a linked review element');
        }
        $content_type = $element_plugin->get_content_type($element)::get_identifier();

        $content_ids = $this->find_content_ids($content_type, $data, $subject_user);
        sort($content_ids);

        $section_element_id = section_element::repository()->where('element_id', $element->id)->one()->id;
        $subject_instance_id = subject_instance_entity::repository()
            ->as('si')
            ->select_raw('DISTINCT si.id')
            ->join([participant_instance::TABLE, 'pi'], 'si.id', 'subject_instance_id')
            ->join([participant_section::TABLE, 'ps'], 'pi.id', 'participant_instance_id')
            ->join([section_element::TABLE, 'se'], 'ps.section_id', 'section_id')
            ->where('si.subject_user_id', $subject_user->id)
            ->where('se.element_id', $element->id)
            ->one()
            ->id;

        $linked_review_contents = [];
        foreach ($content_ids as $content_id) {
            $entity = new linked_review_content_entity();
            $entity->content_id = $content_id;
            $entity->section_element_id = $section_element_id;
            $entity->subject_instance_id = $subject_instance_id;
            $entity->selector_id = $selector_user->id;
            $entity->content_type = $content_type;
            $entity->save();

            $linked_review_contents []= linked_review_content::load_by_entity($entity);
        }

        return $linked_review_contents;
    }

    /**
     * Create a participant instance & section, with an optional user, subject instance and section relationship.
     *
     * @param array $data Input: section, activity, user, subject_instance, relationship, create_section_relationship
     * @return array Output: [user, subject instance, participant instance, participant section]
     */
    public function create_participant_in_section(array $data): array {
        global $DB;
        if ($data['section'] instanceof section) {
            $section = $data['section'];
        } else {
            $section = section::load_by_entity(section_entity::repository()->where('title', $data['section'])->one());
        }

        if (!isset($data['activity'])) {
            $activity = $section->get_activity();
        } else if ($data['activity'] instanceof activity) {
            $activity = $data['activity'];
        } else {
            $activity = activity::load_by_entity(activity_entity::repository()->where('name', $data['activity'])->one());
        }

        if (!isset($data['user'])) {
            $user = core_generator::instance()->create_user();
        } else if (is_string($data['user'])) {
            $user = $DB->get_record('user', ['username' => $data['user']]);
        } else {
            $user = $data['user'];
        }

        $subject_instance = null;
        if (isset($data['subject_instance']) && is_object($data['subject_instance'])) {
            $subject_instance = $data['subject_instance'];
        } else if (isset($data['subject_user']) && !empty($data['subject_user'])) {
            $subject_user_id = $DB->get_field('user', 'id', ['username' => $data['subject_user']]);
            if ($subject_user_id === null) {
                throw new coding_exception("Couldn't locate a subject user with the username " . $data['subject_user']);
            }
            $subject_instance = subject_instance_entity::repository()
                ->filter_by_activity_id($activity->id)
                ->where('subject_user_id', $subject_user_id)
                ->one();
        }
        if ($subject_instance === null) {
            $subject_instance = perform_generator::instance()->create_subject_instance([
                'activity_id' => $activity->id,
                'subject_user_id' => $user->id,
                'include_questions' => false,
            ]);
        }
        if (!$subject_instance instanceof subject_instance) {
            $subject_instance = subject_instance::load_by_entity($subject_instance);
        }


        if (!isset($data['relationship'])) {
            $relationship = relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        } else if ($data['relationship'] instanceof relationship) {
            $relationship = $data['relationship'];
        } else {
            $relationship = relationship::load_by_idnumber($data['relationship']);
        }

        // The section relationship might not exist yet so create it
        $create_section_relationship = $data['create_section_relationship'] ?? true;
        if ($create_section_relationship && !section_relationship::repository()
            ->where('section_id', $section->id)
            ->where('core_relationship_id', $relationship->id)
            ->exists()) {
            $can_answer = isset($data['can_answer']) && $data['can_answer']
                ? filter_var($data['can_answer'], FILTER_VALIDATE_BOOLEAN)
                : true;
            $can_view = isset($data['can_view']) && $data['can_view']
                ? filter_var($data['can_view'], FILTER_VALIDATE_BOOLEAN)
                : true;
            perform_generator::instance()->create_section_relationship(
                $section,
                ['relationship' => $relationship],
                $can_view,
                $can_answer
            );
        }

        $participant_section = perform_generator::instance()->create_participant_instance_and_section(
            $activity,
            $user,
            $subject_instance->id,
            $section,
            $relationship->id
        );
        return [$user, $subject_instance, $participant_section->participant_instance, $participant_section];
    }

    /**
     * Create competency & assignment records - useful for simulating content.
     *
     * @param array $data Input: user, competency OR competency_name, reason [for assignment], manual_rating
     * @return object|assignment Competency assignment DB record
     */
    public function create_competency_assignment(array $data): object {
        global $DB;

        if (is_object($data['user'])) {
            $user_id = $data['user']->id;
        } else if (is_numeric($data['user'])) {
            $user_id = $data['user'];
        } else {
            $user_id = $DB->get_field('user', 'id', ['username' => $data['user']]);
        }

        $competency = null;
        if (isset($data['competency'])) {
            $competency = competency::repository();
            if (is_numeric($data['competency'])) {
                $competency->where('id', $data['competency']);
            } else {
                $competency->where('fullname', $data['competency']);
            }
            $competency = $competency->one();
        }

        if ($competency === null) {
            $framework = competency_framework::repository()->order_by('id')->first();
            if ($framework === null) {
                $scale = scale::repository()->order_by('id')->first();
                $framework = competency_generator::instance()->create_framework($scale);
            }
            $competency_name = $data['competency_name'] ?? null;
            $competency = competency::repository()->where('fullname', $competency_name)->one();
            if ($competency === null) {
                $competency = competency_generator::instance()->create_competency(
                    $competency_name,
                    $framework,
                    $competency_name ? ['idnumber' => $data['competency_name']] : null
                );
            }
        }

        $generator = competency_generator::instance()->assignment_generator();
        $user_group_type = $data['reason'] ?? user_groups::USER;
        $user_group_id = null;
        switch ($user_group_type) {
            case user_groups::COHORT:
                $user_group_id = $generator->create_cohort_and_add_members($user_id)->id;
                break;
            case user_groups::POSITION:
                $user_group_id = $generator->create_position_and_add_members($user_id)->id;
                break;
            case user_groups::ORGANISATION:
                $user_group_id = $generator->create_organisation_and_add_members($user_id)->id;
                break;
            default:
                $user_group_id = $user_id;
                break;
        }

        $assignment = $generator->create_assignment([
            'user_group_type' => $user_group_type,
            'user_group_id' => $user_group_id,
            'competency_id' => $competency->id,
        ]);
        $assignment = new assignment($assignment);
        (new expand_task(builder::get_db()))->expand_single($assignment->id);

        if (isset($data['manual_rating']) && !empty($data['manual_rating'])) {
            $scale_value = $competency->scale->values->filter('name', $data['manual_rating'])->first();
            if ($scale_value === null) {
                throw new coding_exception(
                    'Scale value ' . $data['manual_rating'] . ' does not exist for competency ' . $competency->id
                );
            }
            competency_generator::instance()->create_manual($competency, ['self']);
            competency_generator::instance()->create_manual_rating($competency, $user_id, $user_id, 'self', $scale_value);
            (new aggregation_task(new aggregation_users_table(), false))->execute();
        }

        return $assignment;
    }

    /**
     * Creates linked review element responses.
     *
     * @param linked_review $element_plugin linked review plugin.
     * @param section_element $section_element section element where linked review
     *        element resides.
     * @param participant_instance $subject_as_participant the subject whose
     *        linked review content is to be assessed.
     * @param participant_instance[] $normal_participant_instances the other
     *        participants in the linked review question.
     */
    public function create_review_element_responses(
        linked_review $element_plugin,
        section_element $section_element,
        participant_instance $subject_as_participant,
        array $normal_participant_instances
    ): void {
        $element = $section_element->element;
        $content_type = $element_plugin->get_content_type(element::load_by_entity($element));

        $content_id = null;
        if ($content_type === competency_assignment::class) {
            $user = $subject_as_participant->participant_user->id;
            $content_id = $this
                ->create_competency_assignment(['user' => $user])
                ->id;
        } else {
            throw new coding_exception("cannot generate data for '$content_type'");
        }

        $linked_review = new linked_review_content_entity();
        $linked_review->section_element_id = $section_element->id;
        $linked_review->subject_instance_id = $subject_as_participant->subject_instance_id;
        $linked_review->selector_id = $subject_as_participant->participant_user->id;
        $linked_review->content_type = competency_assignment::get_identifier();
        $linked_review->content_id = $content_id;

        $linked_review_id = $linked_review->save()->id;

        foreach ($element->children as $child_element) {
            $sub_element_type = $child_element->plugin_name;
            $sub_element_plugin = element_plugin::load_by_plugin($sub_element_type);
            if (!$sub_element_plugin->get_is_respondable()) {
                // Don't create responses for non-respondable elements.
                continue;
            }

            $response = $sub_element_plugin->get_example_response_data();

            $subject_child_response = new linked_review_content_response();
            $subject_child_response->linked_review_content_id = $linked_review_id;
            $subject_child_response->child_element_id = $child_element->id;
            $subject_child_response->participant_instance_id = $subject_as_participant->id;
            $subject_child_response->response_data = $response;
            $subject_child_response->save();

            $participant_ids = [$subject_as_participant->id];
            foreach ($normal_participant_instances as $participant_instance) {
                $participant_response = new linked_review_content_response();
                $participant_response->linked_review_content_id = $linked_review_id;
                $participant_response->child_element_id = $child_element->id;
                $participant_response->participant_instance_id = $participant_instance->id;
                $participant_response->response_data = $response;
                $participant_response->save();

                $participant_ids[] = $participant_instance->id;
            }
        }

        if ($sub_element_type === 'long_text') {
            // For linked review long texts, any 'uploaded' files are stored
            // against the relevant element_response records, not the actual
            // linked_review_content_response records. That's why the files
            // are created after all the linked review responses have been created.

            $context_id = $element->context_id;

            element_response::repository()
                ->where('section_element_id', $section_element->id)
                ->where('participant_instance_id', $participant_ids)
                ->get()
                ->map(
                    function (element_response $response) use ($context_id): void {
                        $this->create_file_item(
                            $response->id,
                            $context_id,
                            $response->participant_instance_id . ' response'
                        );
                    }
                );
        }
    }

    /**
     * "Uploads" a file for the given response id.
     *
     * @param int $element_response_id element_response associated with this file.
     * @param int $context_id context associated with this file.
     * @param string $content file content.
     *
     * @return stored_file the created file
     */
    public function create_file_item(
        int $element_response_id,
        int $context_id,
        string $content = 'testing123'
    ): stored_file {
        $file_record = [
            'component' => \performelement_long_text\long_text::get_response_files_component_name(),
            'filearea' => \performelement_long_text\long_text::get_response_files_filearea_name(),
            'filepath' => '/',
            'filename' => "test_review_{$element_response_id}.txt",
            'contextid' => $context_id,
            'itemid' => $element_response_id
        ];

        return get_file_storage()->create_file_from_string($file_record, $content);
    }

    /**
     * @param string $content_type
     * @param array $data
     * @param $subject_user
     * @return int[]|string[]
     * @throws coding_exception
     */
    public function find_content_ids(string $content_type, array $data, $subject_user): array
    {
        $content_names = [];
        foreach ($data as $key => $value) {
            // Pull all content_type.* fields.
            if (strpos($key, 'content_name') === 0) {
                $content_names[] = $value;
            }
        }

        switch ($content_type) {
            case competency_assignment::get_identifier():
                return competency_assignment_user::repository()
                    ->select('assignment_id')
                    ->join([competency::TABLE, 'comp'], 'competency_id', 'id')
                    ->where_in('comp.fullname', $content_names)
                    ->where('user_id', $subject_user->id)
                    ->when(!empty($data['assignment_reason']), function (repository $repo) use ($data) {
                        $repo
                            ->join([assignment::TABLE, 'ass'], 'assignment_id', 'id')
                            ->where('ass.user_group_type', $data['assignment_reason']);
                    })
                    ->get()
                    ->pluck('assignment_id');
                break;
            case company_goal_assignment::get_identifier():
                return company_goal_assignment_enttity::repository()
                    ->as('a')
                    ->join([company_goal::TABLE, 'g'], 'g.id', 'a.goalid')
                    ->where_in('g.fullname', $content_names)
                    ->where('a.userid', $subject_user->id)
                    ->get()
                    ->pluck('id');
            case personal_goal_assignment::get_identifier():
                return personal_goal::repository()
                    ->where_in('name', $content_names)
                    ->where('userid', $subject_user->id)
                    ->get()
                    ->pluck('id');
            default:
                throw new coding_exception($content_type . ' is not implemented in generator::create_content_selection');
        }
    }
}

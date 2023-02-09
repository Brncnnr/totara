<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\backup;

defined('MOODLE_INTERNAL') || die();

use mod_perform\models\activity\section_element_reference;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\element_cloning;
use restore_path_element;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use mod_perform\util;

global $CFG;
require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');

class restore_activity_structure_step extends \restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element(
            'perform',
            '/activity/perform'
        );

        $paths[] = new restore_path_element(
            'external_participant',
            '/activity/perform/external_participants/external_participant'
        );

        $paths[] = new restore_path_element(
            'track',
            '/activity/perform/tracks/track'
        );

        $paths[] = new restore_path_element(
            'section',
            '/activity/perform/sections/section'
        );

        $paths[] = new restore_path_element(
            'element',
            '/activity/perform/elements/element'
        );

        $paths[] = new restore_path_element(
            'sub_element',
            '/activity/perform/elements/element/sub_elements/sub_element'
        );

        $paths[] = new restore_path_element(
            'element_identifier',
            '/activity/perform/element_identifiers/element_identifier'
        );

        $paths[] = new restore_path_element(
            'setting',
            '/activity/perform/settings/setting'
        );

        $paths[] = new restore_path_element(
            'section_element',
            '/activity/perform/sections/section/section_elements/section_element'
        );

        $paths[] = new restore_path_element(
            'section_element_reference',
            '/activity/perform/section_element_references/section_element_reference'
        );

        $paths[] = new restore_path_element(
            'element_response',
            '/activity/perform/sections/section/section_elements/section_element/element_responses/element_response'
        );

        $paths[] = new restore_path_element(
            'section_relationship',
            '/activity/perform/sections/section/section_relationships/section_relationship'
        );

        $paths[] = new restore_path_element(
            'track_assignment',
            '/activity/perform/tracks/track/track_assignments/track_assignment'
        );

        $paths[] = new restore_path_element(
            'track_user_assignment',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment'
        );

        $paths[] = new restore_path_element(
            'track_user_assignment_via',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment/track_user_assignment_vias/track_user_assignment_via'
        );

        $paths[] = new restore_path_element(
            'subject_instance',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment/subject_instances/subject_instance'
        );

        $paths[] = new restore_path_element(
            'participant_instance',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment/subject_instances/subject_instance/participant_instances/participant_instance'
        );

        $paths[] = new restore_path_element(
            'participant_section',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment/subject_instances/subject_instance/participant_instances/participant_instance/participant_sections/participant_section'
        );

        $paths[] = new restore_path_element(
            'manual_relationship',
            '/activity/perform/manual_relation_selections/manual_relation_selection'
        );

        $paths[] = new restore_path_element(
            'manual_relationship_progress',
            '/activity/perform/manual_relationships/manual_relationship/manual_relationships_progresses/manual_relationships_progress'
        );

        $paths[] = new restore_path_element(
            'manual_relation_selector',
            '/activity/perform/manual_relationships/manual_relationship/manual_relationships_progresses/manual_relationships_progress/manual_relation_selectors/manual_relation_selector'
        );

        $paths[] = new restore_path_element(
            'subject_instance_manual_participant',
            '/activity/perform/tracks/track/track_user_assignments/track_user_assignment/subject_instances/subject_instance/subject_instance_manual_participants/subject_instance_manual_participant'
        );

        $paths[] = new restore_path_element(
            'notification',
            '/activity/perform/notifications/notification'
        );

        $paths[] = new restore_path_element(
            'notification_recipient',
            '/activity/perform/notifications/notification/notification_recipients/notification_recipient'
        );

        $paths[] = new restore_path_element(
            'notification_preference',
            '/activity/perform/notification_preferences/notification_preference'
        );

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_perform($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->course = $this->get_courseid();
        $data->status = 0; // Lets keep it draft

        $is_cloning = $this->get_setting_value('is_cloning');
        if ($is_cloning && !preg_match('/^multilang:/', $data->name)) {
            $suffix = get_string('activity_name_restore_suffix', 'mod_perform');
            $data->name = util::augment_text($data->name, activity::NAME_MAX_LENGTH, '', $suffix);
        }

        $data->created_at = time();
        $data->updated_at = $data->created_at;

        // Without the types being part of the backup we cannot map the id
        //$data->type_id = $this->get_mappingid('perform_type', $data->type_id);

        $new_item_id = $DB->insert_record('perform', $data);
        $this->apply_activity_instance($new_item_id);
        $this->set_mapping('perform', $old_id, $new_item_id);
    }

    protected function process_setting($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->activity_id = $this->get_new_parentid('perform');
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_setting', $data);
        $this->set_mapping('perform_setting', $old_id, $new_item_id);
    }

    protected function process_external_participant($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->created_at = time();

        $new_item_id = $DB->insert_record('perform_participant_external', $data);
        $this->set_mapping('perform_external_participant', $old_id, $new_item_id);
    }

    protected function process_section($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->activity_id = $this->get_new_parentid('perform');
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_section', $data);
        $this->set_mapping('perform_section', $old_id, $new_item_id);
    }

    protected function process_element($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $activity_id = $this->get_new_parentid('perform');
        $data->context_id = activity::load_by_id($activity_id)->get_context()->id;

        // If the element has its own cloner then use that instead.
        $class = "\\performelement_{$data->plugin_name}\\models\\helpers\\element_clone";
        if (class_exists($class)) {
            /** @var element_cloning $element_clone */
            $element_clone = new $class();
            $new_item_id = $element_clone->create($activity_id, $data);
        } else {
            $new_item_id = $DB->insert_record('perform_element', $data);
        }

        $this->set_mapping('perform_element', $old_id, $new_item_id);
    }

    protected function process_sub_element($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $activity_id = $this->get_new_parentid('perform');
        $data->context_id = activity::load_by_id($activity_id)->get_context()->id;
        $data->parent = $this->get_mappingid('perform_element', $data->parent);

        // If the element has its own cloner then use that instead.
        $class = "\\performelement_{$data->plugin_name}\\models\\helpers\\element_clone";
        if (class_exists($class)) {
            $element_clone = new $class();
            $new_item_id = $element_clone->create($activity_id, $data);
        } else {
            $new_item_id = $DB->insert_record('perform_element', $data);
        }

        $this->set_mapping('perform_sub_element', $old_id, $new_item_id);
    }

    protected function process_element_identifier($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $activity_id = $this->get_new_parentid('perform');
        $data->context_id = activity::load_by_id($activity_id)->get_context()->id;

        // Only create new element identifiers if they don't already exist - otherwise use the existing id
        $existing_identifier = $DB->get_record('perform_element_identifier', ['identifier' => $data->identifier]);
        if ($existing_identifier) {
            $new_item_id = $existing_identifier->id;
        } else {
            $new_item_id = $DB->insert_record('perform_element_identifier', $data);
        }
        $this->set_mapping('perform_element_identifier', $old_id, $new_item_id);
    }

    protected function process_section_element($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->section_id = $this->get_mappingid('perform_section', $data->section_id);
        $data->element_id = $this->get_mappingid('perform_element', $data->element_id);

        $new_item_id = $DB->insert_record('perform_section_element', $data);

        $element = element::load_by_id($data->element_id);
        $helper = $element->get_element_plugin()->get_clone_helper();

        if ($helper) {
            $helper->restore($new_item_id, $data, $element);
        }

        $this->set_mapping('perform_section_element', $old_id, $new_item_id);
    }

    protected function process_section_element_reference($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;
        $data->referencing_element_id = $this->get_mappingid('perform_element', $data->referencing_element_id);

        $section_element_reference = section_element_reference::load_by_id($data->id);

        // When referencing a section element inside the same activity,
        // we have to adjust the source section element to point at the clone, not the original.
        if ($this->get_mappingid('perform', $section_element_reference->get_source_activity_id()) === $this->get_new_parentid('perform')) {
            $data->source_section_element_id = $this->get_mappingid('perform_section_element', $data->source_section_element_id);
        }

        $new_item_id = $DB->insert_record('perform_section_element_reference', $data);

        $this->set_mapping('section_element_reference', $old_id, $new_item_id);
    }

    protected function process_element_response($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->section_element_id = $this->get_mappingid('perform_section_element', $data->section_element_id);
        $data->participant_instance_id = $this->get_mappingid('perform_participant_instance', $data->participant_instance_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_element_response', $data);
        $this->set_mapping('perform_element_response', $old_id, $new_item_id);
    }

    protected function process_section_relationship($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->created_at = time();
        $data->section_id = $this->get_mappingid('perform_section', $data->section_id);

        $new_item_id = $DB->insert_record('perform_section_relationship', $data);
        $this->set_mapping('perform_section_relationship', $old_id, $new_item_id);
    }

    protected function process_notification($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->created_at = time();
        $data->activity_id = $this->get_new_parentid('perform');

        $new_item_id = $DB->insert_record('perform_notification', $data);
        $this->set_mapping('notification', $old_id, $new_item_id);
    }

    protected function process_notification_recipient($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->notification_id = $this->get_new_parentid('notification');

        $new_item_id = $DB->insert_record('perform_notification_recipient', $data);
        $this->set_mapping('notification_recipient', $old_id, $new_item_id);
    }

    protected function process_track($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->activity_id = $this->get_new_parentid('perform');
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_track', $data);
        $this->set_mapping('perform_track', $old_id, $new_item_id);
    }

    protected function process_track_assignment($data) {
        global $DB, $USER;

        $data = (object)$data;
        $old_id = $data->id;

        $data->track_id = $this->get_mappingid('perform_track', $data->track_id);
        $data->created_by = $USER->id;
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_track_assignment', $data);
        $this->set_mapping('perform_track_assignment', $old_id, $new_item_id);
    }

    protected function process_track_user_assignment($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->track_id = $this->get_mappingid('perform_track', $data->track_id);
        $data->job_assignment_id = $this->get_mappingid('job_assignment', $data->job_assignment_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;
        $data->period_start_date = $this->apply_date_offset($data->period_start_date);
        $data->period_end_date = $this->apply_date_offset($data->period_end_date);

        $new_item_id = $DB->insert_record('perform_track_user_assignment', $data);
        $this->set_mapping('perform_track_user_assignment', $old_id, $new_item_id);
    }

    protected function process_track_user_assignment_via($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->track_user_assignment_id = $this->get_mappingid('perform_track_user_assignment', $data->track_user_assignment_id);
        $data->track_assignment_id = $this->get_mappingid('perform_track_assignment', $data->track_assignment_id);
        $data->created_at = time();

        $new_item_id = $DB->insert_record('perform_track_user_assignment_via', $data);
        $this->set_mapping('perform_track_user_assignment_via', $old_id, $new_item_id);
    }

    protected function process_subject_instance($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->track_user_assignment_id = $this->get_mappingid('perform_track_user_assignment', $data->track_user_assignment_id);
        $data->job_assignment_id = $this->get_mappingid('job_assignment', $data->job_assignment_id);
        $data->subject_user_id = $this->get_mappingid('user', $data->subject_user_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;
        $data->completed_at = $this->apply_date_offset($data->completed_at);
        $data->closed_at = $this->apply_date_offset($data->closed_at);

        $new_item_id = $DB->insert_record('perform_subject_instance', $data);
        $this->set_mapping('perform_subject_instance', $old_id, $new_item_id);
    }

    protected function process_participant_instance($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->participant_id = $data->participant_source === participant_source::INTERNAL
            ? $this->get_mappingid('user', $data->participant_id)
            : $this->get_mappingid('perform_external_participant', $data->participant_id);

        $data->subject_instance_id = $this->get_mappingid('perform_subject_instance', $data->subject_instance_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_participant_instance', $data);
        $this->set_mapping('perform_participant_instance', $old_id, $new_item_id);
    }

    protected function process_participant_section($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->participant_instance_id = $this->get_mappingid('perform_participant_instance', $data->participant_instance_id);
        $data->section_id = $this->get_mappingid('perform_section', $data->section_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_participant_section', $data);
        $this->set_mapping('perform_participant_section', $old_id, $new_item_id);
    }

    protected function process_manual_relationship($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->activity_id = $this->get_mappingid('perform', $data->activity_id);
        // Without the core_relationship data in the backup we cannot map the ids
        // $data->manual_relationship_id = $this->get_mappingid('totara_core_relationship', $data->manual_relationship_id);
        // $data->selector_relationship_id = $this->get_mappingid('totara_core_relationship', $data->selector_relationship_id);

        $data->created_at = time();

        $new_item_id = $DB->insert_record('perform_manual_relation_selection', $data);
        $this->set_mapping('perform_manual_relation_selection', $old_id, $new_item_id);
    }

    protected function process_manual_relationship_progress($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->subject_instance_id = $this->get_mappingid('perform_subject_instance', $data->subject_instance_id);
        $data->manual_relation_selection_id = $this->get_mappingid('perform_manual_relation_selection', $data->manual_relation_selection_id);
        $data->created_at = time();
        $data->updated_at = $data->created_at;

        $new_item_id = $DB->insert_record('perform_manual_relation_selection_progress', $data);
        $this->set_mapping('perform_manual_relation_selection_progress', $old_id, $new_item_id);
    }

    protected function process_manual_relation_selector($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->manual_relation_select_progress_id = $this->get_mappingid('perform_manual_relation_selection_progress', $data->manual_relation_select_progress_id);
        $data->user_id = $this->get_mappingid('user', $data->user_id);

        if ($this->get_setting_value('is_cloning')) {
            $data->notified_at = null;
        } else {
            $data->notified_at = $this->apply_date_offset($data->notified_at);
        }
        $data->created_at = time();

        $new_item_id = $DB->insert_record('perform_manual_relation_selector', $data);
        $this->set_mapping('perform_manual_relation_selector', $old_id, $new_item_id);
    }

    protected function process_subject_instance_manual_participant($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->subject_instance_id = $this->get_mappingid('perform_subject_instance', $data->subject_instance_id);
        $data->core_relationship_id = $this->get_mappingid('totara_core_relationship', $data->core_relationship_id);
        $data->user_id = $this->get_mappingid('user', $data->user_id);
        $data->created_by = $this->get_mappingid('user', $data->created_by);
        $data->created_at = time();

        $new_item_id = $DB->insert_record('perform_subject_instance_manual_participant', $data);
        $this->set_mapping('perform_subject_instance_manual_participant', $old_id, $new_item_id);
    }

    protected function process_notification_preference($data) {
        global $DB;

        $data = (object)$data;
        $old_id = $data->id;

        $data->time_created = time();
        $activity_id = $this->get_new_parentid('perform');
        $data->context_id = activity::load_by_id($activity_id)->get_context()->id;

        $new_item_id = $DB->insert_record('notification_preference', $data);
        $this->set_mapping('notification_preference', $old_id, $new_item_id);
    }
}
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

use backup;
use backup_nested_element;

global $CFG;
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');

class backup_activity_structure_step extends \backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $perform = new backup_nested_element(
            'perform',
            ['id'],
            [
                'name',
                'status',
                'description',
                'anonymous_responses',
                'created_at',
                'updated_at',
                'type_id',
            ]
        );

        $settings = new backup_nested_element('settings');
        $setting = new backup_nested_element(
            'setting',
            ['id'],
            [
                'activity_id',
                'name',
                'value',
                'created_at',
                'updated_at',
            ]
        );

        $external_participants = new backup_nested_element('external_participants');
        $external_participant = new backup_nested_element(
            'external_participant',
            ['id'],
            [
                'name',
                'email',
                'token',
                'created_at',
            ]
        );

        $sections = new backup_nested_element('sections');
        $section = new backup_nested_element(
            'section',
            ['id'],
            [
                'activity_id',
                'title',
                'sort_order',
                'created_at',
                'updated_at',
            ]
        );

        $elements = new backup_nested_element('elements');
        $element = new backup_nested_element(
            'element',
            ['id'],
            [
                'plugin_name',
                'title',
                'identifier_id',
                'data',
                'context_id',
                'parent',
                'sort_order',
                'is_required'
            ]
        );

        $sub_elements = new backup_nested_element('sub_elements');
        $sub_element = new backup_nested_element(
            'sub_element',
            ['id'],
            [
                'plugin_name',
                'title',
                'identifier_id',
                'data',
                'context_id',
                'parent',
                'sort_order',
                'is_required'
            ]
        );

        $element_identifiers = new backup_nested_element('element_identifiers');
        $element_identifier = new backup_nested_element(
            'element_identifier',
            ['id'],
            [
                'identifier',
            ]
        );

        $section_elements = new backup_nested_element('section_elements');
        $section_element = new backup_nested_element(
            'section_element',
            ['id'],
            [
                'section_id',
                'element_id',
                'sort_order',
            ]
        );

        $section_relationships = new backup_nested_element('section_relationships');
        $section_relationship = new backup_nested_element(
            'section_relationship',
            ['id'],
            [
                'section_id',
                'core_relationship_id',
                'can_view',
                'can_answer',
                'created_at',
            ]
        );

        $section_element_references = new backup_nested_element('section_element_references');
        $section_element_reference = new backup_nested_element(
            'section_element_reference',
            ['id'],
            [
                'source_section_element_id',
                'referencing_element_id',
            ]
        );

        $element_responses = new backup_nested_element('element_responses');
        $element_response = new backup_nested_element(
            'element_response',
            ['id'],
            [
                'section_element_id',
                'participant_instance_id',
                'response_data',
                'created_at',
                'updated_at',
            ]
        );

        $element_linked_review_contents = new backup_nested_element('element_linked_review_contents');
        $element_linked_review_content = new backup_nested_element(
            'element_linked_review_content',
            ['id'],
            [
                'section_element_id',
                'subject_instance_id',
                'selector_id',
                'content_id',
                'content_type',
                'meta_data',
                'created_at'
            ]
        );

        $element_linked_review_content_responses = new backup_nested_element('element_linked_review_content_responses');
        $element_linked_review_content_response = new backup_nested_element(
            'element_linked_review_content_response',
            ['id'],
            [
                'linked_review_content_id',
                'child_element_id',
                'participant_instance_id',
                'response_data',
                'created_at',
                'updated_at',
            ]
        );

        $tracks = new backup_nested_element('tracks');
        $track = new backup_nested_element(
            'track',
            ['id'],
            [
                'activity_id',
                'description',
                'status',
                'created_at',
                'updated_at',
                'schedule_fixed_from',
                'schedule_fixed_to',
                'schedule_fixed_timezone',
                'schedule_is_open',
                'schedule_is_fixed',
                'schedule_dynamic_from',
                'schedule_dynamic_to',
                'schedule_dynamic_source',
                'schedule_needs_sync',
                'schedule_use_anniversary',
                'due_date_is_enabled',
                'due_date_is_fixed',
                'due_date_fixed',
                'due_date_fixed_timezone',
                'due_date_offset',
                'repeating_is_enabled',
                'repeating_trigger',
                'repeating_type',
                'repeating_offset',
                'repeating_is_limited',
                'repeating_limit',
                'subject_instance_generation',
            ]
        );

        $track_assignments = new backup_nested_element('track_assignments');
        $track_assignment = new backup_nested_element(
            'track_assignment',
            ['id'],
            [
                'track_id',
                'type',
                'user_group_type',
                'user_group_id',
                'created_by',
                'created_at',
                'updated_at',
                'expand',
            ]
        );

        $track_user_assignments = new backup_nested_element('track_user_assignments');
        $track_user_assignment = new backup_nested_element(
            'track_user_assignment',
            ['id'],
            [
                'track_id',
                'subject_user_id',
                'deleted',
                'created_at',
                'updated_at',
                'period_start_date',
                'period_end_date',
                'job_assignment_id',
            ]
        );

        $track_user_assignment_vias = new backup_nested_element('track_user_assignment_vias');
        $track_user_assignment_via = new backup_nested_element(
            'track_user_assignment_via',
            ['id'],
            [
                'track_assignment_id',
                'track_user_assignment_id',
                'created_at',
            ]
        );

        $subject_instances = new backup_nested_element('subject_instances');
        $subject_instance = new backup_nested_element(
            'subject_instance',
            ['id'],
            [
                'track_user_assignment_id',
                'subject_user_id',
                'created_at',
                'updated_at',
                'progress',
                'availability',
                'job_assignment_id',
                'completed_at',
                'closed_at',
                'due_date',
                'status',
                'task_id',
                'needs_sync',
            ]
        );

        $participant_instances = new backup_nested_element('participant_instances');
        $participant_instance = new backup_nested_element(
            'participant_instance',
            ['id'],
            [
                'core_relationship_id',
                'participant_id',
                'participant_source',
                'subject_instance_id',
                'progress',
                'availability',
                'created_at',
                'updated_at',
                'task_id',
            ]
        );

        $participant_sections = new backup_nested_element('participant_sections');
        $participant_section = new backup_nested_element(
            'participant_section',
            ['id'],
            [
                'section_id',
                'participant_instance_id',
                'progress',
                'created_at',
                'updated_at',
                'availability',
            ]
        );

        $manual_relationships = new backup_nested_element('manual_relation_selections');
        $manual_relationship = new backup_nested_element(
            'manual_relation_selection',
            ['id'],
            [
                'activity_id',
                'manual_relationship_id',
                'selector_relationship_id',
                'created_at'
            ]
        );

        $manual_relationships_progresses = new backup_nested_element('manual_relationships_progresses');
        $manual_relationships_progress = new backup_nested_element(
            'manual_relation_selection_progress',
            ['id'],
            [
                'subject_instance_id',
                'manual_relation_selection_id',
                'status',
                'created_at',
                'updated_at'
            ]
        );

        $manual_relation_selections = new backup_nested_element('manual_relation_selectors');
        $manual_relation_selected = new backup_nested_element(
            'manual_relation_selector',
            ['id'],
            [
                'manual_relation_select_progress_id',
                'user_id',
                'notified_at',
                'created_at'
            ]
        );

        $subject_instance_manual_participants = new backup_nested_element('subject_instance_manual_participants');
        $subject_instance_manual_participant = new backup_nested_element(
            'subject_instance_manual_participant',
            ['id'],
            [
                'subject_instance_id',
                'core_relationship_id',
                'user_id',
                'name',
                'email',
                'created_at',
                'created_by'
            ]
        );

        $subject_static_instances = new backup_nested_element('subject_static_instances');
        $subject_static_instance = new backup_nested_element(
            'subject_static_instance',
            ['id'],
            [
                'subject_instance_id',
                'job_assignment_id',
                'manager_job_assignment_id',
                'position_id',
                'organisation_id',
                'appraiser_id',
                'created_at',
                'updated_at',
            ]
        );

        $notifications = new backup_nested_element('notifications');
        $notification = new backup_nested_element(
            'notification',
            ['id'],
            [
                'activity_id',
                'class_key',
                'active',
                'triggers',
                'last_run_at',
                'created_at',
                'updated_at',
            ]
        );

        $notification_recipients = new backup_nested_element('notification_recipients');
        $notification_recipient = new backup_nested_element(
            'notification_recipient',
            ['id'],
            [
                'active',
                'notification_id',
                'core_relationship_id',
            ]
        );

        // Totara centralised notifications for perform.
        $totara_notifications = new backup_nested_element('notification_preferences');
        $totara_notification = new backup_nested_element(
            'notification_preference',
            ['id'],
            [
                'ancestor_id',
                'resolver_class_name',
                'notification_class_name',
                'context_id',
                'component',
                'area',
                'item_id',
                'title',
                'additional_criteria',
                'recipient',
                'recipients',
                'subject',
                'subject_format',
                'body',
                'body_format',
                'time_created',
                'schedule_offset',
                'enabled',
                'forced_delivery_channels',
                'body_backup',
                'subject_backup'
            ]
        );

        $perform->add_child($settings);
        $settings->add_child($setting);

        $perform->add_child($elements);
        $elements->add_child($element);

        $element->add_child($sub_elements);
        $sub_elements->add_child($sub_element);

        $perform->add_child($element_identifiers);
        $element_identifiers->add_child($element_identifier);

        $perform->add_child($sections);
        $sections->add_child($section);
        $section->add_child($section_elements);
        $section_elements->add_child($section_element);

        $section->add_child($section_relationships);
        $section_relationships->add_child($section_relationship);

        $perform->add_child($section_element_references);
        $section_element_references->add_child($section_element_reference);

        $perform->add_child($tracks);
        $tracks->add_child($track);
        $track->add_child($track_assignments);
        $track_assignments->add_child($track_assignment);

        $perform->add_child($manual_relationships);
        $manual_relationships->add_child($manual_relationship);

        // Define sources (in the same order as above).
        $perform->set_source_table('perform', ['id' => backup::VAR_ACTIVITYID]);
        $setting->set_source_table('perform_setting', ['activity_id' => backup::VAR_PARENTID]);

        $sub_element->set_source_table('perform_element', ['parent' => backup::VAR_PARENTID]);

        $track->set_source_table('perform_track', ['activity_id' => backup::VAR_PARENTID]);
        $track_assignment->set_source_table('perform_track_assignment', ['track_id' => backup::VAR_PARENTID]);

        $section->set_source_table('perform_section', ['activity_id' => backup::VAR_PARENTID]);
        $section_element->set_source_table('perform_section_element', ['section_id' => backup::VAR_PARENTID]);
        $section_relationship->set_source_table('perform_section_relationship', ['section_id' => backup::VAR_PARENTID]);

        $manual_relationship->set_source_table('perform_manual_relation_selection', ['activity_id' => backup::VAR_PARENTID]);

        // Notifications.
        $perform->add_child($notifications);
        $notifications->add_child($notification);
        $notification->add_child($notification_recipients);
        $notification_recipients->add_child($notification_recipient);

        $perform->add_child($totara_notifications);
        $totara_notifications->add_child($totara_notification);

        $notification->set_source_table(
            'perform_notification',
            ['activity_id' => backup::VAR_PARENTID]
        );
        $notification_recipient->set_source_table(
            'perform_notification_recipient',
            ['notification_id' => backup::VAR_PARENTID]
        );

        $element->set_source_sql(
            "SELECT pe.*
               FROM {perform_element} pe
               JOIN {perform_section_element} pse ON pse.element_id = pe.id
               JOIN {perform_section} ps ON pse.section_id = ps.id
              WHERE ps.activity_id = :activity_id
              AND pe.parent IS NULL",
            ['activity_id' => backup::VAR_ACTIVITYID]
        );

        $sub_element->set_source_sql(
            "SELECT pe.*
               FROM {perform_element} pe
              WHERE pe.parent = :parent",
            ['parent' => backup::VAR_PARENTID]
        );

        $element_identifier->set_source_sql(
            "SELECT pei.*
               FROM {perform_element_identifier} pei
               JOIN {perform_element} pe ON pe.identifier_id = pei.id
               JOIN {perform_section_element} pse ON pse.element_id = pe.id
               JOIN {perform_section} ps ON pse.section_id = ps.id
              WHERE ps.activity_id = :activity_id",
            ['activity_id' => backup::VAR_PARENTID]
        );

        $section_element_reference->set_source_sql(
            "SELECT ser.*
               FROM {perform_section_element_reference} ser
               JOIN {perform_section_element} referencing_section_element ON referencing_section_element.element_id = ser.referencing_element_id
               JOIN {perform_section} referencing_section ON referencing_section_element.section_id = referencing_section.id
              WHERE referencing_section.activity_id = :activity_id",
            ['activity_id' => backup::VAR_PARENTID]
        );

        $totara_notification->set_source_sql(
            "SELECT np.*
               FROM {notification_preference} np
               JOIN {context} ct ON ct.id = np.context_id
               JOIN {course_modules} cm ON cm.id = ct.instanceid
               JOIN {course} c ON c.id = cm.course
               JOIN {perform} p ON p.id = cm.instance AND p.course = c.id
            WHERE p.id = :activity_id
            AND np.component = ''
            AND np.area = ''
            AND np.item_id = 0",
            ['activity_id' => backup::VAR_PARENTID]
        );

        $perform->annotate_ids('perform_type', 'type_id');
        $track_assignment->annotate_ids('user', 'created_by');

        $element->annotate_ids('context', 'context_id');
        $element->annotate_ids('element_identifier', 'identifier_id');

        $sub_element->annotate_ids('context', 'context_id');
        $sub_element->annotate_ids('element', 'parent');
        $sub_element->annotate_ids('element_identifier', 'identifier_id');

        $participant_instance->annotate_ids('totara_core_relationship', 'core_relationship_id');
        $participant_section->annotate_ids('perform_section', 'section_id');

        $manual_relationship->annotate_ids('totara_core_relationship', 'manual_relationship_id');
        $manual_relationship->annotate_ids('totara_core_relationship', 'selector_relationship_id');

        $totara_notification->annotate_ids('context', 'context_id');

        if ($userinfo) {
            $perform->add_child($external_participants);
            $external_participants->add_child($external_participant);

            $section_element->add_child($element_responses);
            $element_responses->add_child($element_response);

            $section_element->add_child($element_linked_review_contents);
            $element_linked_review_contents->add_child($element_linked_review_content);
            $element_linked_review_content->add_child($element_linked_review_content_responses);
            $element_linked_review_content_responses->add_child($element_linked_review_content_response);

            $track->add_child($track_user_assignments);
            $track_user_assignments->add_child($track_user_assignment);
            $track_user_assignment->add_child($track_user_assignment_vias);
            $track_user_assignment_vias->add_child($track_user_assignment_via);
            $track_user_assignment->add_child($subject_instances);
            $subject_instances->add_child($subject_instance);
            $subject_instance->add_child($participant_instances);
            $participant_instances->add_child($participant_instance);
            $participant_instance->add_child($participant_sections);
            $participant_sections->add_child($participant_section);

            $manual_relationship->add_child($manual_relationships_progresses);
            $manual_relationships_progresses->add_child($manual_relationships_progress);
            $manual_relationships_progress->add_child($manual_relation_selections);
            $manual_relation_selections->add_child($manual_relation_selected);

            $subject_instance->add_child($subject_instance_manual_participants);
            $subject_instance_manual_participants->add_child($subject_instance_manual_participant);

            $subject_instance->add_child($subject_static_instances);
            $subject_static_instances->add_child($subject_static_instance);

            $external_participant->set_source_table('perform_participant_external', []);

            $element_linked_review_content->set_source_table(
                'perform_element_linked_review_content',
                ['section_element_id' => backup::VAR_PARENTID]
            );
            $element_linked_review_content_response->set_source_table(
                'perform_element_linked_review_content_response',
                ['linked_review_content_id' => backup::VAR_PARENTID]
            );

            $element_response->set_source_table(
                'perform_element_response',
                ['section_element_id' => backup::VAR_PARENTID]
            );
            $track_user_assignment->set_source_table(
                'perform_track_user_assignment',
                ['track_id' => backup::VAR_PARENTID]
            );
            $track_user_assignment_via->set_source_table(
                'perform_track_user_assignment_via',
                ['track_user_assignment_id' => backup::VAR_PARENTID]
            );
            $subject_instance->set_source_table(
                'perform_subject_instance',
                ['track_user_assignment_id' => backup::VAR_PARENTID]
            );
            $participant_instance->set_source_table(
                'perform_participant_instance',
                ['subject_instance_id' => backup::VAR_PARENTID]
            );
            $participant_section->set_source_table(
                'perform_participant_section',
                ['participant_instance_id' => backup::VAR_PARENTID]
            );
            $manual_relationships_progress->set_source_table(
                'perform_manual_relation_selection_progress',
                ['manual_relation_selection_id' => backup::VAR_PARENTID]
            );
            $manual_relation_selected->set_source_table(
                'perform_manual_relation_selector',
                ['manual_relation_select_progress_id' => backup::VAR_PARENTID]
            );
            $subject_instance_manual_participant->set_source_table(
                'perform_subject_instance_manual_participant',
                ['subject_instance_id' => backup::VAR_PARENTID]
            );
            $subject_static_instance->set_source_table(
                'perform_subject_static_instance',
                ['subject_instance_id' => backup::VAR_PARENTID]
            );

            $track_user_assignment->annotate_ids('user', 'subject_user_id');
            $track_user_assignment->annotate_ids('job_assignment', 'job_assignment_id');
            $subject_instance->annotate_ids('user', 'subject_user_id');
            $subject_instance->annotate_ids('job_assignment', 'job_assignment_id');
            $subject_static_instance->annotate_ids('job_assignment', 'job_assignment_id');
            $subject_static_instance->annotate_ids('job_assignment', 'manager_job_assignment_id');

            $participant_instance->annotate_ids('user', 'participant_id');

            $element_response->annotate_ids('perform_participant_instance', 'participant_instance_id');
            $track_user_assignment_via->annotate_ids('perform_track_assignment', 'track_assignment_id');

            $manual_relationships_progress->annotate_ids('perform_subject_instance', 'subject_instance_id');
            $manual_relation_selected->annotate_ids('user', 'user_id');
            $subject_instance_manual_participant->annotate_ids('user', 'user_id');
            $subject_instance_manual_participant->annotate_ids('totara_core_relationship', 'core_relationship_id');
        }

        return $this->prepare_activity_structure($perform);
    }

}

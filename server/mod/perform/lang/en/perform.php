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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

$string['pluginname'] = 'Performance activity';

$string['a11y_activity_created_at_label'] = 'Created:';
$string['a11y_activity_job_assignment_label'] = 'Job assignment:';
$string['a11y_activity_type_label'] = 'Type:';
$string['access_check_error'] = 'Cannot access this activity';
$string['actions'] = 'Actions';
$string['actions_for_item'] = 'Actions for "{$a}"';
$string['activation_criteria_assignments'] = 'At least one group assigned';
$string['activation_criteria_elements'] = 'At least one question element added per section';
$string['activation_criteria_relationships'] = 'At least one responding participant added (if multiple sections enabled, must be on a section containing a question)';
$string['activation_criteria_schedule'] = 'Valid activity instance creation schedule';
$string['activity_action_activate'] = 'Activate';
$string['activity_action_clone'] = 'Clone';
$string['activity_action_delete'] = 'Delete';
$string['activity_action_options'] = 'Actions';
$string['activity_availability_closed'] = 'Closed';
$string['activity_content_tab_heading'] = 'Content';
$string['activity_draft_not_ready'] = 'Draft is not yet ready for activation';
$string['activity_due_on'] = 'Due on {$a}';
$string['activity_first_assigned_date'] = 'Date first assigned to this activity';
$string['activity_general_tab_heading'] = 'General';
$string['activity_general_response_attribution_heading'] = 'Response attribution and visibility';
$string['activity_general_anonymous_responses_label'] = 'Anonymise responses';
$string['activity_general_anonymous_responses_label_help'] = 'When anonymised, the responding participant’s name and relationship will not be shown to other participants, or in reports. To ensure anonymity, this setting cannot be changed once an activity is active, and closure of all response submissions is enforced as the condition for response visibility.';
$string['activity_instance_creation_heading'] = 'Activity instance creation';
$string['activity_job_idnumber'] = 'Activity job ID number';
$string['activity_job_title'] = 'Activity job title';
$string['activity_name'] = 'Performance activity name';
$string['activity_name_linked_to_view_form'] = 'Performance activity name (linked to view form)';
$string['activity_name_restore_suffix'] = ' - Copy';
$string['activity_name_with_section_name_and_element_type'] = '{$a->activity_name} : {$a->section_name} ({$a->element_type})';
$string['activity_overall_progress_status_complete'] = 'Activity is complete';
$string['activity_overall_progress_status_in_progress'] = 'Activity is in progress';
$string['activity_overall_progress_status_not_applicable'] = 'Activity is n/a (view only)';
$string['activity_overall_progress_status_not_started'] = 'Activity is not started';
$string['activity_overall_progress_status_not_submitted'] = 'Activity is not submitted';
$string['activity_overall_progress_status_overdue'] = 'Activity is overdue';
$string['activity_participant_can_view'] = 'Can view others\' responses';
$string['activity_participant_view_other_responses'] = '(*Can view others\' responses)';
$string['activity_participants_add'] = 'Add participants';
$string['activity_participants_view_only_add'] = 'Add view-only participants';
$string['activity_participants_heading'] = 'Responding participants';
$string['activity_participants_view_only_heading'] = 'View-only participants';
$string['activity_participants_select_done'] = 'Done';
$string['activity_participants_select_heading'] = 'Select participants';
$string['activity_report_no_params_warning_message'] = 'This report can only be accessed from the performance reporting interface - go to the <a href="{$a->url}">performance reporting</a> page, and select some data to report on.';
$string['activity_response_data_actions'] = 'Actions';
$string['activity_section_done'] = 'Done';
$string['activity_status_active'] = 'Active';
$string['activity_status_banner_active'] = 'This activity is <strong>active</strong>. Changes can be applied that will affect future and, in some cases, existing instances.';
$string['activity_status_banner_draft'] = 'This activity is currently in a <strong>draft state</strong>.';
$string['activity_status_banner_help_intro'] = 'It can be activated once all of the following criteria are met:';
$string['activity_status_banner_help_title'] = 'Show activity activation criteria';
$string['activity_status_draft'] = 'Draft';
$string['activity_title_for_subject'] = '{$a->activity} for {$a->user}';
$string['activity_title_with_subject_creation_date'] = '{$a->title} ({$a->date})';
$string['activity_type'] = 'Activity type';
$string['activity_type_help_text'] = 'This can be changed later as long as the activity is still in draft.';
$string['activity_your_progress_label'] = 'Your progress:';
$string['add_activity'] = 'Add activity';
$string['add_your_response'] = 'Add your response';
$string['add_participants_page_title'] = 'Add participants';
$string['add_section'] = 'Add section';
$string['add_section_error_section_mode'] = 'Activity is not in multi-section mode';
$string['anonymous_group_relationship_name'] = 'Anonymous';
$string['all_performance_data_records'] = 'All performance data records';
$string['all_responses_anonymised'] = 'All responses anonymised';
$string['all_relationships_select_option_label'] = 'All';
$string['automatic_closure_status_mismatch_warning'] = 'Closure is set as a condition for response visibility, but this cannot be met by participants without manual intervention while automatic closure is disabled. Enable it, or change the visibility condition on the "General" tab to "None".';
$string['availability'] = 'Availability';
$string['back_to_activity'] = '« Back to performance activity';
$string['back_to_activity_content'] = 'Content ({$a})';
$string['back_to_all_activities'] = 'Back to all performance activities';
$string['back_to_manage_participation'] = '« Back to manage participation';
$string['back_to_report'] = 'Back to Performance data for {$a}';
$string['back_to_user_activities'] = 'Performance activities';
$string['bulk_export'] = 'Bulk export';
$string['bulk_export_filter_changed_warning_message'] = 'New report filters have been applied since the page where you requested the export was last updated, and your export content will be different to what was shown on the page. This could be due to changes made in another browser tab or window to the same report. Please go back and reapply the search before exporting again, to ensure you receive the correct records';
$string['bulk_export_no_params_warning_message'] = 'This report can only be accessed from the performance reporting interface - to select records to export, go to the <a href="{$a->url}">performance reporting</a> page, and select some data to export.';
$string['bulk_export_type_incorrect'] = 'Invalid bulk export type';
$string['bulk_export_shortname_incorrect'] = 'Bulk export with an invalid embedded report shortname attempted.';
$string['bulk_response_data_filter_changed_warning_message'] = 'New report filters have been applied since the page where you requested to view the report was last updated, and your report content will be different to what was shown on the page. This could be due to changes made in another browser tab or window to the same report. Please go back and reapply the search before viewing again, to ensure you receive the correct records';
$string['bulk_response_data_type_incorrect'] = 'Invalid response data report type';
$string['bulk_response_data_shortname_incorrect'] = 'Response data report with an invalid embedded report shortname attempted.';
$string['bulk_response_data_no_params_warning_message'] = 'This report can only be accessed from the performance reporting interface - to select records to view, go to the <a href="{$a->url}">performance reporting</a> page, and select some data to view.';
$string['button_delete']  = 'Delete';
$string['button_cancel']  = 'Cancel';
$string['button_close'] = 'Close';
$string['button_continue'] = 'Continue';
$string['button_create']  = 'Create';
$string['button_done']  = 'Done';
$string['button_export'] = 'Export';
$string['button_reopen'] = 'Reopen';
$string['button_save']  = 'Save';
$string['button_view']  = 'View';
$string['button_view_as_report']  = 'View as report';
$string['button_close_all_instances']  = 'Close all instances';
$string['boolean_setting_text_enabled'] = 'Enabled';
$string['boolean_setting_text_disabled'] = 'Disabled';
$string['browse_records_by_content'] = 'Browse records by content';
$string['browse_records_by_user'] = 'Browse records by user';
$string['cannot_manage_participant'] = 'You do not have permission to manage participation';
$string['can_not_edit_section_elements'] = 'Cannot edit content elements';
$string['can_not_view_element'] = 'You do not have permission to view this element';
$string['can_not_view_report_identifiers'] = 'You do not have permission to view reporting identifiers';
$string['can_not_view_relationships'] = 'You do not have permission to view relationships';
$string['check_notification_trigger_task'] = 'Periodically check notification trigger conditions';
$string['choose_element_type_plural'] = 'Choose element types';
$string['choose_relationship_name_plural'] = 'Choose relationship names';
$string['choose_section_id_plural'] = 'Choose section titles';
$string['cleanup_unused_element_identifiers_task'] = 'Clean up unused element identifiers';
$string['close_all'] = 'Close all';
$string['close_all_instances'] = 'Close all instances';
$string['close_all_instances_modal_message'] = 'This will close all the subject instances that are currently open to prevent any further submission of responses from all participants, regardless of their progress.';
$string['close_activity_subject_instances_on_due_date_task'] = 'Close activity subject instances on due date';
$string['condition_actor_is_participant_fail'] = 'The currently logged in user is not the participant.';
$string['conditional_duedate_participant_placeholder'] = '
This needs to be completed by {$a->duedate}.
';
$string['conditional_duedate_subject_placeholder'] = '
Their input is needed by {$a->duedate}.
';
$string['configshowhistoricactivities'] = 'Use this setting to make any historic activities from Legacy Appraisals and 360 Feedback visible to assigned users under their Performance Activities.

Note: Legacy appraisals and legacy 360 feedback features must also be enabled in order to make historic activities visible.';
$string['confirm_selection'] = 'Confirm selection';
$string['create_activity'] = 'Add activity';
$string['create_activity_select_placeholder'] = 'Select...';
$string['create_activity_title'] = 'Title';
$string['create_activity_type'] = 'Type';
$string['create_manual_participant_progress_task'] = 'Create progress records for selecting manual participants in performance activities';
$string['create_subject_instance_task'] = 'Create subject instance for performance activities';
$string['created_at'] = 'Created at';
$string['date'] = 'Date';
$string['date_completed'] = 'Date completed';
$string['date_created'] = 'Instance creation date';
$string['date_updated'] = 'Date updated';
$string['default_sort'] = 'Default sort';
$string['delete_element'] = 'Delete element: {$a}';
$string['delete_relationship'] = 'Delete {$a}';
$string['deleted_dynamic_source_label'] = '{$a} (deleted)';
$string['deleted_user'] = 'Deleted user';
$string['development'] = 'Development';
$string['due_date'] = 'Due date';
$string['due_date_disabled'] = 'Due date disabled';
$string['due_date_disabled_description'] = 'Participants are not expected to submit their responses by a certain date.';
$string['due_date_enabled'] = 'Due date enabled';
$string['due_date_enabled_description'] = 'Participants are expected to submit their responses by a certain date - reminder notifications can be triggered based on this.';
$string['due_date_enabled_fixed_date'] = 'calendar date (same fixed date for all instances)';
$string['due_date_enabled_relative_date'] = 'time since creation of instance:';
$string['due_date_enabled_relative_date_label'] = 'Time since creation of instance';
$string['due_date_enabled_relative_date_a11y'] = 'within {$a->value} {$a->range} after each instance creation date';
$string['due_date_error_must_be_after_creation_date'] = 'Due date must be after the creation end date';
$string['due_date_error_not_integer'] = 'Please enter a valid whole number';
$string['due_date_is_disabled'] = 'Disabled';
$string['due_date_is_enabled'] = 'Enabled';
$string['edit_content_elements'] = 'Edit content elements';
$string['edit_element'] = 'Edit element: {$a}';
$string['edit_section'] = 'Edit section';
$string['element_action_options'] = 'Actions';
$string['element_data'] = 'Data';
$string['element_description'] = 'Description';
$string['element_description_hide'] = 'Hide description';
$string['element_description_show'] = 'Show description';
$string['element_enable_description'] = 'Enable description';
$string['element_enable_option_description'] = 'Enable description {$a}';
$string['element_identifier'] = 'Reporting ID';
$string['element_is_required'] = 'Required';
$string['element_option_description'] = 'Description {$a}';
$string['element_reporting_title_activity'] = 'Activity';
$string['element_reporting_title_element_type'] = 'Element type';
$string['element_reporting_title_required'] = 'Required';
$string['element_reporting_title_responding_relationship'] = 'Responding relationship';
$string['element_reporting_title_responding_relationship_id'] = 'Responding relationship id';
$string['element_reporting_title_responding_relationships'] = 'Responding relationships';
$string['element_reporting_title_section_id'] = 'Section ID';
$string['element_reporting_title_section_title'] = 'Section title';
$string['element_reporting_title_section_title_by_activity'] = 'Section title by activity';
$string['element_reporting_title_section_title_relationship_id'] = 'Section relationship id';
$string['element_title'] = 'Title';
$string['element_type'] = 'Element type';
$string['element_type_heading_a11y'] = '{$a}:';
$string['embedded_perform_manage_participation_participant_instance'] = 'Performance activity participant instances: Manage participation';
$string['embedded_perform_manage_participation_participant_section'] = 'Performance activity participant sections: Manage participation';
$string['embedded_perform_manage_participation_subject_instance'] = 'Performance activity subject instances: Manage participation';
$string['embedded_perform_participation_participant_instance'] = 'Performance activity participant instances';
$string['embedded_perform_participation_subject_instance'] = 'Performance activity subject instances';
$string['embedded_perform_response_element_by_activity'] = 'Performance activity response reporting: Elements for single activity';
$string['embedded_perform_response_element_by_reporting_id'] = 'Performance activity response reporting: Elements for set of reporting IDs';
$string['embedded_perform_response_data'] = 'Performance activity response data';
$string['embedded_perform_response_data_cloning_not_allowed'] = 'This report can not be cloned.';
$string['embedded_perform_response_export'] = 'Performance activity response export';
$string['embedded_perform_response_export_cloning_not_allowed'] = 'This report can not be cloned because it can only be used for the exporting of activity responses.';
$string['embedded_perform_response_subject_instance'] = 'Performance activity response reporting: Subject instances for single subject user';
$string['embedded_perform_response_user'] = 'Performance activity response reporting: Subject users';
$string['enable_performance_activities'] = 'Enable Performance Activities';
$string['enable_performance_activities_description'] = 'When enabled this will allow users to view performance activities. All functionality related to performance activities will be visible and accessible to users.';
$string['error_activity_id_wrong'] = 'Wrong activity id';
$string['error_activity_name_missing'] = 'You are not allowed to create an activity with an empty name';
$string['error_activity_unavailable'] = 'You cannot report on this activity because you do not have permission on any of its subject users';
$string['error_activity_type_missing'] = 'You are not allowed to create an activity without a type';
$string['error_activity_type_unknown'] = 'Invalid activity type id';
$string['error_activate'] = 'Cannot activate this activity due to invalid state or conditions are not satisfied.';
$string['error_access_permission_missing'] = 'You do not have the permission to access this performance activity';
$string['error_create_permission_missing'] = 'You do not have the permission to create a performance activity';
$string['error_export_permission_missing'] = 'You do not have the permission to export this data';
$string['error_external_participant_duplicate_email'] = 'Please enter a different email address';
$string['error_no_participants_selected'] = 'You must select at least one user.';
$string['error_subject_instance_id_wrong'] = 'Wrong subject instance id';
$string['error_updating_activity_manual_relationships'] = 'Can not update selecting relationships, activity is active';
$string['error_user_unavailable'] = 'You cannot report on this subject user because you do not have permission';
$string['event_activity_activated'] = 'Performance activity activated';
$string['event_activity_created'] = 'Performance activity created';
$string['event_activity_deleted'] = 'Performance activity deleted';
$string['event_activity_subject_instances_closed'] = 'Performance activity subject instances closed';
$string['event_participant_instance_availability_closed'] = 'Performance activity participant instance closed';
$string['event_participant_instance_availability_opened'] = 'Performance activity participant instance opened';
$string['event_participant_instance_manually_added'] = 'Performance activity participant instance manually added';
$string['event_participant_instance_manually_deleted'] = 'Performance activity participant instance manually deleted';
$string['event_participant_section_availability_closed_name'] = 'Performance participant section availability closed';
$string['event_participant_section_availability_opened_name'] = 'Performance participant section availability opened';
$string['event_participant_section_saved_as_draft'] = 'Performance participant section saved as draft';
$string['error_question_required'] = 'Question is required';
$string['event_subject_instance_activated'] = 'Performance activity subject instance activated';
$string['event_subject_instance_availability_closed'] = 'Performance activity subject instance closed';
$string['event_subject_instance_availability_opened'] = 'Performance activity subject instance opened';
$string['event_subject_instance_manual_participants_selected'] = 'Participant users manually selected for performance activity subject instance';
$string['event_subject_instance_manually_deleted'] = 'Performance activity subject instance manually deleted';
$string['event_track_schedule_changed'] = 'Performance activity track schedule changed';
$string['event_track_subject_instance_generation_changed'] = 'Performance activity track subject instance generation changed';
$string['event_track_user_assigned'] = 'User assigned to a performance activity track';
$string['event_track_users_assigned'] = 'Users assigned to a performance activity track';
$string['event_track_user_group_assigned'] = 'User group assigned to a performance activity track';
$string['event_track_user_group_unassigned'] = 'User group removed from a performance activity track';
$string['event_track_user_unassigned'] = 'User unassigned to a performance activity track';
$string['expand_assignments_task'] = 'Expand performance activity track assignments flagged for expansion';
$string['expand_all_assignments_task'] = 'Expand all active performance activity track assignments';
$string['expand_task_notification_body'] = 'All users assigned to performance activity tracks have been synced.';
$string['expand_task_notification_subject'] = 'Performance activities: Sync assigned users task is complete';
$string['export'] = 'Export';
$string['export_all'] = 'Export all';
$string['export_as_csv'] = 'Export as CSV';
$string['export_as_excel'] = 'Export as Excel';
$string['export_confirm_modal_body'] = 'If you have requested a large number of records, the export may take some time. Your browser will need to remain open until the export is complete.';
$string['export_confirm_modal_type'] = 'The selected records will be exported to {$a}, with one row per response.';
$string['export_confirm_modal_title'] = 'Export performance response records';
$string['export_invalid_format_warning_message'] = 'The format you are trying to export with ({$a}) has been disabled. Ask an admin to enable the {$a} format or to override and enable {$a} formatting on the embedded report performance tab';
$string['export_limit_exceeded_modal_text'] = 'A maximum of {$a} records can be exported in a single file. Use the filters to reduce the number of records exported in bulk and try again.';
$string['export_limit_exceeded_modal_title'] = 'Export data limit exceeded';
$string['external_participation_invalid_heading'] = 'Sorry';
$string['external_participation_invalid_message'] = 'This performance activity is no longer available.';
$string['external_participation_success_heading'] = 'Thank you';
$string['external_participation_success_message'] = 'Your responses have been submitted.';
$string['external_participation_success_message_closed'] = 'This activity is now closed.';
$string['external_participation_success_review_link_text'] = 'Review your responses.';
$string['external_user_email'] = 'Email address';
$string['external_user_email_help'] = 'External respondent {$a}\'s email address';
$string['external_user_name'] = 'Name';
$string['external_user_name_help'] = 'External respondent {$a}\'s name';
$string['export_unsupported_format_warning_message'] = 'You are attempting to export in a format which is not currently supported by this export';
$string['fixed_date_selector_date'] = 'Date';
$string['fixed_date_selector_error_range'] = 'Range end date cannot be before range start date';
$string['general_info_label_activity_description'] = 'Description';
$string['general_info_label_activity_title'] = 'Activity title';
$string['general_info_label_activity_type'] = 'Type';
$string['general_info_select_activity_type'] = 'Select a type';
$string['get_started'] = 'Get started';
$string['hidden_anonymised'] = 'Hidden (anonymised)';
$string['html_content_title'] = 'Content';
$string['in_team'] = '{$a}\'s team';
$string['instance_info_card_creation_date'] = 'Creation date';
$string['instance_info_card_show_all_button'] = 'Show all';
$string['instance_info_card_subject_full_name'] = 'Subject full name';
$string['instance_number'] = 'Instance number';
$string['instance_one'] = '1 instance';
$string['instances'] = '{$a} instances';
$string['invalid_activity'] = 'Invalid activity';
$string['invalid_change_on_closed_participant_section'] = 'Can not update response to a closed participant section';
$string['invalid_relationship'] = 'Invalid relationship';
$string['invalid_section'] = 'Invalid section';
$string['invalid_state_switch'] = 'Cannot switch from {$a->from_state} to {$a->target_state}';
$string['is_overdue'] = 'Overdue';
$string['items_selected_by'] = 'These items were selected by {$a->user} on {$a->date}.';
$string['job_assignment_start_date'] = 'Job assignment start date';
$string['manage_activities_tabs_assignment'] = 'Assignments';
$string['manage_activities_tabs_content'] = 'Content';
$string['manage_activities_tabs_general'] = 'General';
$string['manage_activities_tabs_instance_creation'] = 'Instance creation';
$string['manage_activities_tabs_notifications'] = 'Notifications';
$string['manage_activity_page_title'] = 'Manage performance activity';
$string['manage_activity_for_page_title'] = 'Manage performance activity: {$a}';
$string['manage_activity_list_filter_name'] = 'Name';
$string['manage_activity_list_filter_name_placeholder'] = 'Search activities';
$string['manage_activity_list_filter_status'] = 'Status';
$string['manage_activity_list_filter_type'] = 'Type';
$string['manage_activity_list_none_created'] = 'No activities have been created yet.';
$string['manage_activity_list_none_filtered'] = 'No matching items found.';
$string['manage_activity_list_sort_creation_date'] = 'Creation date';
$string['manage_activity_list_sort_name'] = 'Name';
$string['manage_participation'] = 'Manage participation';
$string['manage_participation_heading'] = 'Manage participation: “{$a}”';
$string['manage_participation_no_activities'] = 'No activities to display. When a user whose activity participation you have permission to manage has an instance generated for them, that activity will be listed here.';
$string['manage_participation_participant_instances_number_shown'] = '{$a} - Participant instances';
$string['manage_participation_participant_sections_number_shown'] = '{$a} - Participant sections';
$string['manage_participation_select_activity'] = 'Select activity';
$string['manage_participation_subject_instances_number_shown'] = '{$a} - Subject instances';
$string['manage_section_content_page_title'] = 'Content elements: {$a}';
$string['manual_participant_add_confirmation_message'] = '{$a} additional participant instances will be generated for this subject instance. This will not affect other participants in the activity, but may affect the overall progress and availability status of the subject instance (e.g. reopening if closed, and/or marking a "complete" instance as "in progress").';
$string['manual_participant_add_confirmation_message_singular'] = '1 additional participant instance will be generated for this subject instance. This will not affect other participants in the activity, but may affect the overall progress and availability status of the subject instance (e.g. reopening if closed, and/or marking a "complete" instance as "in progress").';
$string['manual_participant_add_confirmation_title'] = 'Confirm create participant instances';
$string['manual_participant_add_no_relationships'] = 'There are no relationships available for adding additional participants to this subject instance.';
$string['manual_participant_add_require_at_least_one'] = 'You must select at least one user in any of the relationships to create additional participant instances.';
$string['manual_participant_selector_role_description'] = 'Participants for each relationship below must be manually chosen by the selected role.';
$string['manual_participant_selector_role_heading'] = 'Selection of participants';
$string['menu_title_activity_management'] = 'Activity management';
$string['menu_title_activity_response_data'] = "Performance activity response data";
$string['menu_title_my_activities'] = 'Activities';
$string['modal_activate_message'] = 'Activation will make this activity live. Subjects will be assigned, and instances generated for them according to the schedule set on the activity. Once activated, changes can still be made to assignments, but content cannot be edited.';
$string['modal_activate_message_question'] = 'Are you sure you would like to activate this activity?';
$string['modal_activate_message_users'] = '<strong>{$a}</strong> users will be assigned on activation.';
$string['modal_activate_title'] = 'Confirm activity activation';
$string['modal_can_not_activate_message'] = 'Activation of this draft activity will only be possible once all of the following criteria are met:';
$string['modal_can_not_activate_title'] = 'Activity cannot be activated';
$string['modal_can_not_delete_activity_title'] = 'Cannot delete activity';
$string['modal_can_not_delete_element_message'] = 'This question cannot be deleted, because it is being referenced by other elements:';
$string['modal_can_not_delete_element_title'] = 'Cannot delete question element';
$string['modal_can_not_delete_relationship_title'] = 'Cannot remove participant(s)';
$string['modal_can_not_delete_section_message'] = 'This section cannot be deleted, because it contains questions that are being referenced by other elements:';
$string['modal_can_not_delete_section_title'] = 'Cannot delete section';
$string['modal_delete_confirmation_line'] = 'Are you sure you would like to delete this activity?';
$string['modal_delete_draft_message'] = 'This will permanently delete all content created for this activity. It will not affect assigned users, as assignments are only created on activity activation.';
$string['modal_delete_draft_title'] = 'Confirm draft activity deletion';
$string['modal_delete_message'] = 'This will permanently delete all content created for this activity, and all associated user records. This may affect aggregated data based on these records, and impact scheduling rules in other activities based on participation in this one.';
$string['modal_delete_message_data_recovery_warning'] = 'Deleted data cannot be recovered.';
$string['modal_delete_title'] = 'Confirm activity deletion';
$string['modal_element_delete_message'] = 'This cannot be undone.';
$string['modal_element_delete_title'] = 'Confirm delete element';
$string['modal_element_move_from'] = 'Move from';
$string['modal_element_move_message'] = 'It will be added as the final element in the section it moves to.';
$string['modal_element_move_title'] = 'Move element to another section';
$string['modal_element_move_to'] = 'Move to';
$string['modal_notification_preview'] = 'Notification preview';
$string['modal_participant_instance_delete_title'] = 'Delete participant instance';
$string['modal_participant_instance_delete_message'] = 'This will permanently delete the participant instance and all associated records. This may affect aggregated data based on these records.';
$string['modal_participant_instance_delete_confirmation_line'] = 'Are you sure you would like to delete this participant instance?';
$string['modal_section_delete_message'] = 'This will also delete all content elements contained in this section';
$string['modal_section_delete_title'] = 'Confirm section deletion';
$string['modal_subject_instance_delete_confirmation_line'] = 'Are you sure you would like to delete this subject instance?';
$string['modal_subject_instance_delete_message_line1'] = 'This will delete the subject instance and all associated participant records from this activity.';
$string['modal_subject_instance_delete_message_line2'] = 'New instances may continue to be created for this subject. If you wish to permanently remove this subject from this activity, go to the "Assignments" tab and unassign this subject first before deleting this subject instance.';
$string['modal_subject_instance_delete_title'] = 'Delete subject instance';
$string['modal_warning_delete_activity_message'] = 'This activity contains questions that are being referenced by other elements:';
$string['modulename'] = 'Performance activity';
$string['modulenameplural'] = 'Performance activities';
$string['move'] = 'Move';
$string['move_element'] = 'Move {$a}';
$string['move_to_other_section'] = 'Move to another section';
$string['multiple_sections_confirmation_title'] = 'Confirm format change';
$string['modal_confirm'] = 'Confirm';
$string['multiple_sections_disabled_confirmation_text'] = 'All sections\' content will be merged and section headings removed. Participant settings will be removed. This cannot be undone.';
$string['multiple_sections'] = 'Multiple sections';
$string['multiple_sections_enabled_confirmation_text'] = 'All existing content will be grouped into the first section, along with the existing participant settings';
$string['multiple_sections_label_help'] = 'Having multiple sections provides more flexibility and control over the participants’ experience of an activity, as each section has its own participant settings. Participants’ progress per section is tracked, and the section headings will appear to participants as navigation within the activity. Switching between formats is possible, but any already created section headings and participant settings will be lost if multiple sections is subsequently disabled (the content, however, will be merged).';
$string['need_to_add_participant'] = '(Add at least one responding participant)';
$string['next_section'] = 'Next section';
$string['no_items_selected'] = 'No items have been selected.';
$string['no_participants'] = 'You need to add participants to this activity section,
before you can choose which participant can select review items.';
$string['no_participants_added'] = 'No participants added';
$string['no_recipients'] = 'No recipients. Go to Content tab: Responding participants, to add recipients.';
$string['no_response_submitted'] = 'No response submitted';
$string['notification_active'] = 'Active';
$string['notification_activity_completion_participant_body'] = 'Hi [recipient:full_name],

The following activity has been completed by all participants: 

[perform_activity:name] [perform_activity:type]: [subject_user:full_name] 

Thank you for your participation. 

You can review the completed activity through this link: [subject_instance:activity_name_link]';
$string['notification_activity_completion_participant_subject'] = 'Completed activity - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_activity_completion_subject_user_body'] = 'Hi [recipient:full_name],

Your [perform_activity:name] [perform_activity:type] has been completed by all participants.

You can review the completed activity through this link: [subject_instance:activity_name_link]';
$string['notification_activity_completion_subject_user_subject'] = 'Completed activity - [perform_activity:name] [perform_activity:type]';
$string['notification_activity_completion_title'] = 'Completion of subject instance';
$string['notification_activity_due_participant_body'] = 'Hi [recipient:full_name],

The following activity is due to be completed today : 

[perform_activity:name] [perform_activity:type] 

You were selected to participate in this activity as [subject:full_name]’s [participant_instance:relationship]. 

Please ensure you complete it by the end of the day. 

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_activity_due_participant_subject'] = 'Due today - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_activity_due_subject_user_body'] = 'Hi [recipient:full_name],

Your [perform_activity:name] [perform_activity:type] is due to be completed today.

Please ensure you complete it by the end of the day.

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_activity_due_subject_user_subject'] = 'Due today - [perform_activity:name] [perform_activity:type]';
$string['notification_activity_reopened_participant_body'] = 'Hi [recipient:full_name],

The following activity has been reopened: 

[perform_activity:name] [perform_activity:type]: [subject_user:full_name] 

As [subject_user:full_name]’s [participant_instance:relationship], it requires further action from you to complete it. 

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_activity_reopened_participant_subject'] = 'Reopened activity - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_activity_reopened_subject_user_body'] = 'Hi [recipient:full_name],

Your [perform_activity:name] [perform_activity:type] has been reopened and requires further action from you to complete it.

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_activity_reopened_subject_user_subject'] = 'Reopened activity - [perform_activity:name] [perform_activity:type]';
$string['notification_activity_reopened_title'] = 'Reopened activity';
$string['notification_broker_completion'] = 'Completion of subject instance';
$string['notification_broker_due_date'] = 'On due date reminder';
$string['notification_broker_due_date_reminder'] = 'Due date approaching reminder';
$string['notification_broker_instance_created'] = 'Participant instance creation';
$string['notification_broker_instance_created_reminder'] = 'Participant instance creation reminder';
$string['notification_broker_overdue_reminder'] = 'Overdue reminder';
$string['notification_broker_participant_selection'] = 'Participant selection';
$string['notification_broker_reopened'] = 'Reopened activity';
$string['notification_due_date_reminder_participant_body'] = 'Hi [recipient:full_name],

The following activity is due to be completed in [participant_instance:days_remaining] days: [perform_activity:name] [perform_activity:type].

You were selected to participate in this activity as [subject_user:full_name]’s [participant_instance:relationship].

Please ensure you complete it by [subject_instance:duedate].

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_due_date_reminder_participant_subject'] = 'Reminder - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_due_date_reminder_subject_user_body'] = 'Hi [recipient:full_name],

[perform_activity:name] [perform_activity:type] is due to be completed in [subject_instance:days_remaining] days.

Please ensure you complete it by [subject_instance:duedate].

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_due_date_reminder_subject_user_subject'] = 'Reminder - [perform_activity:name] [perform_activity:type]';
$string['notification_instance_created_external_user_body'] = 'Hi [external_recipient:name], 

As [subject_user:full_name]’s [participant_instance:relationship], you have been selected to participate in the following activity: 

[perform_activity:name] [perform_activity:type] 

[participant_instance:conditional_due_date]

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_instance_created_external_user_subject'] = '[perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_instance_created_participant_body'] = 'Hi [recipient:full_name], 

As [subject_user:full_name]’s [participant_instance:relationship], you have been selected to participate in the following activity: 

[perform_activity:name] [perform_activity:type] 

[subject_instance:conditional_duedate]

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_instance_created_participant_subject'] = '[perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_instance_created_reminder_for_external_user_body'] = 'Hi [external_recipient:name],

[participant_instance:days_active] days ago you were selected to participate in the following activity as [subject_user:full_name]’s [participant_instance:relationship]: 

[perform_activity:name] [perform_activity:type] 

[participant_instance:conditional_due_date]

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_instance_created_reminder_for_external_user_subject'] = 'Reminder - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_instance_created_reminder_participant_body'] = 'Hi [recipient:full_name], 

[participant_instance:days_active] days ago you were selected to participate in the following activity as [subject_user:full_name]’s [participant_instance:relationship]: 

[perform_activity:name] [perform_activity:type] 

[subject_instance:conditional_duedate]

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_instance_created_reminder_participant_subject'] = 'Reminder - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_instance_created_reminder_subject_user_body'] = 'Hi [recipient:full_name], 

[subject_instance:days_active] days ago you were sent your [perform_activity:name] [perform_activity:type] to complete.

[subject_instance:conditional_duedate]

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_instance_created_reminder_subject_user_subject'] = 'Reminder - [perform_activity:name] [perform_activity:type]';
$string['notification_instance_created_subject_user_body'] = 'Hi [recipient:full_name], 

Your [perform_activity:name] [perform_activity:type] is ready for you to complete.

[subject_instance:conditional_duedate]

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_instance_created_subject_user_subject'] = 'Your [perform_activity:name] [perform_activity:type]';
$string['notification_instance_created_title'] = 'Participant instance created';
$string['notification_overdue_subject_user_body'] = 'Hi [recipient:full_name], 

Your [perform_activity:name] [perform_activity:type] was due to be completed on [subject_instance:duedate] and is now overdue. 

Please ensure you complete it as soon as possible. 

You can access the activity through this link: [subject_instance:activity_name_link]';
$string['notification_overdue_subject_user_subject'] = 'Overdue - [perform_activity:name] [perform_activity:type]';
$string['notification_overdue_participant_body'] = 'Hi [recipient:full_name], 

The following activity was due to be completed on [subject_instance:duedate] and is now overdue: 

[perform_activity:name] [perform_activity:type] 

You were selected to participate in this activity as [subject_user:full_name]’s [participant_instance:relationship]. 

Please ensure you complete it as soon as possible. 

You can access the activity through this link: [participant_instance:activity_name_link]';
$string['notification_overdue_participant_subject'] = 'Overdue - [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_participant_due_date_title'] = 'Participant due date reminder';
$string['notification_participant_role_appraiser'] = 'Appraiser';
$string['notification_participant_role_direct_report'] = 'Direct report';
$string['notification_participant_role_external_respondent'] = 'External respondent';
$string['notification_participant_role_manager'] = 'Manager';
$string['notification_participant_role_managers_manager'] = 'Manager\'s manager' ;
$string['notification_participant_role_mentor'] = 'Mentor';
$string['notification_participant_role_peer'] = 'Peer';
$string['notification_participant_role_reviewer'] = 'Reviewer';
$string['notification_participant_role_subject'] = 'Subject';
$string['notification_participant_submission_resolvers_group_title'] = 'Performance activities: Submission of participant instance';
$string['notification_participant_submission_by_manager_title'] = 'Submission of participant instance by manager';
$string['notification_participant_submission_by_subject_title'] = 'Submission of participant instance by subject';
// CN: participant to recipient
$string['notification_participant_submission_participant_for_recipient_body'] = 'Hi [recipient:full_name], 

[subject_user:full_name]’s [participant_instance:relationship] [participant_user:full_name] has submitted their responses to [perform_activity:name] [perform_activity:type].

You can review the activity through this link: [participant_instance:activity_name_link]';
$string['notification_participant_submission_participant_for_recipient_subject'] = '[subject_user:full_name]’s [participant_instance:relationship] has submitted their responses to [perform_activity:name] [perform_activity:type]';
$string['notification_participant_submitted_instance_by'] = 'Submitted instance by';
$string['notification_placeholder_activity_name'] = 'Activity title';
$string['notification_placeholder_activity_type'] = 'Type';
$string['notification_placeholder_activity_name_linked'] = 'Activity title (with link)';
$string['notification_placeholder_external_participant_name'] = 'Name';
$string['notification_placeholder_external_participant_email'] = 'Email';
$string['notification_placeholder_group_external_participant'] = 'External participant {$a}';
$string['notification_placeholder_group_participant_instance'] = 'Participant instance {$a}';
$string['notification_placeholder_group_participant_user'] = 'Participant user {$a}';
$string['notification_placeholder_group_subject_instance'] = 'Subject instance {$a}';
$string['notification_placeholder_group_perform_activity'] = 'Performance activity {$a}';
$string['notification_placeholder_participant_fullname'] = 'Participant full name';
$string['notification_placeholder_participant_relationship'] = 'Relationship participation';
$string['notification_placeholder_participant_selection_linked'] = 'Participant selection link';
$string['notification_placeholder_subject_activity_url'] = 'Subject activity url';
$string['notification_placeholder_subject_conditional_duedate'] = 'Subject conditional due date';
$string['notification_placeholder_subject_created_at'] = 'Subject created at';
$string['notification_placeholder_subject_days_active'] = 'Subject days active';
$string['notification_placeholder_subject_days_overdue'] = 'Subject days overdue';
$string['notification_placeholder_subject_days_remaining'] = 'Subject days remaining';
$string['notification_placeholder_subject_duedate'] = 'Subject due date';
$string['notification_placeholder_subject_fullname'] = 'Subject full name';
$string['notification_placeholder_subject_activity_name'] = 'Subject activity name';
$string['notification_placeholder_subject_activity_name_linked'] = 'Subject activity name link';
$string['notification_placeholder_subject_activity_type'] = 'Subject activity type';
$string['notification_placeholder_subject_participant_linked'] = 'Subject participant link';
$string['notification_placeholder_subject_participant_relationship'] = 'Subject participant relationship';
$string['notification_placeholder_subject_participant_url'] = 'Subject participant url';
$string['notification_placeholder_subject_recipient_relationship'] = 'Subjects relation to recipient';
$string['notification_recipient_participant'] = 'Participant';
$string['notification_recipient_participant_selector_appraiser'] = 'Appraiser';
$string['notification_recipient_participant_selector_direct_report'] = 'Direct report';
$string['notification_recipient_participant_selector_manager'] = 'Manager';
$string['notification_recipient_participant_selector_managers_manager'] = 'Manager\'s manager';
$string['notification_recipient_participant_selector_subject'] = 'Subject';
$string['notification_recipient_perform_external'] = 'External respondent';
$string['notification_recipient_perform_mentor'] = 'Mentor';
$string['notification_recipient_perform_peer'] = 'Peer';
$string['notification_recipient_perform_reviewer'] = 'Reviewer';
$string['notification_resolver_participant_completion_title'] = 'Completion of subject instance';
$string['notification_resolver_participant_due_date_title'] = 'Participant due date reminder';
$string['notification_resolver_participant_instance_created_audit'] = 'Participant instance created for {$a->user} : {$a->activity}';
$string['notification_resolver_participant_instance_created_title'] = 'Participant instance created';
$string['notification_resolver_participant_reopened_activity_title'] = 'Reopened activity';
$string['notification_resolver_participant_selection_title'] = 'Participant selection';
$string['notification_select_participant_participant_body'] = 'Hi [recipient:full_name], 

As [subject_user:full_name]’s [subject_instance:recipient_relationship], you need to select who should participate in the following activity: 

[perform_activity:name] [perform_activity:type] 

Please complete this as soon as possible, to give participants time to provide their input. 

[subject_instance:conditional_duedate]

You can select participants through this link: [subject_instance:participant_selection_link]';
$string['notification_select_participant_participant_subject'] = 'Select participants for [perform_activity:name] [perform_activity:type]: [subject_user:full_name]';
$string['notification_select_participant_subject_user_body'] = 'Hi [recipient:full_name], 

You need to select who you want to participate in your [perform_activity:name] [perform_activity:type]. 

Please complete this as soon as possible, to give participants time to provide their input. 

[subject_instance:conditional_duedate]

You can select participants through this link: [subject_instance:participant_selection_link]';
$string['notification_select_participant_subject_user_subject'] = 'Select participants for [perform_activity:name] [perform_activity:type]';
$string['notification_select_participant_title'] = 'Participant selection';
$string['notification_subject_status'] = 'Recipient role';
$string['notification_trigger_instance_creation'] = 'instance creation';
$string['notification_trigger_duedate'] = 'due date';
$string['overdue'] = 'Overdue';
$string['participant_instance_availability_closed'] = 'Closed';
$string['participant_instance_availability_not_applicable'] = 'Not applicable';
$string['participant_instance_availability_open'] = 'Open';
$string['participant_instance_card_participant_full_name'] = 'Participant full name';
$string['participant_instance_card_relationship'] = 'Relationship';
$string['participant_instance_card_title'] = 'Showing results for 1 participant instance only';
$string['participant_instance_progress'] = 'Progress';
$string['participant_instance_status_complete'] = 'Complete';
$string['participant_instance_status_in_progress'] = 'In progress';
$string['participant_instance_status_not_started'] = 'Not started';
$string['participant_instance_status_not_submitted'] = 'Not submitted';
$string['participant_instance_status_progress_not_applicable'] = 'n/a (view only)';
$string['participant_instance_status_progress_view_only'] = 'View-only';
$string['participant_instances'] = 'Participant instances';
$string['participant_instances_close_confirmation']  = 'Participant instance (and any sections within) closed';
$string['participant_instances_close_title'] = 'Close participant instance';
$string['participant_instances_close_message_line1'] = 'This will prevent any further submission of responses from this participant, regardless of their progress.';
$string['participant_instances_close_message_line2'] = 'If this is a multi-section activity, this applies to all sections (but those already closed will not be affected). If you want to close only some sections for the participant, click on the linked sections to navigate to the “participant sections” tab and select the relevant ones.';
$string['participant_instances_delete_confirmation'] = 'The participant instance and all associated records have been successfully deleted.';
$string['participant_instances_manually_added_toast'] = '{$a} participant instances created';
$string['participant_instances_manually_added_toast_singular'] = '1 participant instance created';
$string['participant_instances_reopen_confirmation'] = 'Participant instance (and any sections within) reopened';
$string['participant_instances_reopen_title'] = 'Reopen participant instance';
$string['participant_instances_reopen_message_line1'] = 'If complete, the instance will be returned to an “in progress” state, and any submitted responses saved as a new draft for the participant. Responses will be unavailable for viewing in this state (and will become visible again when the conditions for visibility are fulfilled).';
$string['participant_instances_reopen_message_line2'] = 'If multiple sections are enabled on the activity, this will open all sections for the participant. If you want to open only some sections, click on the linked sections to navigate to the “participant sections” tab and select the relevant ones.';
$string['participant_instances_title'] = '{$a->activity_name} : {$a->fullname}';
$string['participant_relationship'] = 'Participant relationship';
$string['notification_participant_instance_completion_by_direct_report_title'] = 'Participant instance completion by direct report';
$string['notification_participant_instance_completion_by_external_respondent_title'] = 'Participant instance completion by external respondent';
$string['notification_participant_instance_completion_by_managers_manager_title'] = 'Participant instance completion by manager\'s manager';
$string['notification_participant_instance_completion_by_mentor_title'] = 'Participant instance completion by mentor';
$string['notification_participant_instance_completion_by_peer_title'] = 'Participant instance completion by peer';
$string['notification_participant_instance_completion_by_reviewer_title'] = 'Participant instance completion by reviewer';
$string['notification_participant_instance_completion_by_participant_body'] = 'Hi [recipient:full_name], 

[subject_user:full_name]’s [participant_instance:relationship] [participant_user:full_name] has submitted their responses to [perform_activity:name] [perform_activity:type].

You can review the activity through this link: [participant_instance:activity_name_link]';
$string['notification_participant_instance_completion_by_participant_subject'] = '[subject_user:full_name]’s [participant_instance:relationship] has submitted their responses to [perform_activity:name] [perform_activity:type]';
$string['notification_participant_instance_completion_by_participant_for_subject_body'] = 'Hi [recipient:full_name], 

Your [participant_instance:relationship] [participant_user:full_name] has submitted their responses to your [perform_activity:name] [perform_activity:type].

You can review the activity through this link: [participant_instance:activity_name_link]';
$string['notification_participant_instance_completion_by_participant_for_subject_subject'] = 'Your [participant_instance:relationship] has submitted their responses to your [perform_activity:name] [perform_activity:type]';
$string['notification_participant_instance_completion_by_subject_for_participant_body'] = 'Hi [recipient:full_name],

[participant_user:full_name] has submitted their responses to their [perform_activity:name] [perform_activity:type].

You can review the activity through this link: [participant_instance:activity_name_link]';
$string['notification_participant_instance_completion_by_subject_for_participant_subject'] = '[participant_user:full_name] has submitted their responses to their [perform_activity:name] [perform_activity:type]';
$string['notification_participant_instance_completion_by_manager_title'] = 'Participant instance completion by manager';
$string['notification_participant_instance_completion_by_subject_body'] = 'Hi [recipient:full_name],

 

[participant_user:full_name] has submitted their responses to their [perform_activity:name] [perform_activity:type].

You can review the activity through this link: [participant_instance:activity_name_link]';
$string['notification_participant_instance_completion_by_subject_subject'] = '[participant_user:full_name] has submitted their responses to their [perform_activity:name] [perform_activity:type]';
$string['notification_participant_instance_completion_by_subject_title'] = 'Participant instance completion by subject';
$string['notification_participant_instance_completion_title'] = 'Participant instance completion';

$string['notification_participant_relationship_recipient_appraiser'] = 'Participant: Appraiser';
$string['notification_participant_relationship_recipient_direct_report'] = 'Participant: Direct report';
$string['notification_participant_relationship_recipient_external'] = 'Participant: External respondent';
$string['notification_participant_relationship_recipient_manager'] = 'Participant: Manager';
$string['notification_participant_relationship_recipient_managers_manager'] = 'Participant: Manager\'s manager';
$string['notification_participant_relationship_recipient_mentor'] = 'Participant: Mentor';
$string['notification_participant_relationship_recipient_peer'] = 'Participant: Peer';
$string['notification_participant_relationship_recipient_reviewer'] = 'Participant: Reviewer';
$string['notification_participant_relationship_recipient_subject'] = 'Participant: Subject';
$string['participant_section_availability_closed'] = 'Closed';
$string['participant_section_availability_not_applicable'] = 'Not applicable';
$string['participant_section_availability_open'] = 'Open';
$string['participant_section_availability_status_availability_not_applicable'] = 'View-only';
$string['participant_section_button_draft'] = 'Save as draft';
$string['participant_section_close_confirmation']  = 'Participant section closed';
$string['participant_section_close_title'] = 'Close participant section';
$string['participant_section_close_message'] = 'This will prevent any further submission of responses from the participant on this section, regardless of their progress.';
$string['participant_section_draft_saved'] = 'Draft saved';
$string['participant_section_reopen_confirmation'] = 'Participant section reopened';
$string['participant_section_reopen_title'] = 'Reopen participant section';
$string['participant_section_reopen_message'] = 'If complete, the section will be returned to an “in progress” state, and any submitted responses saved as a new draft for the participant. Responses will be unavailable for viewing in this state (and will become visible again when the conditions for visibility are fulfilled).';
$string['participant_section_status_complete'] = 'Complete';
$string['participant_section_status_in_progress'] = 'In progress';
$string['participant_section_status_not_started'] = 'Not started';
$string['participant_section_status_not_submitted'] = 'Not submitted';
$string['participant_section_status_progress_not_applicable'] = 'Not applicable';
$string['participant_sections'] = 'Participant sections';
$string['participant_user'] = 'Participant user';
$string['participation_reporting'] = 'Participation reporting';
$string['participation_reporting_with_activity'] = 'Participation reporting - {$a}';
$string['perform:audit_notifications'] = 'Audit performance activity notifications';
$string['perform:create_activity'] = 'Create performance activities';
$string['perform:manage_activity'] = 'Manage performance activities';
$string['perform:manage_all_participation'] = 'Manage all participation';
$string['perform:manage_subject_user_participation'] = 'Manage participation';
$string['perform:report_on_all_subjects_responses'] = 'Report on all performance activity subjects\' responses';
$string['perform:report_on_subject_responses'] = 'Report on a performance activity subject\'s responses';
$string['perform:view_manage_activities'] = 'Access the performance activities management interface';
$string['perform:view_participation_reporting'] = 'Access the participation reporting interface';
$string['perform_admin_sync_participant_instance_auto_assign'] = 'Auto-assign new participants to existing activities on role change';
$string['perform_admin_sync_participant_instance_auto_assign_description'] = 'When enabled, this will create a new participant instance for existing open activities when a new user enters a relationship for that activity. This only applies to participants with the relationship of manager, manager\'s manager, appraiser, and direct report.';
$string['perform_admin_sync_participant_instance_auto_close'] = 'Auto-close all participant instances for removed participants';
$string['perform_admin_sync_participant_instance_auto_close_description'] = 'When enabled, this will close existing participant instances for open activities when a user leaves a relationship for that activity.';
$string['perform_admin_sync_participant_instance_override'] = 'Override global settings';
$string['perform_admin_sync_participant_instance_override_description'] = 'When enabled, this allows you to override the global participation settings';
$string['perform_admin_sync_participant_instance_title'] = 'Manage relationship participation';
$string['perform_close_suspended_user_instances'] = 'Close suspended users\' performance activities';
$string['perform_close_suspended_user_instances_description'] = 'When enabled this closes all subject and participant performance activity instances of users that are suspended.';
$string['perform_hide_incomplete_responses_closed_instances'] = 'Hide incomplete responses for closed instances';
$string['perform_hide_incomplete_responses_closed_instances_description'] = 'Hide incomplete responses for participants who have not provided any response to any question on a closed instance.
Participants can still see their own participant instance regardless of whether they have added responses.';
$string['perform_hide_suspended_users'] = 'Close and hide suspended users\' performance activities';
$string['perform_hide_suspended_users_description'] = 'When enabled this closes and hides all subject and participant performance activity instances of users that are suspended.';
$string['perform_sync_participant_instance_creation'] = 'Enable role change participant instance creation';
$string['perform_sync_participant_instance_creation_description'] = 'When enabled, this will create a new participant instance for existing open activities when a new user enters a relationship for that activity.';
$string['perform_sync_participant_instance_closure'] = 'Enable role change participant instance closure';
$string['perform_sync_participant_instance_closure_description'] = 'When enabled, this will close participant instances for open activities when a user leaves a relationship for that activity.';
$string['performance_activity_response_data_heading'] = 'Performance activity response data';
$string['performance_data_for'] = 'Performance data for {$a->target}: {$a->count} record shown';
$string['performance_data_for_plural'] = 'Performance data for {$a->target}: {$a->count} records shown';
$string['performance_report_load_records'] = 'Load records';
$string['performance_report_load_records_divider'] = 'OR';
$string['performance_report_select_activity'] = 'Select activity';
$string['performance_report_select_activity_placeholder'] = 'Select...';
$string['performance_report_select_reporting_ids'] = 'Select reporting IDs';
$string['pluginadministration'] = 'Performance activity administration';
$string['preview'] = 'Preview';
$string['preview_question'] = 'Preview question';
$string['previous_section'] = 'Previous section';
$string['print'] = 'Print';
$string['print_activity'] = 'Print activity';
$string['printed_on_date'] = 'Printed on {$a}';
$string['progress'] = 'Progress';
$string['question_title'] = 'Question text';
$string['question_response_required_yes'] = 'Yes';
$string['question_response_required_no'] = 'No';
$string['recipients'] = 'Recipients';
$string['relationship'] = 'Relationship';
$string['relation_to_subject_self'] = 'Self';
$string['relation_to_subject_self_internal'] = 'You are participating in an activity about yourself';
$string['relationship_external'] = 'External respondent';
$string['relationship_external_plural'] = 'External respondents';
$string['relationship_id'] = 'Relationship ID';
$string['relationship_name'] = 'Relationship name';
$string['relationship_sort_order'] = 'Relationship sort order';
$string['relationship_type'] = 'Relationship type';
$string['relationship_name_perform_external'] = 'External respondent';
$string['relationship_name_perform_mentor'] = 'Mentor';
$string['relationship_name_perform_peer'] = 'Peer';
$string['relationship_name_perform_reviewer'] = 'Reviewer';
$string['relationship_name_plural_perform_external'] = 'External respondents';
$string['relationship_name_plural_perform_mentor'] = 'Mentors';
$string['relationship_name_plural_perform_peer'] = 'Peers';
$string['relationship_name_plural_perform_reviewer'] = 'Reviewers';
$string['relationship_not_found'] = 'Relationship missing';
$string['relative_date_selector_error_value'] = 'Please enter a valid whole number';
$string['relative_date_selector_error_range'] = 'Range end date cannot be before range start date';
$string['relative_date_selector_reference_date'] = 'Reference date';
$string['remove_group_assignment'] = 'Remove {$a->name} from {$a->type}';
$string['report_activity_warning_message'] = 'This report page can only show details for a single activity at a time - to select which activity\'s report to view, go to <a href="{$a->url}">Manage performance activities</a>, and click on the relevant activity\'s reporting icon.';
$string['report_participant_warning_message'] = 'This report page can only show details for a single subject instance at a time - to select which subject instance\'s report to view, go to <a href="{$a->url}">Manage performance activities</a>, click on the reporting icon of the activity to which the subject instance belongs. From the report\'s list of subject instances, navigate to the relevant one\'s participant instance report by clicking on its participant count.';
$string['reporting_id_help_text'] = 'Create unique element identifier for reporting purposes.';
$string['reporting_identifier'] = 'Reporting ID';
$string['response_other']  = 'Others’ responses';
$string['responses']  = 'Responses';
$string['required_fields'] = 'Required fields';
$string['response_data_report_link_text'] = 'Performance activity response data';
$string['response_data_info_banner'] = 'All participants\' responses are included in these reports. If you are a participant in any of these activities, this may include responses you would not ordinarily be able to see.';
$string['response_visibility_label'] = 'Your responses (once submitted) are visible to: {$a}. They are also visible to those granted access to performance reports.';
$string['response_visibility_label_anonymous'] = 'Your <strong>anonymised</strong> responses (once submitted) are visible to: {$a}. They are also visible to those granted access to performance reports.';
$string['response_visibility_the_employee'] = 'the <strong>Employee</strong>';
$string['response_visibility_the_employees_relationship'] = 'the employee\'s <strong>{$a}</strong>';
$string['response_visibility_to_only_granted_access'] = 'Your responses are visible only to those granted access to performance reports, but not other participants.';
$string['response_visibility_your_relationship'] = 'your <strong>{$a}</strong>';
$string['response_visibility_your_relationship_separator'] = ', ';
$string['response_visibility_view_only_lozenge'] = 'View-only';
$string['responses_by_relationship'] = 'Responses by relationship';
$string['save_changes'] = 'Save changes';
$string['save'] = 'Save';
$string['schedule_activity_instance_creation_period'] = 'Activity instance creation period';
$string['schedule_after'] = 'after date:';
$string['schedule_after_a11y'] = '{$a->value} {$a->range} after date';
$string['schedule_before'] = 'before date:';
$string['schedule_before_a11y'] = '{$a->value} {$a->range} before date';
$string['schedule_confirm_title'] = 'Confirm changes to instance creation';
$string['schedule_confirm_title_active'] = 'Changes will be applied and may affect whether assigned users receive instances in the future. Existing instances will not be affected.';
$string['schedule_confirm_title_draft'] = 'Instances are not created until after an activity is activated, so no users will be affected by the changes.';
$string['schedule_creation_date_type'] = 'Date type';
$string['schedule_creation_range'] = 'Creation range';
$string['schedule_creation_range_and_date_type'] = 'Creation range and date type';
$string['schedule_creation_frequency'] = 'Frequency';
$string['schedule_creation_frequency_once_off'] = 'Once';
$string['schedule_creation_frequency_repeating'] = 'Repeating';
$string['schedule_date_range_until'] = 'until';
$string['schedule_date_to'] = 'to';
$string['schedule_dynamic_activity_required'] = 'Activity is required';
$string['schedule_dynamic_another_activity_close_date'] = 'Close date of another activity instance';
$string['schedule_dynamic_another_activity_close_or_completion_date'] = 'Close or completion date of another activity instance (whichever is sooner)';
$string['schedule_dynamic_another_activity_completion_date'] = 'Completion date of another activity instance';
$string['schedule_dynamic_another_activity_select'] = 'Select activity';
$string['schedule_dynamic_another_activity_instance_creation_date'] = 'Creation date of another activity instance';
$string['schedule_dynamic_before_after_label'] = 'before or after reference date';
$string['schedule_dynamic_direction_after'] = 'after';
$string['schedule_dynamic_direction_before'] = 'before';
$string['schedule_dynamic_unit_days'] = 'days';
$string['schedule_dynamic_unit_label'] = 'units';
$string['schedule_dynamic_unit_weeks'] = 'weeks';
$string['schedule_is_fixed'] = 'Fixed';
$string['schedule_is_limited'] = 'Limited';
$string['schedule_is_open'] = 'Open-ended';
$string['schedule_is_relative'] = 'Relative';
$string['schedule_job_assignment_based_disable_error'] = 'This setting cannot be disabled while “{$a}” is in use as a reference date for scheduling. Please select a different reference date or enable this setting.';
$string['schedule_job_assignment_based_instances'] = "Job assignment-based instances";
$string['schedule_job_assignment_based_instances_disabled'] = "Disabled";
$string['schedule_job_assignment_based_instances_disabled_description'] = "One subject instance will be generated per user (participants will be drawn from all job assignments, if applicable). \"Job assignment start date\" cannot be used as a reference date for dynamic scheduling.";
$string['schedule_job_assignment_based_instances_enabled'] = "Enabled";
$string['schedule_job_assignment_based_instances_enabled_description'] = "Users will receive one subject instance per job assignment. Users without job assignments will not receive instances.";
$string['schedule_on_date'] = 'on date';
$string['schedule_repeating_date_error_value'] = 'Please enter a valid whole number';
$string['schedule_repeating_date_max_value'] = 'Number must be {$a} or less';
$string['schedule_repeating_date_min_value'] = 'Number must be 1 or more';
$string['schedule_repeating_disabled_heading'] = 'Frequency: Once';
$string['schedule_repeating_disabled_description'] = 'Users will receive a maximum of 1 instance each (or maximum of 1 per job, depending on job assignment setting)';
$string['schedule_repeating_enabled_heading'] = 'Frequency: Repeating';
$string['schedule_repeating_enabled_description'] = 'While users remain assigned and the creation range open, additional instances will be created for them:';
$string['schedule_repeating_every_time_after_type'] = 'time since {$a->type} of previous instance:';
$string['schedule_repeating_every_time_after_type_a11y'] = 'every {$a->value} {$a->range} after the {$a->type} of their previous instance';
$string['schedule_repeating_limit_label'] = 'Repeating limit';
$string['schedule_repeating_limit_maximum_of'] = 'maximum instances per assigned user:';
$string['schedule_repeating_limit_maximum_of_a11y'] = 'a maximum limit of {$a} instances per assigned user';
$string['schedule_repeating_limit_none'] = 'none (repeat until creation period ends)';
$string['schedule_repeating_limit_none_open_ended'] = 'none (repeat indefinitely)';
$string['schedule_repeating_minimum_time_since_creation'] = 'once minimum time since creation of previous instance has passed:';
$string['schedule_repeating_minimum_time_since_creation_a11y'] = 'every {$a->value} {$a->range} after the creation of their previous instance';
$string['schedule_repeating_trigger_type_closure'] = 'Closure of previous instance';
$string['schedule_repeating_trigger_type_closure_inline'] = 'closure';
$string['schedule_repeating_trigger_type_completion'] = 'Completion of previous instance';
$string['schedule_repeating_trigger_type_completion_inline'] = 'completion';
$string['schedule_repeating_trigger_type_completion_closure'] = 'Completion or closure of previous instance';
$string['schedule_repeating_trigger_type_completion_closure_inline'] = 'completion or closure (whichever is sooner)';
$string['schedule_repeating_trigger_type_creation'] = 'Creation of previous instance';
$string['schedule_repeating_trigger_type_creation_inline'] = 'creation';
$string['schedule_repeating_type_after_completion_label'] = 'after completion';
$string['schedule_use_anniversary_label'] = 'On assignment, if user\'s calculated start date is in the past, use next anniversary of reference date instead';
$string['schedule_multiple_job_assignments_help'] = 'Where a subject user has multiple job assignments, this setting controls whether a subject instance will contain participants drawn from only one job assignment (resulting in potentially multiple subject instances per user), or all. Where a single subject instance contains all job assignments, participants with the same relationship to the subject will be able to see each others’ responses.';
$string['schedule_multiple_job_assignments_single_subject_multiple_job'] = "Automatic: Single subject instance with participants from all job assignments";
$string['schedule_multiple_job_assignments_single_subject_per_job'] = "Automatic: One subject instance per job assignment";
$string['schedule_range_activity'] = 'Activity';
$string['schedule_range_date_description'] = 'Assigned users will have instances created for them during this period:';
$string['schedule_range_date_description_limited_relative'] = 'Assigned users will have instances created for them during a period defined relative to another date:';
$string['schedule_range_date_end'] = 'End';
$string['schedule_range_date_min_value'] = 'Number must be 1 or more';
$string['schedule_range_date_start'] = 'Start';
$string['schedule_range_heading_limited_dynamic'] = 'Limited range defined by relative dates';
$string['schedule_range_heading_limited_fixed'] = 'Limited range defined by fixed dates';
$string['schedule_range_heading_open_dynamic'] = 'Open-ended range defined by relative dates';
$string['schedule_range_heading_open_fixed'] = 'Open-ended range defined by fixed start date';
$string['schedule_range_use_anniversary'] = 'Use anniversary';
$string['schedule_save_changes'] = 'Update instance creation';
$string['section_action_delete'] = 'Delete';
$string['section_action_add_above'] = 'Add section above';
$string['section_action_add_below'] = 'Add section below';
$string['section_add_element'] = 'Add element';
$string['section_add_child_element'] = 'Add sub-element';
$string['section_delete_element'] = 'Delete';
$string['section_relationship_deletion_error'] = 'Cannot remove relationship ({$a})';
$string['section_dropdown_menu'] = 'Section dropdown menu';
$string['section_dropdown_other_elements'] = 'Other elements';
$string['section_dropdown_question_elements'] = 'Question elements';
$string['section_element_questions'] = 'Questions';
$string['section_element_response_required'] = 'Response required';
$string['section_element_response_optional'] = 'optional';
$string['section_element_sort_order'] = 'Sort order';
$string['section_element_tag_required'] = 'Required';
$string['section_element_tag_optional'] = 'Optional';
$string['section_element_summary_optional_questions'] = 'Optional questions';
$string['section_element_summary_other_content_elements'] = 'Other content elements';
$string['section_element_summary_required_questions'] = 'Required questions';
$string['section_participant_required'] = 'At least one responding participant must be added in order to edit content elements.';
$string['section_sort_order'] = 'Section sort order';
$string['section_title'] = 'Section title';
$string['sections_one'] = '1 section';
$string['sections'] = '{$a} sections';
$string['sections_header'] = 'Sections';
$string['selected_reporting_ids'] = 'selected reporting IDs';
$string['selected_relationship_not_in_section'] = 'Selected relationship is not a responding participant in this section';
$string['select_relationship_to_respond_as_explanation'] = 'You have multiple relationships to {$a}. Please select one to access the activity.';
$string['select_relationship_to_respond_as_option'] = '{$a->relationship_name} ({$a->progress_status})';
$string['select_relationship_to_respond_as_title'] = 'Select relationship to continue';
$string['setting_element'] = 'Element settings: {$a}';
$string['short_text'] = 'Text: Short response';
$string['showing_activities'] = 'Showing {$a->shown} of {$a->total} activities';
$string['subject_availability'] = 'Availability';
$string['subject_instance_status'] = 'Status of subject instance';
$string['showhistoricactivities'] = 'Show historic activities';
$string['subject_instance_availability_closed'] = 'Closed';
$string['subject_instance_availability_open'] = 'Open';
$string['subject_instance_availability_reopen'] = 'Reopen';
$string['subject_instance_card_instance_count'] = 'Instance count';
$string['subject_instance_card_job_assignment'] = 'Job assignment';
$string['subject_instance_card_title'] = 'Showing results for 1 subject instance only';
$string['subject_instance_closed_confirmation'] = 'Subject instance and all its participant instances closed';
$string['subject_instance_closed_message_line1'] = 'This will prevent any further submission of responses from all participants (across all sections, if multiple sections are enabled), regardless of their progress. Already closed instances and sections will not be affected.';
$string['subject_instance_closed_message_line2'] = 'If you want to close the activity for only some participants, click on the linked participants to navigate to the “participant instances” tab and select the relevant ones.';
$string['subject_instance_closed_title'] = 'Close subject instance';
$string['subject_instance_delete_confirmation'] = 'The subject instance and all associated records have been successfully deleted.';
$string['subject_instance_progress'] = 'Progress';
$string['subject_instance_progress_complete'] = 'Complete';
$string['subject_instance_progress_in_progress'] = 'In progress';
$string['subject_instance_progress_not_started'] = 'Not started';
$string['subject_instance_progress_not_submitted'] = 'Not submitted';
$string['subject_instance_reopen_confirmation'] = 'Subject instance and all its participant instances reopened';
$string['subject_instance_reopen_message_line1'] = 'This will reopen all instances (and sections, if multiple sections are enabled) for all participants.';
$string['subject_instance_reopen_message_line2'] = 'Completed items will be returned to an “in progress” state, and any submitted responses saved as a new draft for the participant. Responses will be unavailable for viewing in this state (and will become visible again when the conditions for visibility are fulfilled).';
$string['subject_instance_reopen_message_line3'] = 'If you want to reopen the activity for only some participants, click on the linked participants to navigate to the “participant instances” tab and select the relevant ones.';
$string['subject_instance_reopen_title']  = 'Reopen subject instance';
$string['subject_instance_status'] = 'Subject instance status';
$string['subject_instance_status_active'] = 'Active';
$string['subject_instance_status_pending'] = 'Pending';
$string['subject_instances'] = 'Subject instances';
$string['subject_progress'] = 'Progress';
$string['subject_user'] = 'Subject user';
$string['subject_users_number_shown'] = 'Subject users: {$a} records shown';
$string['sync_participant_instances_task'] = 'Synchronize participants for performance activities';
$string['sync_track_schedule_task'] = 'Synchronize assignment schedules for performance activities';
$string['system_activity_type:appraisal'] = 'Appraisal';
$string['system_activity_type:check-in'] = 'Check-in';
$string['system_activity_type:feedback'] = 'Feedback';
$string['teams_page_response_report_line'] = 'Their current and historical performance records are available for you to {$a}.';
$string['teams_page_response_report_link_text'] = 'view or export';

$string['template_completion_appraiser_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_appraiser_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_manager_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_managers_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_managers_manager_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_perform_mentor_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_perform_peer_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_perform_peer_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

The following activity has been completed by all participants:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

Thank you for your participation.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_perform_reviewer_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_completion_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} has been completed by all participants.

You can review the completed activity through this link: {$a->activity_link}';
$string['template_completion_subject_subject'] = 'Completed activity - {$a->activity_name} {$a->activity_type}';

$string['template_due_date_appraiser_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_appraiser_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_manager_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_managers_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_managers_manager_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';


$string['template_due_date_perform_external_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_perform_external_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_perform_mentor_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_perform_peer_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_perform_peer_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed today :

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_perform_reviewer_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} is due to be completed today.

Please ensure you complete it by the end of the day.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_subject_subject'] = 'Due today - {$a->activity_name} {$a->activity_type}';

$string['template_due_date_reminder_appraiser_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_appraiser_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_manager_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_managers_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_managers_manager_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';


$string['template_due_date_reminder_perform_external_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_perform_external_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_perform_mentor_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_perform_peer_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_perform_peer_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

The following activity is due to be completed in {$a->instance_days_remaining} days:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_perform_reviewer_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_due_date_reminder_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} is due to be completed in {$a->instance_days_remaining} days.

Please ensure you complete it by {$a->instance_duedate}.

You can access the activity through this link: {$a->activity_link}';
$string['template_due_date_reminder_subject_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}';

$string['template_instance_created_appraiser_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_appraiser_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_manager_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_manager_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_managers_manager_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_managers_manager_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_perform_external_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_perform_external_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_perform_mentor_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_perform_peer_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_perform_peer_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you have been selected to participate in the following activity:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_perform_reviewer_subject'] = '{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} is ready for you to complete.
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_subject_subject'] = 'Your {$a->activity_name} {$a->activity_type}';

$string['template_instance_created_reminder_appraiser_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_appraiser_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_manager_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_manager_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_managers_manager_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_managers_manager_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_perform_external_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_perform_external_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_perform_mentor_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_perform_peer_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_perform_peer_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were selected to participate in the following activity as {$a->subject_fullname}’s {$a->participant_relationship}:

{$a->activity_name} {$a->activity_type}
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_perform_reviewer_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_instance_created_reminder_subject_body'] = 'Hi {$a->recipient_fullname},

{$a->instance_days_active} days ago you were sent your {$a->activity_name} {$a->activity_type} to complete.
{$a->conditional_duedate}
You can access the activity through this link: {$a->activity_link}';
$string['template_instance_created_reminder_subject_subject'] = 'Reminder - {$a->activity_name} {$a->activity_type}';

$string['template_overdue_reminder_appraiser_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_appraiser_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_manager_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_managers_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_managers_manager_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_perform_external_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_perform_external_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_perform_mentor_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_perform_peer_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_perform_peer_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

The following activity was due to be completed on {$a->instance_duedate} and is now overdue:

{$a->activity_name} {$a->activity_type}

You were selected to participate in this activity as {$a->subject_fullname}’s {$a->participant_relationship}. Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_perform_reviewer_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_overdue_reminder_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} was due to be completed on {$a->instance_duedate} and is now overdue.

Please ensure you complete it as soon as possible.

You can access the activity through this link: {$a->activity_link}';
$string['template_overdue_reminder_subject_subject'] = 'Overdue - {$a->activity_name} {$a->activity_type}';

$string['template_participant_selection_appraiser_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you need to select who should participate in the following activity:

{$a->activity_name} {$a->activity_type}

Please complete this as soon as possible, to give participants time to provide their input.
{$a->conditional_duedate}
You can select participants through this link: {$a->participant_selection_link}';
$string['template_participant_selection_appraiser_subject'] = 'Select participants for {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_participant_selection_manager_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you need to select who should participate in the following activity:

{$a->activity_name} {$a->activity_type}

Please complete this as soon as possible, to give participants time to provide their input.
{$a->conditional_duedate}
You can select participants through this link: {$a->participant_selection_link}';
$string['template_participant_selection_manager_subject'] = 'Select participants for {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_participant_selection_managers_manager_body'] = 'Hi {$a->recipient_fullname},

As {$a->subject_fullname}’s {$a->participant_relationship}, you need to select who should participate in the following activity:

{$a->activity_name} {$a->activity_type}

Please complete this as soon as possible, to give participants time to provide their input.
{$a->conditional_duedate}
You can select participants through this link: {$a->participant_selection_link}';
$string['template_participant_selection_managers_manager_subject'] = 'Select participants for {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_participant_selection_subject_body'] = 'Hi {$a->recipient_fullname},

You need to select who you want to participate in your {$a->activity_name} {$a->activity_type}.

Please complete this as soon as possible, to give participants time to provide their input.
{$a->conditional_duedate}
You can select participants through this link: {$a->participant_selection_link}';
$string['template_participant_selection_subject_subject'] = 'Select participants for {$a->activity_name} {$a->activity_type}';
$string['template_reopened_appraiser_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_appraiser_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_manager_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_managers_manager_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_managers_manager_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_perform_external_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_perform_external_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_perform_mentor_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_perform_mentor_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_perform_peer_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_perform_peer_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_perform_reviewer_body'] = 'Hi {$a->recipient_fullname},

The following activity has been reopened:

{$a->activity_name} {$a->activity_type}: {$a->subject_fullname}

As {$a->subject_fullname}’s {$a->participant_relationship}, it requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_perform_reviewer_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}: {$a->subject_fullname}';

$string['template_reopened_subject_body'] = 'Hi {$a->recipient_fullname},

Your {$a->activity_name} {$a->activity_type} has been reopened and requires further action from you to complete it.

You can access the activity through this link: {$a->activity_link}';
$string['template_reopened_subject_subject'] = 'Reopened activity - {$a->activity_name} {$a->activity_type}';
$string['toast_error_create_activity'] = 'An error occurred while saving, and the activity could not be created.';
$string['toast_error_generic_update'] = 'An error occurred, and your latest changes have not been saved.';
$string['toast_error_save_response'] = 'An error occurred while saving, and the activity responses could not be updated.';
$string['toast_success_activity_activated'] = '"{$a}" was successfully activated.';
$string['toast_success_activity_cloned'] = '"{$a}" activity successfully cloned.';
$string['toast_success_activity_deleted'] = 'Activity and all associated user records successfully deleted.';
$string['toast_success_activity_update'] = 'Activity saved.';
$string['toast_success_delete_element'] = 'Element deleted.';
$string['toast_success_draft_activity_deleted'] = 'Draft activity successfully deleted.';
$string['toast_success_move_element'] = 'Element moved successfully';
$string['toast_success_participants_saved'] = 'The participants have been successfully saved.';
$string['toast_success_save_close_on_completion_response'] = 'Section submitted and closed.';
$string['toast_success_save_element'] = 'Element saved';
$string['toast_success_save_response'] = 'Section submitted';
$string['toast_success_save_schedule'] = 'Changes applied and activity has been updated.';
$string['toast_success_close_all_open_instances'] = 'A task has been scheduled to close all instances.';
$string['toggle_notification'] = '{$a} notification';
$string['track_description'] = 'Track description';
$string['trigger'] = 'Trigger';
$string['trigger_after'] = 'day(s) after {$a->name}';
$string['trigger_before'] = 'day(s) before {$a->name}';
$string['trigger_duplicates'] = 'Duplicate trigger. Delete or change number';
$string['trigger_events'] = 'Trigger events';
$string['trigger_out_of_range'] = 'Set trigger from 1 to 365 days';
$string['trigger_set'] = 'Set';
$string['trigger_type'] = 'Trigger type';
$string['unnamed_job_assignment'] = 'Unnamed job assignment (ID: {$a})';
$string['unsaved_changes_warning'] = 'Your unsaved changes will be lost.';
$string['untitled_section'] = 'Untitled section';
$string['user_email_unobscured_no_cap_checks'] = 'User\'s Email (ignoring user display setting and capability checks)';
$string['user_activities_activity_does_not_exist'] = 'The requested performance activity could not be found.';
$string['user_activities_as_a_role'] = 'As {$a}';
$string['user_activities_close_on_completion_submit_confirmation_message'] = 'You will not be able to update your responses after submission.';
$string['user_activities_closed'] = 'Closed';
$string['user_activities_created_at'] = 'Created {$a}.';
$string['user_activities_due_today'] = 'Due today';
$string['user_activities_filter'] = 'Filter activities';
$string['user_activities_filter_exclude_completed'] = 'Exclude completed activities';
$string['user_activities_filter_own_progress'] = 'Your progress';
$string['user_activities_filter_overdue_only'] = 'Overdue activities only';
$string['user_activities_filter_search'] = 'Search by activity';
$string['user_activities_filter_search_others'] = 'Search by activity or name';
$string['user_activities_filter_search_others_placeholder'] = 'Activity, name';
$string['user_activities_filter_search_placeholder'] = 'Activity';
$string['user_activities_filter_type'] = 'Type';
$string['user_activities_header'] = 'All activities';
$string['user_activities_historic_activities'] = 'Historic activities';
$string['user_activities_list_none_about_self'] = 'There are no activities assigned to you yet.';
$string['user_activities_list_none_filtered'] = 'No matching items found.';
$string['user_activities_other_response_hide'] = "Hide others' responses";
$string['user_activities_other_response_no_participants_identified'] = 'No participants identified';
$string['user_activities_other_response_response'] = '{$a->relationship} response';
$string['user_activities_other_response_show'] = "Show others' responses";
$string['user_activities_page_print'] = '{$a} (performance activity)';
$string['user_activities_page_title'] = 'Performance activities';
$string['user_activities_print_created_on'] = 'Created on {$a}';
$string['user_activities_print_due_date'] = 'Due date: {$a}';
$string['user_activities_print_overall_progress'] = 'Overall progress: {$a}';
$string['user_activities_print_your_progress'] = 'Your progress: {$a}';
$string['user_activities_priority_heading'] = 'There are {$a} activities for you to complete';
$string['user_activities_priority_page_heading'] = '{$a->number} activities to complete as {$a->role}';
$string['user_activities_priority_page_title'] = 'All activities';
$string['user_activities_require_manual_participant_selection_body'] = 'When you have selected the participants, the activities will begin and become visible to them.';
$string['user_activities_require_manual_participant_selection_link_text'] = 'Select participants';
$string['user_activities_require_manual_participant_selection_title'] = 'There is {$a} activity which requires you to select some of the participants';
$string['user_activities_require_manual_participant_selection_title_plural'] = 'There are {$a} activities which require you to select some of the participants';
$string['user_activities_resume'] = 'Resume';
$string['user_activities_resume_a11y'] = 'Resume activity: {$a}';
$string['user_activities_section_view_only'] = '(view only)';
$string['user_activities_select_participants_none'] = 'You have no remaining participants to select.';
$string['user_activities_select_participants_note'] = 'Note: None of these activities can start until participants are selected.';
$string['user_activities_select_participants_page_title'] = 'Select participants';
$string['user_activities_sort_option_created_at'] = 'Most recent';
$string['user_activities_sort_option_activity_name'] = 'Activity';
$string['user_activities_sort_option_subject_name'] = 'Name';
$string['user_activities_sort_option_job_assignment'] = 'Job assignment';
$string['user_activities_sort_option_due_date'] = 'Due date';
$string['user_activities_start'] = 'Start';
$string['user_activities_start_a11y'] = 'Start activity: {$a}';
$string['user_activities_status_complete'] = 'Complete';
$string['user_activities_status_header_section_progress'] = 'Section progress';
$string['user_activities_status_header_relationship'] = 'Relationship to user';
$string['user_activities_status_in_progress'] = 'In progress';
$string['user_activities_status_not_applicable'] = 'n/a (view only)';
$string['user_activities_status_not_applicable_for_relationship_selector'] = 'View only';
$string['user_activities_status_not_started'] = 'Not started';
$string['user_activities_status_not_submitted'] = 'Not submitted';
$string['user_activities_total_completed'] = 'Total completed: {$a}';
$string['user_activities_total_respondents'] = 'Total respondents: {$a}';
$string['user_activities_subject_header'] = 'Name';
$string['user_activities_submit_confirmation_message'] = 'Once submitted, your responses will be visible to other users who have permission to view them.';
$string['user_activities_submit_confirmation_title'] = 'Confirm submission';
$string['user_activities_unit_deadline'] = '{$a->number} {$a->unit}';
$string['user_activities_unit_overdue'] = '{$a->number} {$a->unit} overdue';
$string['user_activities_unit_to_due'] = 'Due in {$a->number} {$a->unit}';
$string['user_activities_you'] = 'You';
$string['user_activities_your_activities_title'] = 'Activities about you';
$string['user_activities_your_relationship_to_user'] = 'Your relationship to user';
$string['user_activities_your_relationship_to_user_internal'] = 'You are participating as:';
$string['user_activities_their_capacity'] = 'of {$a} in their capacity as:';
$string['user_activities_your_capacity'] = 'In your capacity as:';
$string['user_components_accessible_name'] = '{$a->component} for {$a->user}';
$string['user_components_performance_data'] = 'Performance data';
$string['user_creation_date'] = 'User creation date';
$string['user_historic_activities_title'] = 'Your historic activities';
$string['user_historic_activities_about_others_title'] = 'Activities about others';
$string['user_historic_activities_activity_title_header'] = 'Activity title';
$string['user_historic_activities_type_header'] = 'Type';
$string['user_historic_activities_status_header'] = 'Status';
$string['user_historic_activities_relationship_to_header'] = 'Relationship to user';
$string['user_historic_activities_user_header'] = 'User';
$string['user_group_assignment_add_cohort'] = 'Select audiences to add';
$string['user_group_assignment_add_group'] = 'Assign users';
$string['user_group_assignment_confirm_modal_remove'] = 'Remove';
$string['user_group_assignment_confirm_remove_active'] = 'Users in this group will not have any further instances generated for them, unless they remain assigned via membership of another assigned group. Unassigned users\' existing subject instances will not be affected.';
$string['user_group_assignment_confirm_remove_draft'] = 'Instances are not created until after assignment activation, so no users will be affected by this change. Members of the removed group may still be assigned if they are also members of another assigned group.';
$string['user_group_assignment_confirm_remove_title'] = 'Remove assigned group';
$string['user_group_assignment_group_cohort'] = 'Audience';
$string['user_group_assignment_group_cohort_plural'] = 'Audiences';
$string['user_group_assignment_group_individual'] = 'Individual';
$string['user_group_assignment_group_individual_plural'] = 'Individuals';
$string['user_group_assignment_group_organisation'] = 'Organisation';
$string['user_group_assignment_group_organisation_plural'] = 'Organisations';
$string['user_group_assignment_group_position'] = 'Position';
$string['user_group_assignment_group_position_plural'] = 'Positions';
$string['user_group_assignment_group_cohort_name'] = 'Audience name';
$string['user_group_assignment_group_org'] = 'Organisation';
$string['user_group_assignment_group_pos'] = 'Position';
$string['user_group_assignment_group_user'] = 'Individual';
$string['user_group_assignment_name'] = 'Name';
$string['user_group_assignment_no_users'] = 'No groups assigned';
$string['user_group_assignment_title'] = 'Activity assigned to';
$string['user_group_assignment_type'] = 'Group type';
$string['user_group_assignment_usercount'] = 'Users';
$string['user_individual_assignment_confirm_remove_active'] = 'This user will not have any further instances generated for them, unless they remain assigned via membership of another assigned group. The unassigned user\'s existing subject instances will not be affected.';
$string['user_individual_assignment_confirm_remove_draft'] = 'Instances are not created until after assignment activation, so this user will not be affected by this change. The removed user may still be assigned if they are also a member of another assigned group.';
$string['user_individual_assignment_confirm_remove_title'] = 'Remove assigned individual';
$string['userdataitemexport_user_responses'] = 'User\'s responses';
$string['userdataitemexport_user_responses_help'] = 'All responses by a user about themselves and about others.';
$string['userdataitemexport_other_hidden_responses'] = 'Responses in performance activities about the user that are hidden from them';
$string['userdataitemexport_other_hidden_responses_help'] = 'Responses on all the user\'s subject instances that they are not permitted to see via the interface, because they are from a section where they are neither a respondent with permission to view others\' responses, nor a view-only participant.';
$string['userdataitemexport_other_visible_responses'] = 'Responses in performance activities about the user that are visible to them';
$string['userdataitemexport_other_visible_responses_help'] = 'Responses on all the user\'s subject instances that they are permitted to see via the interface, because they are from a section where they are either a respondent with permission to view others\' responses, or a view-only participant. This includes the user\'s own responses, which are always visible to themselves.';
$string['userdataitempurge_other_responses'] = 'Performance activities about the user';
$string['userdataitempurge_other_responses_help'] = 'Removes all performance activity subject instances about the user. This includes responses by the user and responses from others about the user.';
$string['userdataitempurge_user_responses'] = 'User\'s participation in performance activities';
$string['userdataitempurge_user_responses_help'] = 'Removes all participant instances associated with the user. This includes all responses a user has provided on their own performance activities as well as responses a user has provided on performance activities about others.';
$string['view_actions'] = 'Actions';
$string['view_as_report'] = 'View as report';
$string['view_creation_date'] = 'Creation date';
$string['view_content_elements'] = 'View content elements';
$string['view_details'] = 'View details';
$string['view_name'] = 'Name';
$string['view_response_data'] = 'View response data';
$string['view_status'] = 'Status';
$string['view_report_confirm_modal_body'] = 'The report may take some time to load if it contains a large number of data.';
$string['view_report_confirm_modal_title'] = 'View performance activity response report';
$string['view_type'] = 'Type';
$string['visibility_condition_all_closed'] = 'Response submission is closed for all participants';
$string['visibility_condition_all_participants_closed'] = "Response submission is closed for all participants";
$string['visibility_condition_all_participants_closed_description'] = 'Responses are only visible to viewers when response submission is closed for all participants.';
$string['visibility_condition_all_participants_closed_view_only_description'] = 'Once response submission is closed for all participants, their responses are displayed here.';
$string['visibility_condition_label'] = 'Visibility condition';
$string['visibility_condition_label_help'] = 'This setting determines when users whose relationship allows them to view others\' responses will have access to those responses. Responses are never visible to others before they\'ve been submitted. When anonymising responses is enabled, closure of all response submissions is enforced as the condition for response visibility, to ensure anonymity.';
$string['visibility_condition_non'] = 'None (submitted responses immediately visible to viewers)';
$string['visibility_condition_none_view_only_description'] = 'Responses are displayed as soon as a participant has submitted.';
$string['visibility_condition_viewer_closed'] = "Response submission is closed for the participant who is viewing";
$string['visibility_condition_viewer_closed_description'] = 'Responses are only visible to viewers when their own response submission is closed.';
$string['visibility_condition_status_mismatch_warning'] = 'This condition cannot be met by participants without manual intervention, because automatic closure is currently disabled. Enable it as a workflow setting on the "Content" tab.';
$string['workflow_automatic_closure_confirmation_title'] = 'Confirm workflow change';
$string['workflow_automatic_closure_enabled_confirmation_text'] = 'Only future instances and those that are not yet complete will be automatically closed on completion. Already completed instances will not be affected.';
$string['workflow_automatic_closure_disabled_confirmation_text'] = 'Only future instances and those that are not yet complete will remain open on completion. Already closed instances will remain that way.';
$string['workflow_automatic_closure_label'] = 'Automatic closure';
$string['workflow_automatic_closure_label_help'] = 'While a section or instance is open, participants may submit responses. Once a section or instance is closed, responses cannot be modified or (re)submitted. These settings determine what causes closure to occur (if at all). Changes to the "On completion" setting will be applied to future, but not already existing, subject instances. Changes to the "On due date" setting will apply to future subject instances, and to existing subject instances if they have a due date.';
$string['workflow_automatic_closure_on_completion'] = 'On completion';
$string['workflow_automatic_closure_on_completion_help'] = 'Sections and instances will close once they have progressed to "Complete"';
$string['workflow_automatic_closure_disable_due_date_warning_main'] = 'The due date is set as a condition for automatic activity closure.';
$string['workflow_automatic_closure_disable_due_date_warning_secondary'] = 'Only future instances and those that are not yet complete will remain open at the due date. Already closed instances will remain closed.';
$string['workflow_automatic_closure_disable_due_date_warning_title'] = 'Disable due date?';
$string['workflow_automatic_closure_on_due_date'] = 'On due date';
$string['workflow_automatic_closure_on_due_date_help'] = 'All active instances of the activity will close on the due date';
$string['workflow_automatic_closure_on_due_date_help_no_due_date'] = 'To enable this option you must set a due date on the "Instance creation" tab';
$string['workflow_automatic_closure_on_due_date_no_due_date'] = 'On due date (no due date set)';
$string['workflow_due_date_closure_confirmation_title'] = 'Confirm workflow change';
$string['workflow_due_date_closure_disabled_confirmation_text'] = 'All open activity instances and any activity instances created in the future will no longer automatically close on the activity due date.';
$string['workflow_due_date_closure_enabled_confirmation_text'] = 'All open and overdue activity instances, and any activity instances created in the future, will automatically close on the activity due date.';
$string['workflow_settings'] = 'Workflow settings';
$string['x_record_found'] = 'record found';
$string['x_records_found'] = 'records found';
$string['you_can_add_this_later'] = 'You can add this later.';
$string['your_response'] = 'Your response';

// Deprecated since 17
$string['export_confirm_modal_text'] = 'The selected records will be exported to CSV, with one row per response. If you have requested a large number of records, the export may take some time. Your browser will need to remain open until the export is complete.';
$string['export_type_csv'] = 'CSV';
$string['x_record_selected'] = '<strong>{$a}</strong> record selected';
$string['x_records_selected'] = '<strong>{$a}</strong> records selected';
$string['response_data_report_export_link_text'] = 'Performance activity response data (export)';
$string['response_visibility_not_visible_to_anyone'] = 'Your responses are not visible to other participants';
$string['user_activities_complete_before'] = 'Due date: {$a}.';
$string['user_activities_due_date_header'] = 'Due date';
$string['user_activities_job_assignment_header'] = 'Job assignment';
$string['user_activities_single_section_view_only_activity'] = 'You have view-only access to this activity.';
$string['user_activities_status_header_activity'] = 'Overall progress';
$string['user_activities_status_header_participation'] = 'Your progress';
$string['user_activities_title_header'] = 'Activity';
$string['user_activities_type_header'] = 'Type';
$string['messageprovider:activity_notification'] = 'Performance activity notifications';
$string['messageprovider:activity_reminder'] = 'Performance activity reminders';

// Deprecated since 16.1
$string['modal_subject_instance_delete_message'] = 'This will permanently delete the subject instance and all associated records. This may affect aggregated data based on these records.';

// Deprecated since 16
$string['modal_can_not_delete_activity_message'] = 'This activity cannot be deleted, because it contains questions that are being referenced by other elements:';
$string['general_info_participant_selection_description'] = 'Participants for each relationship below must be manually chosen by the selected role.';
$string['general_info_participant_selection_heading'] = 'Selection of participants';
$string['user_activities_require_manual_participant_selection_header_text'] = 'There are activities awaiting participant selection. Please select people to participate so that those activities can start.';
$string['schedule_repeating_every_time_after_completion'] = 'time since completion of previous instance:';
$string['schedule_repeating_every_time_after_completion_a11y'] = 'every {$a->value} {$a->range} after the completion of their previous instance';
$string['schedule_repeating_every_time_after_creation_when_complete'] = 'completion of previous instance, once minimum time since creation has passed:';
$string['schedule_repeating_every_time_after_creation_when_complete_a11y'] = 'when previous instance is complete, and it was created more than {$a->value} {$a->range} ago';
$string['schedule_repeating_every_time_since_creation'] = 'time since creation of previous instance:';
$string['schedule_repeating_every_time_since_creation_a11y'] = 'every {$a->value} {$a->range} after the creation of their previous instance';

// Deprecated since 15
$string['all_job_assignments'] = 'All';

// Deprecated since 13.2

$string['external_participation_success_review_link'] = 'Review your responses.';
$string['modal_element_unsaved_changes_message'] = 'You currently have unsaved changes that will be lost if you close this content editor. Cancel to go back and save individual content elements. Close to discard the changes.';
$string['modal_element_unsaved_changes_title'] = 'Unsaved changes will be lost';
$string['reporting_identifier_a11y'] = 'Reporting ID: {$a}';
$string['response_data_report_export_link'] = 'Performance activity response data (export)';
$string['teams_page_response_report_link'] = 'view or export';
$string['user_activities_require_manual_participant_selection_link'] = 'Select participants';
$string['user_activities_require_manual_participant_selection_text'] = 'You must select participants to take part in performance activities. Note: activities cannot start until this is done';
$string['user_components_performance_data_link'] = 'Performance data';
$string['user_group_assignment_unique_user_count_link'] = 'View report';
$string['user_group_assignment_unique_user_count_title'] = 'Total unique users currently assigned as subjects';

// Deprecated since 17.0

$string['check_notification_trigger_task'] = 'Periodically check notification trigger conditions';

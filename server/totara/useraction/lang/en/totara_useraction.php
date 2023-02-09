<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

$string['pluginname'] = 'User actions';

// Capabilities
$string['useraction:manage_actions'] = 'Manage User Actions';

// Exceptions
$string['error:invalid_action'] = 'The action field must be a valid action.';
$string['error:missing_duration'] = 'The duration filter fields source, unit and value must not be empty.';
$string['error:missing_name'] = 'The name field must not be empty.';

// UI
$string['action_criteria'] = 'Action criteria';
$string['action_delete_user'] = 'Delete user';
$string['action_deleted'] = 'Action successfully deleted';
$string['action_name'] = 'Action name';
$string['action_saved'] = 'Action successfully saved';
$string['action_type'] = 'Action type';
$string['action_type_help'] = "The type of action that will apply when the scheduled action runs against users who meet the criteria rules.";
$string['actions'] = 'Actions';
$string['add_action'] = 'Add action';
$string['add_audience'] = 'Add audience';
$string['add_scheduled_action'] = 'Add new user action';
$string['applies_to'] = 'Applies to';
$string['cohort_delete_affects_changes'] = 'Removed';
$string['conditions'] = 'Conditions';
$string['create_new_action'] = 'Create new action';
$string['criteria'] = 'Criteria';
$string['data_source'] = 'Data source';
$string['data_source_help'] = "The source date that will be used when calculating the duration period. For example, 'Date suspended' is when a user first had their user status changed to 'suspended'.";
$string['date_suspended'] = 'Date suspended';
$string['delete_scheduled_action'] = 'Delete scheduled action';
$string['delete_scheduled_action_confirm_message'] = 'Are you sure you want to delete this scheduled action? This cannot be undone.';
$string['disabled'] = 'Disabled';
$string['discard_changes'] = 'Discard changes';
$string['duration'] = 'Duration';
$string['duration_help'] = "The period calculated since the data source. For example, the criteria could apply to users who's 'Date suspended' happened 7 years ago.";
$string['duration_unit'] = 'Duration unit';
$string['duration_value'] = 'Duration value';
$string['edit_action'] = 'Edit';
$string['edit_scheduled_action'] = 'Edit user action';
$string['enabled'] = 'Enabled';
$string['error_must_be_at_least_1'] = 'Must be at least 1';
$string['execute_active_scheduled_rules'] = 'Process scheduled user actions';
$string['exit_without_saving'] = 'Are you sure you want to exit without saving?';
$string['filter_applies_to_all_users'] = 'All users';
$string['filter_applies_to_audiences'] = 'Audiences';
$string['filter_applies_to_audiences_x'] = 'Audiences ({$a})';
$string['general'] = 'General';
$string['history_title'] = 'Scheduled user action report: {$a}';
$string['history_title_all'] = 'Scheduled user action report';
$string['id'] = 'ID';
$string['no_actions'] = 'No actions have been created.';
$string['no_audiences_selected'] = 'No audiences selected';
$string['pluginname'] = 'User actions';
$string['remove_x'] = 'Remove "{$a}"';
$string['scheduled_user_actions'] = 'Scheduled user actions';
$string['status_help'] = 'This user action will only run when marked as Enabled.';
$string['unknown_action'] = 'Unknown action';
$string['unit_days'] = 'days';
$string['unit_months'] = 'months';
$string['unit_years'] = 'years';
$string['unsaved_changes_message'] = 'Any unsaved changes will not be saved.';
$string['user_status'] = 'User status';
$string['user_status_help'] = 'The current user status that the user must have as part of the filter criteria.';
$string['view_history'] = 'Past actions report';

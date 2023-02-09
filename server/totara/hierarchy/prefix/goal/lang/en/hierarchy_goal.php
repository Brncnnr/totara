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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

defined('MOODLE_INTERNAL') || die();

$string['a11y_goal_status_updated_date'] = 'Date updated:';
$string['add_company_goals'] = 'Add company goals';
$string['add_personal_goals'] = 'Add personal goals';
$string['awaiting_company_selection_text'] = 'Awaiting company goal selection from a {$a}.';
$string['awaiting_personal_selection_text'] = 'Awaiting personal goal selection from a {$a}.';
$string['enable_goal_status_change'] = 'Ability to change goal status during activity';
$string['enable_goal_status_change_participant'] = 'Change of goal status participant';
$string['eventchangedtype'] = 'Changed Goal Type';
$string['eventcreatedassignment'] = 'Created Goal Assignment';
$string['eventcreatedframework'] = 'Created Goal Framework';
$string['eventcreateditem'] = 'Created Goal';
$string['eventcreatedpersonalgoal'] = 'Created Personal Goal';
$string['eventcreatedscale'] = 'Created Goal Scale';
$string['eventcreatedscalevalue'] = 'Created Goal Scale Value';
$string['eventcreatedtype'] = 'Created Goal Type';
$string['eventdeletedassignment'] = 'Deleted Goal Assignment';
$string['eventdeletedframework'] = 'Deleted Goal Framework';
$string['eventdeleteditem'] = 'Deleted Goal';
$string['eventdeletedpersonalgoal'] = 'Deleted Personal Goal';
$string['eventdeletedscale'] = 'Deleted Goal Scale';
$string['eventdeletedscalevalue'] = 'Deleted Goal Scale Value';
$string['eventdeletedtype'] = 'Deleted Goal Type';
$string['eventmoveditem'] = 'Moved Goal';
$string['eventupdatedframework'] = 'Updated Goal Framework';
$string['eventupdateditem'] = 'Goal Updated';
$string['eventupdatedpersonalgoal'] = 'Updated Personal Goal';
$string['eventupdatedscale'] = 'Updated Goal Scale';
$string['eventupdatedscalevalue'] = 'Updated Goal Scale Value';
$string['eventupdatedtype'] = 'Updated Goal Type';
$string['eventvieweditem'] = 'Viewed Goal';
$string['example_goal_title'] = 'Goals example';
$string['example_goal_description'] = 'This is an example of how a goal will display after a participant has selected it.';
$string['example_goal_status'] = 'Goal assigned';
$string['goal_change_status_help'] = 'Only one goal status change will be submitted to the goal. If there are multiple people in the participant role, the first change submitted will be applied.';
$string['goal_confirmation_body_1'] = 'You\'ve given \'{$a->goal_name}\' a status of <strong>{$a->scale_value}</strong> for {$a->user}';
$string['goal_confirmation_body_2'] = 'This will be submitted to the goal';
$string['goal_exists_message'] = 'Your goal status has not been saved, as this has already been selected by you or by someone in the same role as you.';
$string['goal_scale_unavailable'] = 'There is no goal status to update for this goal';
$string['goal_status'] = 'Goal status';
$string['goal_status_answered_by_other'] = 'This will be answered by a {$a}';
$string['goal_status_response_subject'] = '{$a} response';
$string['goal_status_select'] = 'Select goal status';
$string['goal_status_update'] = 'Goal status update';
$string['goal_status_updated'] = 'Goal status updated';
$string['goal_status_updated_error'] = 'Unable to update goal status';
$string['goal_updated_by'] = 'Status update by: {$a->user} ({$a->relationship})';
$string['perform_review_goal_missing'] = 'The goal no longer exists';
$string['perform_review_goal_status_changer_you'] = 'You';
$string['pluginname'] = 'Goal Hierarchies';
$string['remove_company_goal'] = 'Remove company goal';
$string['remove_personal_goal'] = 'Remove personal goal';
$string['selected_goal'] = 'Goal: {$a}';
$string['submit_status'] = 'Submit status';
$string['submit_goal_title'] = 'Submit goal status?';
$string['target_date'] = 'Target date';
$string['updated_goal_status'] = 'Goal status: {$a}';
$string['userdataitemcompany_export_hidden'] = 'Company goals hidden';
$string['userdataitemcompany_export_hidden_help'] = 'Only relevant to users without the viewowncompanygoal capability. To export company goals regardless of a user’s capability, select both the visible and hidden settings.';
$string['userdataitemcompany_export_visible'] = 'Company goals visible';
$string['userdataitemcompany_export_visible_help'] = 'Only relevant to users with the viewowncompanygoal capability. To export company goals regardless of a user’s capability, select both the visible and hidden settings.';
$string['userdataitemcompany_purge'] = 'Company goals purge';
$string['userdataitemcompany_purge_help'] = 'This includes all data related to a user\'s company goal assignments regardless of whether they have the viewowncompanygoal capability.';
$string['userdataitemperform_goal_status_other'] = 'Performance activity status change on other users\' goals';
$string['userdataitemperform_goal_status_other_help'] = "When purging this item, the status change will be anonymised (changer’s name replaced with text indicating it has been removed). The change itself (scale value, role in which change was made) will remain.";
$string['userdataitemperform_goal_status_self'] = 'Performance activity status change on own goals';
$string['userdataitempersonal_export_hidden'] = 'Personal goals hidden';
$string['userdataitempersonal_export_hidden_help'] = 'Only relevant to users without the viewownpersonalgoal capability. To export personal goals regardless of a user’s capability, select both the visible and hidden settings.';
$string['userdataitempersonal_export_visible'] = 'Personal goals visible';
$string['userdataitempersonal_export_visible_help'] = 'Only relevant to users with the viewownpersonalgoal capability. To export personal goals regardless of a user’s capability, select both the visible and hidden settings.';
$string['userdataitempersonal_purge'] = 'Personal goals';
$string['userdataitempersonal_purge_help'] = 'This includes all data related to a user\'s personal goals regardless of whether they have the viewownpersonalgoal capability.';

<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_manual
 */

$string['pluginname'] = 'Manual rating';

$string['achievement_path_group_label'] = 'Assessment';
$string['activity_log_rating_by'] = 'Rating by {$a->name} ({$a->role})';
$string['activity_log_rating_by_removed'] = 'Rating by {$a} <i>(rater details removed)</i>';
$string['add_comment'] = 'Add optional comment';
$string['add_rating'] = 'Add rating';
$string['add_raters'] = 'Add raters';
$string['comment'] = 'Comment';
$string['comment_wrapper'] = '"{$a}"';
$string['competency_framework_count_plural'] = '{$a->name}: {$a->count} competencies';
$string['competency_framework_count_singular'] = '{$a->name}: {$a->count} competency';
$string['date'] = 'Date';
$string['edit_rating'] = 'Edit rating';
$string['edit_rating_a11y'] = 'Edit rating for {$a}';
$string['error'] = 'Error';
$string['error_no_raters'] = 'No raters added';
$string['error_ratings_not_saved'] = 'An error occurred, and your ratings have not been saved.';
$string['error_user_lacks_role_for_any'] = 'There are no users that you are allowed to rate';
$string['error_user_lacks_role_for_user'] = 'You do not have the {$a} role for this user';
$string['filter_no_competencies'] = 'No competencies match your selection criteria.';
$string['filter_no_users'] = 'No users match your search criteria.';
$string['filter_previously_rated'] = 'Previously rated';
$string['filter_rating_history'] = 'Rating history';
$string['filter_reason_assigned'] = 'Reason assigned';
$string['filter_update_selection'] = 'Update selection';
$string['fullname_date'] = '{$a->name}, {$a->date}';
$string['last_rated'] = 'Last rated';
$string['last_rating_given'] = 'Previous rating';
$string['last_rating_given_other_tooltip'] = 'The last rating this employee was given by someone in this role';
$string['last_rating_given_self_tooltip'] = 'The last rating you gave yourself for this competency';
$string['modal_confirm_update_filters_body'] = '<p>You have unsubmitted ratings. If you apply the filters to change the selection of competencies, you will lose these unsaved changes.</p><p>Are you sure you would like to update the selection?</p>';
$string['modal_confirm_update_filters_title'] = 'Confirm selection update';
$string['modal_confirm_update_role_body'] = '<p>You have unsubmitted ratings. If you change the role you are rating as, you will lose these unsaved changes.</p><p>Are you sure you would like to change your role?</p>';
$string['modal_confirm_update_role_title'] = 'Confirm role update';
$string['modal_submit_ratings_confirmation_question'] = 'Do you want to submit these ratings?';
$string['modal_submit_ratings_confirmation_title'] = 'Submit ratings and comments';
$string['modal_submit_ratings_summary_plural_other'] = 'You\'ve rated {$a->subject_user} on {$a->amount} competencies.';
$string['modal_submit_ratings_summary_plural_self'] = 'You\'ve rated yourself on {$a->amount} competencies.';
$string['modal_submit_ratings_summary_singular_other'] = 'You\'ve rated {$a->subject_user} on 1 competency.';
$string['modal_submit_ratings_summary_singular_self'] = 'You\'ve rated yourself on 1 competency.';
$string['never_rated'] = 'Never rated';
$string['new_rating'] = 'New rating';
$string['no_assessors_can_rate'] = 'There are no assessors that are allowed to rate';
$string['no_rateable_competencies'] = 'There are no competencies available for you to rate.';
$string['no_rating_given'] = 'No rating given';
$string['notification_ratings_saved_plural'] = 'Your ratings have been saved.';
$string['notification_ratings_saved_singular'] = 'Your rating has been saved.';
$string['number_of_people'] = '{$a} people';
$string['rate'] = 'Rate';
$string['rate_competencies'] = 'Rate competencies';
$string['rate_competencies_for_user'] = 'Rate competencies for {$a}';
$string['rate_competency_a11y'] = 'Rate competency: {$a}';
$string['rate_user'] = 'Rate {$a}';
$string['rater_details_removed'] = ' (rater details removed)';
$string['rater'] = 'Rater';
$string['raters'] = 'Raters';
$string['raters_info'] = 'Manual ratings apply to all current assignments for this competency.';
$string['rating'] = 'Rating';
$string['rating_as_a'] = 'Rating as a';
$string['rating_as_appraiser'] = 'Rating as an appraiser';
$string['rating_as_manager'] = 'Rating as a manager';
$string['rating_by'] = 'Rating by';
$string['rating_done'] = 'Done';
$string['rating_none'] = "No rating";
$string['rating_set_to_none'] = "Set to 'No rating'";
$string['receive_a_rating'] = 'Receive a rating from an assessor';
$string['role_appraiser'] = 'Appraiser';
$string['role_appraiser_prefix'] = 'an appraiser';
$string['role_manager'] = 'Manager';
$string['role_manager_prefix'] = 'a manager';
$string['role_self'] = 'Self';
$string['search_people'] = 'Search people';
$string['select...'] = 'Select...';
$string['select_scale_value'] = 'Select scale value';
$string['select_raters'] = 'Select raters';
$string['self_assessment'] = 'Self-assessment';
$string['unsaved_ratings_warning'] = 'You have unsaved ratings';
$string['user_fullname_wrapper'] = '({$a})';
$string['userdataitemmanual_rating_other'] = 'Manual ratings on others';
$string['userdataitemmanual_rating_other_help'] = 'When purging this item, the rating will be anonymised (rater’s name replaced with text indicating it has been removed). The rating itself (scale value, role in which rating was given, and any associated comments) will remain.';
$string['userdataitemmanual_rating_self'] = 'Manual ratings on self';
$string['view_all'] = 'View all';
$string['view_comment'] = 'View comment';
$string['viewing_single_competency'] = 'Your view is currently filtered to show a single competency only.';
$string['your_rating'] = 'Your rating';

<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

$string['close'] = 'Close window';
$string['cannot_view_survey'] = "You do not have permission to view the survey.";
$string['defaultlabel'] = "Survey";
$string['delete_survey_confirm_1'] = 'Are you sure you want to delete this survey?';
$string['delete_survey_confirm_2'] = 'It will be permanently removed and people will no longer be able to view it.';
$string['deletesurvey'] = "Delete survey";
$string['deletewarningtitle'] = 'Delete survey';
$string['created'] = 'Survey successfully created';
$string['edit_survey'] = 'Edit {$a}';
$string['editsetting'] = "Edit settings";
$string['editsurvey'] = "Edit survey";
$string['editsurveyaccessiblename'] = 'Edit survey {$a}';
$string['expiredat'] = 'Survey ends {$a}';
$string['image_alt'] = 'The icon for the survey {$a}';
$string['formtitle'] = 'Enter survey question';
$string['formtypetitle'] = 'How many answers can people select?';
$string['likesurvey'] = 'Like survey "{$a}"';
$string['message_survey'] = 'survey';
$string['noresult'] = 'No results yet';
$string['option'] = 'Option';
$string['optionmultiple'] = 'Multiple answers';
$string['optionsingle'] = 'Single answer';
$string['optionstitle'] = 'What are the answer options?';
$string['pluginname'] = "Survey";
$string['participant'] = 'participant';
$string['participants'] = 'participants';
$string['percentage'] = '{$a}%';
$string['removelikesurvey'] = 'Remove like for survey "{$a}"';
$string['reportsurvey'] = 'Report content';
$string['resharesurvey'] = 'Reshare survey "{$a}"';
$string['save'] = 'Save';
$string['sharesurvey'] = 'Share survey "{$a}"';
$string['survey'] = 'Survey';
$string['surveycreated'] = 'Survey created';
$string['surveydeleted'] = 'Survey deleted';
$string['surveyreshared'] = 'Survey re-shared';
$string['surveyshared'] = 'Survey shared';
$string['surveyupdated'] = 'Survey updated';
$string['surveyvoted'] = 'Survey voted';
$string['survey_unavailable'] = 'The survey is no longer available';
$string['tagarea_engage_resource'] = 'Survey';
$string['user_data_item_survey'] = 'Survey';
$string['user_data_item_survey_completion'] = 'Survey Vote';
$string['viewresult'] = "View all results";
$string['vote'] = "Vote";
$string['vote_message'] = 'You have a new vote on your survey \'{$a}\'';
$string['vote_message_subject'] = 'Someone has voted on your survey';
$string['vote_message_view'] = 'View survey results: ';
$string['votemessage'] = 'Showing {$a->options} of {$a->questions} results';
$string['votenow'] = "Vote";

// For capability usage
$string['survey:create'] = "Create survey";
$string['survey:delete'] = "Delete survey";
$string['survey:share'] = "Share survey";
$string['survey:update'] = "Update survey";
$string['survey:unshare'] = "Unlink survey";

// For error usage, mostly will be invoked at the parent level - totara_engage.
$string['error:answer'] = "Cannot answer the survey";
$string['error:create'] = "Cannot create survey";
$string['error:delete'] = "Cannot delete the survey";
$string['error:sharecapability'] = 'You do not have the required capabilities to share this survey.';
$string['error:shareprivate'] = 'This survey is viewable by only you. Change who can view this survey in order to share it.';
$string['error:sharerestricted'] = 'This survey is not viewable by everyone and only the owner is allowed to share it.';
$string['error:reportsurvey'] = "Cannot report the survey";
$string['error:update'] = "Cannot update the survey";

// Strings for message component
$string['messageprovider:voting_survey_notification'] = "Survey votes";

// Deprecated in 16
$string['deletewarningmsg'] = 'This action is permanent. People with access to this survey will no longer be able to view it.';
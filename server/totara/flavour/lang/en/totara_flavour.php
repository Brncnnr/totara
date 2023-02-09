<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */

defined('MOODLE_INTERNAL') || die();

$string['available'] = 'Available';
$string['currentsetup'] = 'Current setup';
$string['description'] = 'Configuration flavour: {$a}';
$string['disabled'] = 'Disabled';
$string['enforceflavour'] = 'Enforce current flavour';
$string['error_missing_flavour_name'] = 'Please specify a flavour name, it cannot be empty';
$string['features'] = 'Advanced features';
$string['flavouroverview'] = 'Feature overview';
$string['helpabout'] = 'About {$a}';
$string['off'] = 'Off';
$string['on'] = 'On';
$string['pluginname'] = 'Flavours';
$string['settinglocked'] = 'This setting has been locked';
$string['setting_core_enablecourserpl'] = 'Record of Prior Learning';
$string['setting_core_enablecourserpl_desc'] = 'The Record of Prior Learning allows learners to view their history in Totara, including the courses, competencies, programs, and certifications that they have been assigned.';
$string['setting_core_enablebadges'] = 'Badges';
$string['setting_core_enablebadges_desc'] = 'Open Badges are digital certificates that are awarded to learners manually or automatically based on activity and course completion.';
$string['setting_core_enablereportcaching'] = 'Report Caching';
$string['setting_core_enablereportcaching_desc'] = 'A description of Report Caching';
$string['setting_core_enableglobalrestrictions'] = 'Report Restrictions';
$string['setting_core_enableglobalrestrictions_desc'] = 'Global report restrictions allows rules to be applied to a report restricting report results to those belonging to the users you are allowed to view.';
$string['setting_core_audiencevisibility'] = 'Audience based visibility';
$string['setting_core_audiencevisibility_desc'] = 'Audience-based visibility allows you to limit the catalog so the learners only see the courses, programs and certifications applicable to them based upon the audience(s) they are enrolled in.';
$string['setting_core_enablegoals'] = 'Goals';
$string['setting_core_enablegoals_desc'] = 'Goals allow an organization to define key areas for staff to accomplish throughout the year. Goals can be automatically assigned based on the staff member’s organisation, position, audience or assigned to an individual, so that their manager can track their completion.';
$string['setting_core_enableappraisals'] = 'Appraisals (legacy)';
$string['setting_core_enableappraisals_desc'] = 'Online appraisals are custom forms site administrators create to allow learners, managers, manager’s managers and appraisers to answer questions and review a learner’s work towards goals and learning plan components. This feature has been replaced with Performance activities.';
$string['setting_core_enablecompletionimport'] = 'Completion import';
$string['setting_core_enablecompletionimport_desc'] = 'This imports historical completion records from a CSV file and enrols users into the specified courses. All completions imported that relate to courses that currently exist on the system will be added as Record of Prior Learning (RPL Completions). This will be indicated in reports which include these imported completions.';
$string['setting_core_enablefeedback360'] = '360 Feedback (legacy)';
$string['setting_core_enablefeedback360_desc'] = '360° feedback provides a mechanism for individual users to receive feedback on their progress from a group of their peers. This feature has been replaced with Performance activities.';
$string['setting_core_enablelearningplans'] = 'Learning Plans';
$string['setting_core_enablelearningplans_desc'] = 'Learning plans are a personalized development pathway that allow learners and their managers to assign and track learner progress in courses, competencies, objectives, and programs.';
$string['setting_core_enableprograms'] = 'Programs';
$string['setting_core_enableprograms_desc'] = 'Programs allow courses to be taken in a sequence, enforcing prerequisites and allowing learners to be automatically assigned based on existing details such as their position, organisation, or audiences.';
$string['setting_core_enablecertifications'] = 'Certifications';
$string['setting_core_enablecertifications_desc'] = 'Certifications allow courses to be taken in sequence, enforcing prerequisites and requiring learners to recertify by retaking courses on a scheduled basis.';
$string['setting_core_enabletotaradashboard'] = 'Dashboards';
$string['setting_core_enabletotaradashboard_desc'] = 'Dashboards allow administrators to design multiple interface options for a user to access upon logging into Totara, so that the features and dashboard reports displayed are appropriate for the user.';
$string['setting_core_enableorganisations'] = 'Organisational hierarchies';
$string['setting_core_enableorganisations_desc'] = 'Organisational hierarchies allow you to define the regions, departments, and teams that make up your organisation to automate assignment of learning and report filtering.';
$string['setting_core_enablepositions'] = 'Positions';
$string['setting_core_enablepositions_desc'] = 'Position hierarchies allow you to setup a learner’s position in the system, which allows for automatic assignment of learning and competencies as well as report filtering.';
$string['setting_core_enablecompetencies'] = 'Competencies';
$string['setting_core_enablecompetencies_desc'] = 'Totara competencies allow site administrators to define the skills and knowledge that a learner needs to fulfill and managers to assess staff performance.';
$string['setting_core_enablecompetency_assignment'] = 'Competency Assignment';
$string['setting_core_enablecompetency_assignment_desc'] = 'Competency Assignment allows you to have advanced methods of assigning competencies to users directly or via user groups, it also enables detailed configuration of achievement criteria for each competency.';
$string['setting_core_enablemyteam'] = 'Team';
$string['setting_core_enablemyteam_desc'] = 'The Team dashboard allows managers to view their direct reports to access user profiles, instructor led training, learning plans, records, and performance management options.';
$string['setting_core_enableuser_reports'] = 'Report Builder';
$string['setting_core_enableuser_reports_desc'] = 'Report Builder allows site administrators to create and edit reports and make them available to users based on their role. Users can view report data, filter and save queries, export data, and schedule report data to be automatically emailed.';
$string['setting_core_feature_audiencemanagement'] = 'Audience management';
$string['setting_core_feature_audiencemanagement_desc'] = 'Audience management allows you to create rulesets to dynamically group users for automated learning assignment, role assignment, course visibility, dashboard access, and menu selections.';
$string['setting_core_feature_facetoface'] = 'Seminar activities';
$string['setting_core_feature_facetoface_desc'] = 'The seminar activity tracks instructor-led training events, allowing learners to choose a event and register, receive automated notifications, and view upcoming and past bookings on their calendar. Trainers can register learners, view/print a registration list, and track event attendance.  ';
$string['setting_core_enableengage_resources'] = 'Library (All types of Resources, Playlists)';
$string['setting_core_enableengage_resources_desc'] = 'Library allows users to create and share resources/playlists within their Library, as well as access resources/playlists that have been saved and shared with them.';
$string['setting_core_enablecontainer_workspace'] = 'Workspaces';
$string['setting_core_enablecontainer_workspace_desc'] = 'Workspaces allow members to create and discover content, and collaborate with each other, in one place. For example, they can post and comment on discussions, share files, resources and playlists.';
$string['setting_core_enabletotara_msteams'] = 'Microsoft Teams';
$string['setting_core_enabletotara_msteams_desc'] = 'Allows rich integration with Microsoft Teams so that users can view and interact with their Library of resources/playlists, current learning, notifications and Find learning catalogue through Microsoft Teams.';
$string['setting_core_enableml_recommender'] = 'Machine Learning Recommendations Engine';
$string['setting_core_enableml_recommender_desc'] = 'The Recommendations engine pushes personalised content specifically recommended for users (e.g. playlists, resources, surveys, workspaces).';
$string['setting_core_enableperformance_activities'] = 'Performance activities';
$string['setting_core_enableperformance_activities_desc'] = 'Performance activities allow you to create assign and track performance forms with complex workflows';
$string['setting_core_enableevidence'] = 'Evidence';
$string['setting_core_enableevidence_desc'] = 'Evidence provides a way to upload evidence of having achieved something outside of the platform';
$string['unavailable'] = 'Unavailable';
$string['unknown'] = 'Unknown';

// Deprecated in 12

$string['setting_core_enhancedcatalog'] = 'Enhanced course catalog';
$string['setting_core_enhancedcatalog_desc'] = 'The Enhanced course catalog allows site administrators to decide what information and filters to display to learners who are able to then search and filter for particular courses, programs, and certifications.';

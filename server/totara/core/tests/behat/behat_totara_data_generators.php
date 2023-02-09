<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 * @category  test
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/helper_generator.php');

use Behat\Behat\Tester\Exception\PendingException as PendingException;
use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Class to set up quickly a Given environment.
 *
 * Acceptance tests are block-boxed, so this steps definitions should only
 * be used to set up the test environment as we are not replicating user steps.
 *
 * All data generators should be in \testing\ namespace, shared between phpunit
 * and behat and they should be called from here, if possible using the standard
 * 'create_$elementname($options)' and if it's not possible (data generators arguments will not be
 * always the same) or the element is not suitable to be a data generator, create a
 * 'process_$elementname($options)' method and use the data generator from there if possible.
 */
class behat_totara_data_generators extends behat_base {
    /**
     * Each component element specifies:
     * - The data generator sufix used.
     * - The required fields.
     * - The mapping between other elements references and database field names.
     * @var array
     */
    protected static $componentelements = array(
        // NOTE: this could be dynamic, but it is not a problem for Totara.
        'mod_approval' => array(
            'forms' => array(
                'datagenerator' => 'form_for_behat',
                'required' => array('title'),
            ),
            'form versions' => array(
                'datagenerator' => 'form_version_for_behat',
                'required' => array('form', 'version'),
            ),
            'workflow types' => array(
                'datagenerator' => 'workflow_type_for_behat',
                'required' => array('name'),
            ),
            'workflows' => array(
                'datagenerator' => 'workflow_for_behat',
                'required' => array('name', 'id_number', 'workflow_type', 'form'),
            ),
            'workflow versions' => array(
                'datagenerator' => 'workflow_version_for_behat',
                'required' => array('workflow', 'form_version'),
            ),
            'workflow stages' => array(
                'datagenerator' => 'workflow_stage_for_behat',
                'required' => array('name', 'workflow', 'type'),
            ),
            'approval levels' => array(
                'datagenerator' => 'approval_level_for_behat',
                'required' => array('name', 'workflow_stage'),
            ),
            'form views' => array(
                'datagenerator' => 'formview_for_behat',
                'required' => array('workflow_stage', 'field_key'),
            ),
            'assignments' => array(
                'datagenerator' => 'assignment_for_behat',
                'required' => array('name', 'id_number', 'workflow', 'type', 'identifier'),
            ),
            'approvers' => array(
                'datagenerator' => 'approver_for_behat',
                'required' => array('assignment', 'approval_level', 'type', 'identifier'),
            ),
            'applications' => array(
                'datagenerator' => 'application_for_behat',
                'required' => array('user', 'workflow', 'assignment'),
            ),
            'application submissions' => array(
                'datagenerator' => 'application_submission_for_behat',
                'required' => array('application', 'form_data'),
            ),
            'application actions' => array(
                'datagenerator' => 'application_action_for_behat',
                'required' => array('application', 'user', 'action'),
            ),
        ),
        'mod_facetoface' => array (
            'custom seminar fields' => array(
                'datagenerator' => 'custom_field_for_behat',
                'required' => array('prefix', 'datatype'),
            ),
            'global rooms' => array(
                'datagenerator' => 'global_room_for_behat',
                'required' => array('name'),
            ),
            'custom rooms' => array(
                'datagenerator' => 'custom_room_for_behat',
                'required'      => array('name'),
            ),
            'global assets' => array(
                'datagenerator' => 'global_asset_for_behat',
                'required' => array('name')
            ),
            'custom assets' => array(
                'datagenerator' => 'custom_asset_for_behat',
                'required' => array('name')
            ),
            'global facilitators' => array(
                'datagenerator' => 'global_facilitator_for_behat',
                'required' => array('name')
            ),
            'custom facilitators' => array(
                'datagenerator' => 'custom_facilitator_for_behat',
                'required' => array('name')
            ),
            'seminars' => array(
                'datagenerator' => 'instance_for_behat',
                'required' => array('course', 'name')
            ),
            'seminar events' => array(
                'datagenerator' => 'sessions_for_behat',
                'required' => array('facetoface', 'details'),
            ),
            'seminar sessions' => array(
                'datagenerator' => 'sessiondates_for_behat',
                'required' => array('eventdetails', 'start', 'finish')
            ),
            'seminar signups' => array(
                'datagenerator' => 'signups_for_behat',
                'required' => array('eventdetails', 'user')
            ),
            'asset timecreated' => array(
                'age_data' => 'age_asset_timecreated'
            ),
            'facilitator timecreated' => array(
                'age_data' => 'age_facilitator_timecreated'
            ),
            'room timecreated' => array(
                'age_data' => 'age_room_timecreated'
            )
        ),
        'totara_core' => array(
            'custom profile fields' => array(
                'datagenerator' => 'custom_profile_field',
                'required' => array('datatype'),
            ),
            'custom course fields' => array(
                'datagenerator' => 'custom_course_field',
                'required' => array('datatype'),
            ),
            'custom program fields' => array(
                'datagenerator' => 'custom_program_field',
                'required' => array('datatype'),
            ),
            'custom profile field assignments' => array(
                'datagenerator' => 'profile_custom_field_assignment',
                'required' => array('username', 'fieldname', 'value'),
            ),
        ),
        'totara_cohort' => array(
            'cohort enrolments' => array(
                'datagenerator' => 'cohort_enrolment',
                'required' => array('cohort', 'course'),
                'switchids' => array(
                    'cohort' => 'cohortid',
                    'course' => 'courseid',
                    'role' => 'roleid',
                ),
            ),
            'cohort members' => array(
                'datagenerator' => 'cohort_member',
                'required' => array('cohort', 'user'),
                'switchids' => array(
                    'cohort' => 'cohortid',
                    'user' => 'userid',
                ),
            ),
        ),
        'totara_program' => array(
            'programs' => array(
                'datagenerator' => 'program',
                'required' => array('shortname'),
                'switchids' => array(
                    'category' => 'category'
                ),
            ),
            'program assignments' => array(
                'datagenerator' => 'prog_assign',
                'required' => array('user', 'program'),
                'switchids' => array(
                    'user' => 'userid',
                    'program' => 'programid',
                ),
            ),
            'certifications' => array(
                'datagenerator' => 'certification',
                'required' => array('shortname'),
                'switchids' => array(
                    'category' => 'category'
                ),
            ),
        ),
        'totara_hierarchy' => array(
            'position frameworks' => array(
                'datagenerator' => 'pos_frame',
                'required' => array('idnumber'),
            ),
            'positions' => array(
                'datagenerator' => 'pos',
                'required' => array('fullname', 'idnumber', 'pos_framework'),
                'switchids' => array(
                    'pos_framework' => 'frameworkid',
                ),
            ),
            'position type' => array(
                'datagenerator' => 'pos_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'organisation frameworks' => array(
                'datagenerator' => 'org_frame',
                'required' => array('idnumber'),
            ),
            'organisations' => array(
                'datagenerator' => 'org',
                'required' => array('fullname', 'idnumber', 'org_framework'),
                'switchids' => array(
                    'org_framework' => 'frameworkid',
                ),
            ),
            'organisation type' => array(
                'datagenerator' => 'org_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'competency frameworks' => array(
                'datagenerator' => 'comp_frame',
                'required' => array('idnumber'),
            ),
            'competencies' => array(
                'datagenerator' => 'comp',
                'required' => array('fullname', 'idnumber', 'comp_framework'),
                'switchids' => array(
                    'comp_framework' => 'frameworkid',
                ),
            ),
            'competency type' => array(
                'datagenerator' => 'comp_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'goal frameworks' => array(
                'datagenerator' => 'goal_frame',
                'required' => array('idnumber'),
            ),
            'goals' => array(
                'datagenerator' => 'goal',
                'required' => array('fullname', 'idnumber', 'goal_framework'),
                'switchids' => array(
                    'goal_framework' => 'frameworkid',
                ),
            ),
            'goal type' => array(
                'datagenerator' => 'goal_type',
                'required' => array('fullname', 'idnumber'),
            ),
            'hierarchy type assignments' => array(
                'datagenerator' => 'hierarchy_type_assign',
                'required' => array('hierarchy', 'field', 'typeidnumber', 'idnumber', 'value'),
            ),
            'checkbox field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_checkbox',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'menu field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_generic_menu',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'textinput field for hierarchy type' => array(
                'datagenerator' => 'hierarchy_type_text',
                'required' => array('hierarchy', 'typeidnumber', 'value'),
            ),
            'goal assignments' => array(
                'datagenerator' => 'goal_assign',
                'required' => array('user', 'goal'),
                'switchids' => array(
                    'user' => 'userid',
                    'goal' => 'goalid',
                ),
            ),
        ),
        'totara_plan' => array (
            'plans' => array(
                'datagenerator' => 'learning_plan',
                'required' => array('user', 'name')
            ),
            'objectives' => array(
                'datagenerator' => 'learning_plan_objective_for_behat',
                'required' => array('user', 'plan', 'name')
            ),
            'linked courses' => array(
                'datagenerator' => 'learning_plan_course_link_for_behat',
                'required' => array('user', 'plan', 'name')
            ),
            'linked competencies' => array(
                'datagenerator' => 'learning_plan_competency_link_for_behat',
                'required' => array('user', 'plan', 'name')
            ),
            'linked programs' => array(
                'datagenerator' => 'learning_plan_program_link_for_behat',
                'required' => array('user', 'plan', 'name')
            ),
        ),
        'totara_appraisal' => array (
            'appraisals' => array(
                'datagenerator' => 'appraisal',
                'required' => array('name'),
            ),
            'stages' => array(
                'datagenerator' => 'stage_for_behat',
                'required' => array('appraisal', 'name'),
            ),
            'pages' => array(
                'datagenerator' => 'page_for_behat',
                'required' => array('appraisal', 'stage', 'name'),
            ),
            'questions' => array(
                'datagenerator' => 'question_for_behat',
                'required' => array('appraisal', 'stage', 'page', 'name'),
            ),
            'assignments' => array(
                'datagenerator' => 'assignment_for_behat',
                'required' => array('appraisal', 'type', 'id'),
            ),
            'messages' => array(
                'datagenerator' => 'message_for_behat',
                'required' => array('appraisal', 'recipients'),
            ),
            'appraisal_job_assignments' => array(
                'datagenerator' => 'appraisal_job_assignments_for_behat',
                'required' => array('appraisal', 'jobassignment'),
            ),
        ),
        'totara_reportbuilder' => array(
            'report_restrictions' => array(
                'datagenerator' => 'global_restriction',
                'required' => array()
            ),
            'standard_report' => array(
                'datagenerator' => 'default_custom_report',
                'required' => array('fullname', 'shortname', 'source'),
            )
        ),
        'totara_competency' => array(
            'assignments' => array(
                'datagenerator' => 'assignment_for_behat',
                'required' => array('competency', 'user_group', 'user_group_type')
            ),
            'course enrollments and completions' => array(
                'datagenerator' => 'course_enrollment_and_completion_for_behat',
                'required' => array('course', 'user')
            ),
            'criteria group pathways' => array(
                'datagenerator' => 'criteria_group_pathway_for_behat',
                'required' => array('competency', 'scale_value', 'criteria')
            ),
            'pathways' => array(
                'datagenerator' => 'pathway_for_behat',
                'required' => array('pathway', 'competency')
            ),
            'learning plan pathways' => array(
                'datagenerator' => 'learning_plan_pathway_for_behat',
                'required' => array('competency')
            ),
            'learning plan with competency value' => array(
                'datagenerator' => 'learning_plan_with_competency_value_for_behat',
                'required' => array('competency', 'plan', 'scale_value')
            ),
            'linked courses' => array(
                'datagenerator' => 'linked_course_for_behat',
                'required' => array('competency', 'course')
            ),
            'manual pathways' => array(
                'datagenerator' => 'manual_pathway_for_behat',
                'required' => array('competency', 'roles')
            ),
            'manual ratings' => array(
                'datagenerator' => 'manual_rating_for_behat',
                'required' => array('competency', 'subject_user', 'rater_user', 'role', 'scale_value')
            ),
            'achievement configuration' => array(
                'datagenerator' => 'achievement_configuration_for_behat',
                'required' => array('competency', 'aggregation_type')
            ),
            'perform rating pathways' => array(
                'datagenerator' => 'perform_rating_pathway_for_behat',
                'required' => array('competency')
            ),
        ),
        'totara_criteria' => array(
            'childcompetency' => array(
                'datagenerator' => 'childcompetency',
                'required' => array('idnumber', 'competency', 'number_required')
            ),
            'coursecompletion' => array(
                'datagenerator' => 'coursecompletion',
                'required' => array('idnumber', 'courses', 'number_required')
            ),
            'linkedcourses' => array(
                'datagenerator' => 'linkedcourses',
                'required' => array('idnumber', 'competency', 'number_required')
            ),
            'onactivate' => array(
                'datagenerator' => 'onactivate',
                'required' => array('idnumber', 'competency')
            ),
            'othercompetency' => array(
                'datagenerator' => 'othercompetency',
                'required' => array('idnumber', 'competency', 'number_required', 'competencies')
            ),
        ),
        'totara_evidence' => array(
            'evidence' => array(
                'datagenerator' => 'evidence_item_for_behat',
                'required' => array('name')
            ),
            'types' => array(
                'datagenerator' => 'evidence_type_for_behat',
                'required' => array('name')
            ),
            'type fields' => array(
                'datagenerator' => 'evidence_type_fields_for_behat',
                'required' => array('evidence_type', 'datatype', 'fullname', 'shortname')
            ),
            'users' => array(
                'datagenerator' => 'evidence_user_for_behat',
                'required' => array('username')
            ),
            'plan relations' => array(
                'datagenerator' => 'evidence_plan_relation_for_behat',
                'required' => array('evidence')
            ),
        ),
        'auth_approved' => array(
            'signups' => array(
                'datagenerator' => 'signup',
                'required' => array()
            )
        ),
        'tool_sitepolicy' => array(
            'draftpolicies' => array(
                'datagenerator' => 'draft_policy',
                    'required' => array(),
            ),
            'publishedpolicies' => array(
                'datagenerator' => 'published_policy',
                'required' => array(),
            ),
            'multiversionpolicies' => array(
                'datagenerator' => 'multiversion_policy',
                'required' => array(),
            )
        ),
        'tool_customlang' => array(
            'language customisation' => array(
                'datagenerator' => 'language_customisation_for_behat',
                'required' => array('id', 'string')
            )
        ),
        'mod_forum' => array(
            'post' => array(
                'age_data' => 'age_post'
            )
        ),
        'mod_glossary' => array(
            'entry' => array(
                'age_data' => 'age_entry'
            )
        ),
        'mod_lesson' => array(
            'timer' => array (
                'age_data' => 'wind_back_timer'
            )
        ),
        'mod_perform' => array(
            'subject instances' => array(
                'datagenerator' => 'subject_instance',
                'required' => array('activity_name', 'subject_username')
            ),
            'subject instances with single user manager-appraiser' => array(
                'datagenerator' => 'section_with_combined_manager_appraiser_for_behat',
                'required' => array('activity_name', 'subject_username', 'manager_appraiser_username')
            ),
            'activities' => array(
                'datagenerator' => 'activity_in_container',
                'required' => array('activity_name')
            ),
            'activity settings' => array(
                'datagenerator' => 'activity_settings',
                'required' => array('activity_name')
            ),
            'activity sections' => array(
                'datagenerator' => 'activity_section',
                'required' => array('activity_name', 'section_name')
            ),
            'activity tracks' => array(
                'datagenerator' => 'activity_track',
                'required' => array('activity_name', 'track_description')
            ),
            'external participants' => array(
                'datagenerator' => 'external_participant_instances',
                'required' => array('subject', 'fullname', 'email')
            ),
            'section relationships' => array(
                'datagenerator' => 'section_relationship_from_name',
                'required' => array('section_name', 'relationship')
            ),
            'section elements' => array(
                'datagenerator' => 'section_element_from_name',
                'required' => array('section_name', 'element_name')
            ),
            'track assignments' => array(
                'datagenerator' => 'track_assignment',
                'required' => array('track_description', 'assignment_type', 'assignment_name')
            ),
            'child elements' => array(
                'datagenerator' => 'child_element',
                'required' => array('parent_element'),
            ),
        ),
        'performelement_linked_review' => array(
            'activity with section and review element' => array(
                'datagenerator' => 'activity_with_section_and_review_element_for_behat',
                'required' => array('activity_name', 'section_title', 'content_type'),
            ),
            'participants in section' => array(
                'datagenerator' => 'participant_in_section',
                'required' => array('section'),
            ),
            'selected content' => array(
                'datagenerator' => 'content_selection',
                'required' => array('content_name', 'element', 'subject_user', 'selector_user'),
            ),
            'competency assignments' => array(
                'datagenerator' => 'competency_assignment',
                'required' => array('user'),
            ),
        ),
        'mod_quiz' => array(
            'responses' => array(
                'age_data' => 'age_quiz_responses'
            )
        ),
        'mod_contentmarketplace' => array(
            'content marketplace' => array(
                'datagenerator' => 'content_marketplace_instance',
                'required' => array('name', 'course', 'marketplace_component')
            )
        ),
        'contentmarketplace_linkedin' => array(
            'learning objects' => array(
                'datagenerator' => 'learning_object_for_behat',
                'required' => array('urn')
            ),
            'classifications' => array(
                'datagenerator' => 'classification_for_behat',
                'required' => array('urn')
            ),
            'classification relationships' => array(
                'datagenerator' => 'classification_relationship_for_behat',
                'required' => array('parent_urn', 'child_urn')
            ),
            'learning object classifications' => array(
                'datagenerator' => 'learning_object_classifications_for_behat',
                'required' => array('learning_object_urn', 'classification_urn')
            )
        ),
        'totara_message' => array(
            'alerts' => array(
                'datagenerator' => 'alert_from_params',
                'required' => array('fromuser', 'touser', 'description')
            )
        ),
        'totara_mobile' => array(
            'devices' => array(
                'age_data' => 'age_mobile_devices'
            )
        ),
        'totara_topic' => array(
            'topics' => array(
                'datagenerator' => 'topic_from_params',
                'required' => array('name')
            )
        ),
        'totara_playlist' => array(
            'playlists' => array(
                'datagenerator' => 'playlist_from_params',
                'required' => array('name', 'username', 'access')
            ),
            'playlist resources' => array(
                'datagenerator' => 'playlist_resouce_from_params',
                // `user` is the actor that we set for whoever going to add the resources
                // to the playlist.
                'required' => array('component', 'name', 'playlist', 'user')
            )
        ),
        'engage_article' => array(
            'articles' => array(
                'datagenerator' => 'article_from_params',
                'required' => array('name', 'content', 'username')
            )
        ),
        'engage_survey' => array(
            'surveys' => array(
                'datagenerator' => 'survey_from_params',
                'required' => array('question', 'username', 'access')
            )
        ),
        'container_workspace' => array(
            'workspaces' => array(
                'plugin' => 'container_workspace',
                'datagenerator' => 'workspace_from_params',
                'required' => array('name', 'summary')
            ),
            'owners' => array(
                'datagenerator' => 'workspace_owners',
                'required' => array('username', 'workspace'),
            ),
            'discussions' => array(
                'plugin' => 'container_workspace',
                'datagenerator' => 'discussions',
                'required' => array('username', 'workspace', 'content'),
            ),
        ),
        'ml_recommender' => array(
            'user recommendations' => array(
                'plugin' => 'ml_recommender',
                'datagenerator' => 'user_recommendation_from_params',
                'required' => array('username', 'component', 'name')
            ),
            'item recommendations' => array(
                'plugin' => 'ml_recommender',
                'datagenerator' => 'item_recommendation_from_params',
                'required' => array('component', 'name', 'target_name')
            ),
            'trending recommendations' => array(
                'plugin' => 'ml_recommender',
                'datagenerator' => 'trending_recommendation_from_params',
                'required' => array('component', 'name')
            ),
        ),
        'totara_comment' => array(
            'comments' => array(
                'plugin' => 'totara_comment',
                'datagenerator' => 'comment_from_params',
                'required' => array('component', 'name', 'username', 'area')
            )
        ),
        'totara_reportedcontent' => array(
            'resource reviews' => array(
                'plugin' => 'totara_reportedcontent',
                'datagenerator' => 'resource_review_from_params',
                'required' => array('component', 'name', 'username'),
            ),
            'discussion reviews' => array(
                'plugin' => 'totara_reportedcontent',
                'datagenerator' => 'discussion_review_from_params',
                'required' => array('component', 'username', 'discussion'),
            ),
            'comment reviews' => array(
                'plugin' => 'totara_reportedcontent',
                'datagenerator' => 'comment_review_from_params',
                'required' => array('component', 'area', 'name', 'username', 'comment'),
            ),
        ),
        'totara_oauth2' => array(
            'client provider' => array(
                'datagenerator' => 'client_provider_instance',
                'required' => array('name'),
            )
        ),
        'totara_api' => array(
            'api clients' => array(
                'datagenerator' => 'api_client_instance',
                'required' => array('name'),
            )
        ),
        'totara_useraction' => array(
            'scheduled rules' => array(
                'plugin' => 'totara_useraction',
                'datagenerator' => 'scheduled_rule_from_params',
                'required' => array('name'),
            ),
            'history entries' => array(
                'plugin' => 'totara_useraction',
                'datagenerator' => 'history_entry_from_params',
                'required' => array('user'),
                'switchids' => array(
                    'user' => 'userid',
                ),
            ),
        ),
    );

    /**
     * Creates the specified element. More info about available elements in https://help.totaralearning.com/display/DEV/Behat.
     *
     * @Given /^the following "(?P<element_string>(?:[^"]|\\")*)" exist in "([a-z0-9_]*)" plugin:$/
     *
     * @throws Exception
     * @throws PendingException
     * @param string    $elementname The name of the entity to add
     * @param string    $component The Frankenstyle name of the plugin
     * @param TableNode $data
     */
    public function the_following_exist_in_plugin($elementname, $component, TableNode $data) {
        \behat_hooks::set_step_readonly(false);

        if (empty(self::$componentelements[$component][$elementname])) {
            throw new PendingException($elementname . ' data generator is not implemented');
        }

        if (empty(self::$componentelements[$component][$elementname]['datagenerator'])) {
            throw new PendingException($elementname . ' datagenerator attribute not specified');
        }

        $helper = new totara_core_behat_helper_generator();
        $componentgenerator = testing_util::get_data_generator()->get_plugin_generator($component);

        $elementdatagenerator = self::$componentelements[$component][$elementname]['datagenerator'];
        $requiredfields = self::$componentelements[$component][$elementname]['required'];
        if (!empty(self::$componentelements[$component][$elementname]['switchids'])) {
            $switchids = self::$componentelements[$component][$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        if (method_exists($this, 'get_' . $element . '_id')) {
                            $id = $this->{'get_' . $element . '_id'}($elementdata[$element]);
                            unset($elementdata[$element]);
                            $elementdata[$field] = $id;
                        } else if ($helper->get_exists($elementdatagenerator)) {
                            $id = $helper->protected_get($elementdatagenerator, $elementdata[$element]);
                            unset($elementdata[$element]);
                            $elementdata[$field] = $id;
                        } else {
                            // Nothing to change.
                        }
                    }
                }
            }

            // Preprocess the entities that requires a special treatment.
            if (method_exists($this, 'preprocess_' . $elementdatagenerator)) {
                $elementdata = $this->{'preprocess_' . $elementdatagenerator}($elementdata);
            } else if ($helper->preprocess_exists($elementdatagenerator)) {
                $elementdata = $helper->protected_preprocess($elementdatagenerator, $elementdata);
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($componentgenerator, $methodname)) {
                // Using data generators directly.
                $componentgenerator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);

            } else if ($helper->preprocess_exists($elementdatagenerator)) {
                $helper->protected_process($elementdatagenerator, $elementdata);

            } else {
                throw new PendingException($elementname . ' data generator is not implemented');
            }
        }
    }

    /**
     * Age the specified element's data.
     *
     * @Given /^I age the "(?P<element_key>(?:[^"]|\\")*)" "(?P<element_name>(?:[^"]|\\")*)" in the "(?P<component>(?:[^"]|\\")*)" plugin "(?P<seconds_number>\d+)" seconds$/
     * @throws Exception
     * @throws PendingException
     * @param string $element_key The element key to age
     * @param string $element_name The element name to age
     * @param string $component The Frankenstyle name of the plugin
     * @param int $seconds to age data
     */
    public function i_age_the_data_x_seconds($elementkey, $elementname, $component, $seconds) {
        \behat_hooks::set_step_readonly(true);

        if (empty(self::$componentelements[$component][$elementname])) {
            throw new PendingException($elementname . ' data generator is not implemented');
        }

        if (empty(self::$componentelements[$component][$elementname]['age_data'])) {
            throw new PendingException($component . '.' . $elementname . ' data aging is not implemented');
        }

        $helper = new totara_core_behat_helper_generator();
        $componentgenerator = testing_util::get_data_generator()->get_plugin_generator($component);

        $methodname = self::$componentelements[$component][$elementname]['age_data'];
        if (method_exists($componentgenerator, $methodname)) {
            $componentgenerator->{$methodname}($elementkey, $seconds);
        } else {
            throw new PendingException($component . ' data aging method ' . $methodname . ' is not implemented');
        }
    }

    public function get_manager_id($username) {
        return $this->get_user_id($username);
    }

    public function get_user_id($username) {
        global $DB;

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }

        return $id;
    }

    public function get_program_id($shortname) {
        global $DB;
        return $DB->get_field('prog', 'id', array('shortname' => $shortname), MUST_EXIST);
    }

    public function get_org_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('org_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_organisation_id($idnumber) {
        global $DB;
        return $DB->get_field('org', 'id', array('idnumber' => $idnumber));
    }

    public function get_pos_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('pos_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_position_id($idnumber) {
        global $DB;
        return $DB->get_field('pos', 'id', array('idnumber' => $idnumber));
    }

    public function get_comp_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('comp_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_competency_id($idnumber) {
        global $DB;
        return $DB->get_field('comp', 'id', array('idnumber' => $idnumber));
    }

    public function get_goal_framework_id($idnumber) {
        global $DB;
        return $DB->get_field('goal_framework', 'id', array('idnumber' => $idnumber));
    }

    public function get_goal_id($idnumber) {
        global $DB;
        return $DB->get_field('goal', 'id', array('idnumber' => $idnumber));
    }

    /**
     * Gets the cohort id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_cohort_id($idnumber) {
        global $DB;

        if (!$id = $DB->get_field('cohort', 'id', array('idnumber' => $idnumber))) {
            throw new Exception('The specified cohort with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the course id from it's shortname.
     * @throws Exception
     * @param string $shortname
     * @return int
     */
    protected function get_course_id($shortname) {
        global $DB;

        if (!$id = $DB->get_field('course', 'id', array('shortname' => $shortname))) {
            throw new Exception('The specified course with shortname "' . $shortname . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the role id from it's shortname.
     * @throws Exception
     * @param string $roleshortname
     * @return int
     */
    protected function get_role_id($roleshortname) {
        global $DB;

        if (!$id = $DB->get_field('role', 'id', array('shortname' => $roleshortname))) {
            throw new Exception('The specified role with shortname "' . $roleshortname . '" does not exist');
        }

        return $id;
    }

    /**
     * Gets the category is from its idnumber
     * @throws Exception
     * @param string $categoryidnumber
     * @return int
     */
    protected function get_category_id($categoryidnumber) {
        global $DB;

        if (empty($categoryidnumber)) {
            // If empty, the data generator will provide a default value.
            return $categoryidnumber;
        }

        if (!$id = $DB->get_field('course_categories', 'id', array('idnumber' => $categoryidnumber))) {
            throw new Exception('The specified category with idnumber "' . $categoryidnumber . '" does not exist');
        }

        return $id;
    }
}
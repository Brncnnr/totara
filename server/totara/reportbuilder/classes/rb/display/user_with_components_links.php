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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

use totara_core\advanced_feature;

/**
 * Display class intended for showing a users name, icon and links to their learning components
 * To pass the correct data, first:
 *      $usednamefields = totara_get_all_user_name_fields_join($base, null, true);
 *      $allnamefields = totara_get_all_user_name_fields_join($base);
 * then your "field" param should be:
 *      $DB->sql_concat_join("' '", $usednamefields)
 * to allow sorting and filtering, and finally your extrafields should be:
 *      array_merge(array('id' => $base . '.id',
 *                        'picture' => $base . '.picture',
 *                        'imagealt' => $base . '.imagealt',
 *                        'email' => $base . '.email'),
 *                  $allnamefields)
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */
class user_with_components_links extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $OUTPUT, $USER;

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        // Process obsolete calls to this display function.
        if (isset($extrafields->userpic_picture)) {
            $picuser = new \stdClass();
            $picuser->id = $extrafields->user_id;
            $picuser->picture = $extrafields->userpic_picture;
            $picuser->imagealt = $extrafields->userpic_imagealt;
            $picuser->firstname = $extrafields->userpic_firstname;
            $picuser->firstnamephonetic = $extrafields->userpic_firstnamephonetic;
            $picuser->middlename = $extrafields->userpic_middlename;
            $picuser->lastname = $extrafields->userpic_lastname;
            $picuser->lastnamephonetic = $extrafields->userpic_lastnamephonetic;
            $picuser->alternatename = $extrafields->userpic_alternatename;
            $picuser->email = $extrafields->userpic_email;
            $extrafields = $picuser;
        }

        $userid = $extrafields->id;

        if ($isexport) {
            return user::display($value, $format, $row, $column, $report);
        }

        $usercontext = \context_user::instance($userid, MUST_EXIST);
        $show_profile_link = user_can_view_profile($extrafields, null);

        $user_pic = $OUTPUT->user_picture($extrafields, array('courseid' => SITEID, 'link' => $show_profile_link));

        $recordstr = get_string('records', 'rb_source_user');
        $requiredstr = get_string('required', 'rb_source_user');
        $planstr = get_string('plans', 'rb_source_user');
        $profilestr = get_string('profile', 'rb_source_user');
        $bookingstr = get_string('bookings', 'rb_source_user');
        $appraisalstr = get_string('appraisals', 'totara_appraisal');
        $feedback360str = get_string('feedback360_legacy', 'totara_feedback360');
        $goalstr = get_string('goalplural', 'totara_hierarchy');
        $competency_profile_str = get_string('competency_profile', 'totara_competency');
        $evidencestr = get_string('evidence', 'totara_evidence');
        $performance_data = get_string('user_components_performance_data', 'mod_perform');

        $rol_link = \html_writer::link(
            "{$CFG->wwwroot}/totara/plan/record/index.php?userid={$userid}", 
            $recordstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $recordstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $required_link = \html_writer::link(
            new \moodle_url('/totara/program/required.php', array('userid' => $userid)), $requiredstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $requiredstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );

        $plan_link = \html_writer::link(
            "{$CFG->wwwroot}/totara/plan/index.php?userid={$userid}", $planstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $planstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $profile_link = \html_writer::link(
            "{$CFG->wwwroot}/user/profile.php?id={$userid}", $profilestr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $profilestr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $booking_link = \html_writer::link(
            "{$CFG->wwwroot}/my/bookings.php?userid={$userid}", $bookingstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $bookingstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $appraisal_link = \html_writer::link(
            "{$CFG->wwwroot}/totara/appraisal/index.php?subjectid={$userid}", $appraisalstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $appraisalstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $feedback_link = \html_writer::link(
            "{$CFG->wwwroot}/totara/feedback360/index.php?userid={$userid}", $feedback360str, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $feedback360str,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $goal_link = \html_writer::link(
            "{$CFG->wwwroot}/totara/hierarchy/prefix/goal/mygoals.php?userid={$userid}", $goalstr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $goalstr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $competency_profile_link = \html_writer::link(
            new \moodle_url('/totara/competency/profile/index.php', ['user_id' => $userid]), $competency_profile_str, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $competency_profile_str,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $evidence_link = \html_writer::link(
            new \moodle_url('/totara/evidence/index.php', ['user_id' => $userid]), $evidencestr, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $evidencestr,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );
        $perform_response_reporting_link = \html_writer::link(
            new \moodle_url('/mod/perform/reporting/performance/user.php', ['subject_user_id' => $userid]), $performance_data, ['aria-label' => get_string(
                'user_components_accessible_name',
                'mod_perform',
                (object) [
                    'component' => $performance_data,
                    'user' => fullname($extrafields),
                ]
            )
            ]
        );

        $show_plan_link = advanced_feature::is_enabled('learningplans') && dp_can_view_users_plans($userid);

        $links = \html_writer::start_tag('ul');
        $links .= $show_plan_link ? \html_writer::tag('li', $plan_link) : '';
        $links .= $show_profile_link ? \html_writer::tag('li', $profile_link) : '';
        $links .= \html_writer::tag('li', $booking_link);
        $links .= \html_writer::tag('li', $rol_link);

        // Show link to managers, but not to temporary managers.
        $ismanager = \totara_job\job_assignment::is_managing($USER->id, $userid, null, false);
        if ($ismanager && advanced_feature::is_enabled('appraisals')) {
            $links .= \html_writer::tag('li', $appraisal_link);
        }

        if (advanced_feature::is_enabled('feedback360') && \feedback360::can_view_other_feedback360s($userid)) {
            $links .= \html_writer::tag('li', $feedback_link);
        }

        if (advanced_feature::is_enabled('goals')) {
            if (has_capability('totara/hierarchy:viewstaffcompanygoal', $usercontext, $USER->id) ||
                has_capability('totara/hierarchy:viewstaffpersonalgoal', $usercontext, $USER->id)) {
                $links .= \html_writer::tag('li', $goal_link);
            }
        }

        if ((advanced_feature::is_enabled('programs') || advanced_feature::is_enabled('certifications')) && prog_can_view_users_required_learning($userid)) {
            $links .= \html_writer::tag('li', $required_link);
        }

        if (advanced_feature::is_enabled('competency_assignment') &&
            class_exists('\totara_competency\helpers\capability_helper') &&
            \totara_competency\helpers\capability_helper::can_view_profile($userid)) {
            $links .= \html_writer::tag('li', $competency_profile_link);
        }

        if (advanced_feature::is_enabled('evidence') &&
            class_exists('\totara_evidence\models\helpers\evidence_item_capability_helper') &&
            \totara_evidence\models\helpers\evidence_item_capability_helper::for_user($userid)->can_view_list()) {
            $links .= \html_writer::tag('li', $evidence_link);
        }

        if (advanced_feature::is_enabled('performance_activities') &&
            class_exists(\mod_perform\util::class) &&
            \mod_perform\util::can_report_on_user($userid, $USER->id)) {
            $links .= \html_writer::tag('li', $perform_response_reporting_link);
        }

        $links .= \html_writer::end_tag('ul');

        if ($show_profile_link) {
            $user_tag = \html_writer::link(new \moodle_url("/user/profile.php", array('id' => $userid)),
            fullname($extrafields), array('class' => 'name'));
        } else {
            $user_tag = \html_writer::span(fullname($extrafields), 'name');
        }

        $return = $user_pic . $user_tag . $links;

        return $return;
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}

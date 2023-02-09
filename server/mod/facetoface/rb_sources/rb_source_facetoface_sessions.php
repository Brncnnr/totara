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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

use \mod_facetoface\signup\state\booked;
use \mod_facetoface\signup\state\declined;
use \mod_facetoface\signup\state\waitlisted;
use \mod_facetoface\signup\state\user_cancelled;
use \mod_facetoface\signup\state\event_cancelled;
global $CFG;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

/**
 * Seminar Sign-ups
 */
class rb_source_facetoface_sessions extends rb_facetoface_base_source {

    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_joins;
    use \mod_facetoface\rb\traits\post_config;
    use \mod_facetoface\rb\traits\rooms;
    use \totara_cohort\rb\source\report_trait;
    use \mod_facetoface\rb\traits\facilitator;
    use \mod_facetoface\rb\traits\assets;
    use \mod_facetoface\rb\traits\deprecated_sessions_source;

    /** @var string $returnpage name */
    private $returnpage = 'view';

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{facetoface_signups}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->requiredjoins = $this->define_requiredjoins();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_sessions');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_facetoface_sessions');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_facetoface_sessions');
        $this->usedcomponents[] = 'totara_cohort';
        $this->add_customfields();

        // Caching is disabled because visibility needs to be calculated using live data that cannot be cached.
        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    // Methods for defining contents of source

    protected function define_joinlist() {
        global $CFG, $DB;
        require_once($CFG->dirroot .'/mod/facetoface/lib.php');

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'facetoface']);

        // joinlist for this source
        $joinlist = array(
            new rb_join(
                'sessions',
                'LEFT',
                '{facetoface_sessions}',
                'sessions.id = base.sessionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'facetoface',
                'LEFT',
                '{facetoface}',
                'facetoface.id = sessions.facetoface',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'sessions'
            ),
            new rb_join(
                'cmdl',
                'LEFT',
                '{course_modules}',
                "(cmdl.module = {$moduleid} AND cmdl.instance = facetoface.id)",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'facetoface'
            ),
            new rb_join(
                'sessiondate',
                'LEFT',
                '{facetoface_sessions_dates}',
                '(sessiondate.sessionid = sessions.id)',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'sessions'
            ),
            new rb_join(
                'status',
                'LEFT',
                '{facetoface_signups_status}',
                '(status.signupid = base.id AND status.superceded = 0)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'status_dates',
                'LEFT',
                '{facetoface_signups_dates_status}',
                '(status_dates.signupid = base.id AND status_dates.superceded = 0)',
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            ),
            new rb_join(
                'attendees',
                'LEFT',
                // subquery as table
                "(SELECT su.sessionid, count(ss.id) AS number
                    FROM {facetoface_signups} su
                    JOIN {facetoface_signups_status} ss
                        ON su.id = ss.signupid
                    WHERE ss.superceded=0 AND ss.statuscode >= 50
                    GROUP BY su.sessionid)",
                'attendees.sessionid = base.sessionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'cancellationstatus',
                'LEFT',
                '{facetoface_signups_status}',
                '(cancellationstatus.signupid = base.id AND
                    cancellationstatus.superceded = 0 AND
                cancellationstatus.statuscode IN (' .user_cancelled::get_code(). ', ' .event_cancelled::get_code(). ', ' .declined::get_code(). '))',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'bookedby',
                'LEFT',
                '{user}',
                'bookedby.id = CASE WHEN base.bookedby = 0 OR base.bookedby IS NULL THEN base.userid ELSE base.bookedby END',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'creator',
                'LEFT',
                '{user}',
                'status.createdby = creator.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'status'
            ),
            new rb_join(
                'pos',
                'LEFT',
                '{pos}',
                'pos.id = selected_job_assignment.positionid',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'selected_job_assignment'
            ),
            new rb_join(
                'approver',
                'LEFT',
                // Only want the last approval record
                "(SELECT status.signupid, status.createdby as approverid, status.timecreated as approvaltime
                    FROM {facetoface_signups_status} status
                    JOIN (SELECT signupid, max(timecreated) as approvaltime
                            FROM {facetoface_signups_status}
                           WHERE statuscode IN (" . waitlisted::get_code() . ", " . booked::get_code() . ")
                        GROUP BY signupid) lastapproval
                      ON status.signupid = lastapproval.signupid
                     AND status.timecreated = lastapproval.approvaltime
                  WHERE statuscode IN (" . waitlisted::get_code() . ", " . booked::get_code() . "))",
                '(base.id = approver.signupid AND approver.approverid != base.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'selected_job_assignment',
                'LEFT',
                '{job_assignment}',
                'selected_job_assignment.id = base.jobassignmentid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        $this->add_rooms_to_join_list($joinlist, 'sessiondate');
        $this->add_assets_to_join_list($joinlist, 'sessiondate');
        $this->add_facilitators_to_join_list($joinlist, 'sessiondate');

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'facetoface', 'course', 'INNER');
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        // requires the course join
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'facetoface', 'course');
        $this->add_core_tag_tables('core', 'course_modules', $joinlist, 'cmdl', 'id');

        $this->add_facetoface_session_roles_to_joinlist($joinlist);

        $this->add_totara_cohort_course_tables($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB, $PAGE;

        $usernamefieldscreator = totara_get_all_user_name_fields_join('creator');
        $usernamefieldsbooked  = totara_get_all_user_name_fields_join('bookedby', null, true);
        $allnamefields         = totara_get_all_user_name_fields_join('bookedby');

        $columnoptions = array(
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'sessions.details',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'session\'',
                        'component' => '\'mod_facetoface\'',
                        'fileid' => 'sessions.id',
                        'context' => '\'context_module\'',
                        'recordid' => 'sessions.facetoface'
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'signupperiod', // Sign-up Date/Time Period
                get_string('signupperiod', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'outputformat' => 'text',
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_dates_period',
                    'extrafields' => array(
                        'finishdate' => 'sessions.registrationtimefinish',
                    )
                )
            ),
            new rb_column_option(
                'session',
                'signupstartdate', // Sign-up Start Date/Time
                get_string('signupstartdate', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'event',
                'sessionattendance',
                get_string('sessionattendance', 'rb_source_facetoface_sessions'),
                'status_dates.attendancecode',
                array(
                    'joins' => 'status_dates',
                    'displayfunc' => 'attendance_status',
                )
            ),
            new rb_column_option(
                'session',
                'signupenddate', // Sign-up End Date/Time
                get_string('signupenddate', 'rb_source_facetoface_sessions'),
                'sessions.registrationtimefinish',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'status',
                'archived', // Sign-up Archived
                get_string('signuparchived', 'rb_source_facetoface_sessions'),
                'archived',
                array(
                    'dbdatatype' => 'text',
                    'displayfunc' => 'yes_or_no',
                )
            ),
            new rb_column_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_sessions'),
                'status.statuscode',
                array(
                    'joins' => 'status',
                    'displayfunc' => 'signup_status',
                )
            ),
            new rb_column_option(
                'facetoface',
                'name',
                get_string('ftfname', 'rb_source_facetoface_sessions'),
                'facetoface.name',
                array('joins' => 'facetoface',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'facetoface',
                'namelink',
                get_string('ftfnamelink', 'rb_source_facetoface_sessions'),
                "facetoface.name",
                array(
                    'joins' => array('facetoface','sessions'),
                    'displayfunc' => 'seminar_name_link',
                    'defaultheading' => get_string('ftfname', 'rb_source_facetoface_sessions'),
                    'extrafields' => array('activity_id' => 'sessions.facetoface'),
                )
            ),
            new rb_column_option(
                'status',
                'grade',
                get_string('eventgrade', 'rb_source_facetoface_sessions'),
                'status.grade',
                [
                    'joins' => 'status',
                    'displayfunc' => 'session_grade',
                    'outputformat' => 'float',
                    'extrafields' => [
                        'courseid' => 'facetoface.course'
                    ]
                ]
            ),
            new rb_column_option(
                'status',
                'createdby',
                get_string('createdby', 'rb_source_facetoface_sessions'),
                $DB->sql_concat_join("' '", $usernamefieldscreator),
                array(
                    'joins' => 'creator',
                    'displayfunc' => 'user_link',
                    'extrafields' => array_merge(
                        ['id' => 'creator.id', 'deleted' => 'creator.deleted'],
                        $usernamefieldscreator
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'cancellationdate', // Cancellation Date/Time
                get_string('cancellationdate', 'rb_source_facetoface_sessions'),
                'cancellationstatus.timecreated',
                array(
                    'joins' => 'cancellationstatus',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_sessions'),
                "CASE WHEN bookedby.id IS NULL THEN NULL ELSE " . $DB->sql_concat_join("' '", $usernamefieldsbooked) . " END",
                array(
                    'joins' => 'bookedby',
                    'displayfunc' => 'user_link',
                    'extrafields' => array_merge(
                        ['id' => 'bookedby.id', 'deleted' => 'bookedby.deleted'],
                        $allnamefields
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'positionname',
                get_string('selectedposition', 'mod_facetoface'),
                'pos.fullname',
                array('joins' => 'pos',
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'session',
                'jobassignmentnameedit',
                get_string('selectedjobassignmentedit', 'mod_facetoface'),
                'selected_job_assignment.fullname',
                array(
                    'columngenerator' => 'job_assignment_edit',
                    'displayfunc' => 'f2f_job_assignment_edit')
                ),
            new rb_column_option(
                'status',
                'timecreated', // Date/Time of sign-up
                get_string('datetimeofsignup', 'rb_source_facetoface_sessions'),
                '(SELECT MAX(timecreated)
                    FROM {facetoface_signups_status}
                    WHERE signupid = base.id AND statuscode IN ('.booked::get_code().', '.waitlisted::get_code().'))',
                array(
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp',
                )
            ),
            new rb_column_option(
                'approver',
                'approvername',
                get_string('approvername', 'mod_facetoface'),
                'approver.approverid',
                array('joins' => 'approver',
                      'displayfunc' => 'f2f_approver_name')
            ),
            new rb_column_option(
                'approver',
                'approveremail',
                get_string('approveremail', 'mod_facetoface'),
                'approver.approverid',
                array('joins' => 'approver',
                      'displayfunc' => 'f2f_approver_email')
            ),
            new rb_column_option(
                'approver',
                'approvaltime',
                get_string('approverdatetime', 'mod_facetoface'),
                'approver.approvaltime',
                array(
                    'joins' => 'approver',
                    'displayfunc' => 'event_date',
                    'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'session',
                'cancelledstatus',
                get_string('cancelledstatus', 'mod_facetoface'),
                'sessions.cancelledstatus',
                array(
                    'displayfunc' => 'f2f_session_cancelled_status',
                    'joins' => 'sessions',
                    'dbdatatype' => 'integer'
                )
            ),
            new rb_column_option(
                'session',
                'approvallink',
                get_string('approvalrequest', 'rb_source_facetoface_sessions'),
                'sessions.id',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'manage_approval_link',
                    'defaultheading' => get_string('approvalrequest', 'rb_source_facetoface_sessions'),
                    'noexport' => true,
                    'nosort' => true
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_sessions'),
                'sessions.normalcost',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'session',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_sessions'),
                    'sessions.discountcost',
                    array(
                        'joins' => 'sessions',
                        'dbdatatype' => 'char',
                        'outputformat' => 'text',
                        'displayfunc' => 'format_string'
                    )
                );
                $columnoptions[] = new rb_column_option(
                    'session',
                    'discountcode',
                    get_string('discountcode', 'rb_source_facetoface_sessions'),
                    'base.discountcode',
                    array(
                        'dbdatatype' => 'text',
                        'outputformat' => 'text',
                        'displayfunc' => 'format_string'
                    )
                );
            }
        }
        if (has_any_capability(array('mod/facetoface:addattendees', 'mod/facetoface:removeattendees'), $PAGE->context)) {
            $columnoptions[] = new rb_column_option(
                'session',
                'waitlist_checkbox',
                get_string('selectwithdot', 'mod_facetoface'),
                'NULL',
                array(
                    'displayfunc' => 'waitlist_checkbox',
                    'extrafields' => array('userid' => 'base.userid'),
                    'customheading' => true,
                    'nosort' => true,
                    'noexport' => true
                )
            );
        }

        // include some standard columns
        $this->add_session_common_to_columns($columnoptions, 'sessiondate');
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_core_tag_columns('core', 'course_modules', $columnoptions);
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_assets_fields_to_columns($columnoptions, 'asset', false);
        $this->add_rooms_fields_to_columns($columnoptions, 'room', false, 'base');
        $this->add_facilitators_fields_to_columns($columnoptions, 'facilitator', false);
        $this->add_totara_cohort_course_columns($columnoptions);

        // include concatenated facilitator, room and asset name columns
        $columnoptions[] = new rb_column_option(
            'asset',
            'asset_list',
            get_string('asset_list', 'rb_source_facetoface_sessions'),
            "sessiondate.id",
            array(
                'displayfunc' => 'asset_list',
                'dbdatatype' => 'string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'facilitator',
            'facilitator_list',
            get_string('facilitator_list', 'rb_source_facetoface_sessions'),
            "sessiondate.id",
            array(
                'displayfunc' => 'facilitator_list',
                'dbdatatype' => 'string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'room',
            'room_list',
            get_string('room_list', 'rb_source_facetoface_sessions'),
            "sessiondate.id",
            array(
                'displayfunc' => 'room_list',
                'dbdatatype' => 'string'
            )
        );

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_sessions'),
                'multicheck',
                array(
                    'selectfunc' => 'session_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'status',
                'grade',
                get_string('eventgrade', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'date',
                'timestart',
                get_string('sessstarttime', 'rb_source_facetoface_sessions'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'date',
                'timefinish',
                get_string('sessfinishtime', 'rb_source_facetoface_sessions'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'session',
                'sessionid',
                get_string('sessionid', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'session',
                'capacity',
                get_string('sesscapacity', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'reserved',
                get_string('reserved', 'rb_source_facetoface_sessions'),
                'select',
                array(
                     'selectchoices' => array(
                         '0' => get_string('reserved', 'rb_source_facetoface_sessions'),
                     )
                ),
                'base.userid'
            ),
            new rb_filter_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'room',
                'name',
                get_string('roomname', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'room',
                'capacity',
                get_string('roomcapacity', 'rb_source_facetoface_sessions'),
                'number'
            ),
            new rb_filter_option(
                'room',
                'description',
                get_string('roomdescription', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'status',
                'createdby',
                get_string('createdby', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'cancelledstatus',
                get_string('cancelledstatus', 'facetoface'),
                'select',
                array(
                    'selectfunc' => 'cancel_status',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'event',
                'sessionattendance',
                get_string('sessionattendance', 'rb_source_facetoface_sessions'),
                'multicheck',
                array(
                    'selectfunc' => 'event_attendance_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $filteroptions[] = new rb_filter_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_sessions'),
                'text'
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_sessions'),
                    'text'
                );
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcode',
                    get_string('discountcode', 'rb_source_facetoface_sessions'),
                    'text'
                );
            }
        }

        // include some standard filters
        $this->add_session_common_to_filters($filteroptions);
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_core_tag_filters('core', 'course_modules', $filteroptions);
        // add session role fields to filters
        $this->add_facetoface_session_role_fields_to_filters($filteroptions);

        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    public function rb_filter_cancel_status() {
        $selectchoices = array(
            '1' => get_string('cancelled', 'rb_source_facetoface_sessions')
        );

        return $selectchoices;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('thedate', 'rb_source_facetoface_sessions'),
            'sessiondate.timefinish',
            'sessiondate'
        );
        $contentoptions[] = new rb_content_option(
            'session_roles',
            get_string('sessionroles', 'rb_source_facetoface_sessions'),
            'base.sessionid'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',         // parameter name
                'base.userid'     // field
            ),
            new rb_param_option(
                'courseid',
                'course.id',
                'course'
            ),
            new rb_param_option(
                'status',
                'status.statuscode',
                'status'
            ),
            new rb_param_option(
                'sessionid',
                'base.sessionid'
            ),
            new rb_param_option(
                'userdeleted',
                'auser.deleted',
                'auser'
            )
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'date',
                'value' => 'sessionstartdate',
            ),
            array(
                'type' => 'session',
                'value' => 'sessionid'
            ),
            array(
                'type' => 'session',
                'value' => 'approvallink'
            )
        );

        return $defaultcolumns;
    }

    /**
     * Required Columns.
     */
    protected function define_requiredcolumns() {
        return [];
    }

    /**
     * Required Joins.
     */
    protected function define_requiredjoins() {
        $requiredjoins = [];
        $this->add_audiencevisibility_joins($requiredjoins);

        return $requiredjoins;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'status',
                'value' => 'statuscode',
                'advanced' => 1,
            ),
            array(
                'type' => 'date',
                'value' => 'sessionstartdate',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }

    protected function add_customfields() {
        $this->columnoptions[] = new rb_column_option(
            'facetoface_signup',
            'allsignupcustomfields',
            get_string('allsignupcustomfields', 'rb_source_facetoface_sessions'),
            'facetofacesignupid',
            array(
                'columngenerator' => 'allcustomfieldssignupmanage',
            )
        );
        $this->columnoptions[] = new rb_column_option(
            'facetoface_cancellation',
            'allcancellationcustomfields',
            get_string('allcancellationcustomfields', 'rb_source_facetoface_sessions'),
            'facetofacecancellationid',
            array(
                'columngenerator' => 'allcancellationcustomfieldsmanage'
            )
        );
        $this->add_totara_customfield_component('facetoface_session', 'sessions', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_signup', 'base', 'facetofacesignupid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_cancellation', 'base', 'facetofacecancellationid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_sessioncancel', 'sessions', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_room', 'room', 'facetofaceroomid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_asset', 'asset', 'facetofaceassetid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    /**
     * Add control to manage signup customfields when user have rights to do so.
     *
     * @param rb_column_option $columnoption should have public string property "type" which value is the type of customfields to show
     * @param bool $hidden should all these columns be hidden
     * @return array of rb_column
     */
    public function rb_cols_generator_allcustomfieldssignupmanage(rb_column_option $columnoption, $hidden): array {

        $columnargs = [
            'type' => 'facetoface_signup_manage',
            'displayfunc' => 'f2f_all_signup_customfields_manage'
        ];
        return $this->rb_cols_generator_allcustomfieldsmanage($columnoption, $hidden, $columnargs);
    }

    /**
     * Add control to manage user cancellation customfields when user have rights to do so.
     *
     * @param rb_column_option $columnoption should have public string property "type" which value is the type of customfields to show
     * @param bool $hidden should all these columns be hidden
     * @return array of rb_column
     */
    public function rb_cols_generator_allcancellationcustomfieldsmanage(rb_column_option $columnoption, $hidden): array {

        $columnargs = [
            'type' => 'facetoface_cancellation_manage',
            'displayfunc' => 'user_cancellation_customfields_manage'
        ];
        return $this->rb_cols_generator_allcustomfieldsmanage($columnoption, $hidden, $columnargs);
    }

    /**
     * Add control to manage signup/user cancellation/etc customfields when user have rights to do so.
     *
     * @param rb_column_option $columnoption should have public string property "type" which value is the type of customfields to show
     * @param bool $hidden should all these columns be hidden
     * @param array $columnargs
     * @return array of rb_column
     */
    private function rb_cols_generator_allcustomfieldsmanage(rb_column_option $columnoption, $hidden, array $columnargs): array {
        global $PAGE;

        $results = $this->rb_cols_generator_allcustomfields($columnoption, $hidden);

        if (empty($results)) {
            // No money no honey.
            return $results;
        }

        $extrafields = [
            'sessionid' => 'sessions.id',
            'userid' => 'base.userid',
        ];

        if (has_capability('mod/facetoface:manageattendeesnote', $PAGE->context)) {
            // Only include the column manage attendee's note, if the current $user in session does have the permission
            // to perform the action. Otherwise, the report will be ending up with an empty columns.
            $results[] = new rb_column(
                $columnargs['type'],
                'custom_field_edit_all',
                get_string('actions', 'mod_facetoface'),
                'NULL',
                [
                    'displayfunc' => $columnargs['displayfunc'],
                    'noexport' => true,
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'style' => null,
                    'class' => null,
                    'extrafields' => $extrafields,
                    'nosort' => true,
                ]
            );
        }
        return $results;
    }

    /**
     * Position name column that will be displayed only when position select settings are enabled
     *
     * @param rb_column_option $columnoption Column settings configured by user
     * @param bool $hidden should this column always be hidden
     * @return array
     */
    public function rb_cols_generator_job_assignment_edit(rb_column_option $columnoption, $hidden) {
        $result = array();

        $selectjobassignmentonsignupglobal = get_config(null, 'facetoface_selectjobassignmentonsignupglobal');
        if ($selectjobassignmentonsignupglobal) {
            $result[] = new rb_column(
                'session',
                'positionnameedit',
                format_text($columnoption->name),
                'selected_job_assignment.fullname',
                array(
                    'joins' => array('selected_job_assignment','pos'),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'f2f_job_assignment_edit',
                    'hidden' => $hidden,
                    'extrafields' => array(
                        'jobassignmentname' => 'selected_job_assignment.fullname',
                        'jobassignmentid' => 'selected_job_assignment.id',
                        'positionname' => 'pos.fullname',
                        'userid' => 'base.userid',
                        'courseid' => 'facetoface.course',
                        'sessionid' => 'sessions.id',
                        'facetofaceid' => 'facetoface.id')
                    )
            );
        }
        return $result;
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_session_status_list() {

        $output = array();
        $states = \mod_facetoface\signup\state\state::get_all_states();
        foreach ($states as $state) {
            $code = $state::get_code();
            $output[$code] = $state::get_string();
        }

        // show most completed option first in pulldown
        return array_reverse($output, true);

    }

    function rb_filter_event_attendance_list() {
        return \mod_facetoface\attendees_helper::get_status();
    }

    function rb_filter_coursedelivery_list() {
        $coursedelivery = array();
        $coursedelivery[0] = get_string('no');
        $coursedelivery[1] = get_string('yes');
        return $coursedelivery;
    }

    public function post_config(reportbuilder $report) {
        $this->add_audiencevisibility_config($report);
    }

    /**
     * Set extra params: customfield, add and/or remove attendees capabilities.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {

        if (isset($report->embedobj->returnpage)) {
            $this->returnpage = $report->embedobj->returnpage;
        }
    }

    /**
     * Where to return when user customfield note is updated.
     *
     * @return string
     */
    public function get_return_page(): string {
        return $this->returnpage;
    }
} // end of rb_source_facetoface_sessions class


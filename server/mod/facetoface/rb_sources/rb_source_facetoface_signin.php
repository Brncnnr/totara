<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Lee Campbell <lee@learningpool.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

/**
 * Seminar Sign-in Sheet
 */
class rb_source_facetoface_signin extends rb_facetoface_base_source {

    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_joins;
    use \mod_facetoface\rb\traits\post_config;
    use \mod_facetoface\rb\traits\rooms;
    use \totara_cohort\rb\source\report_trait;
    use \mod_facetoface\rb\traits\deprecated_signin_source;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
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
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_signin');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_facetoface_signin');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_facetoface_signin');
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

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Define the joins available for this report.
     *
     * @return array
     */
    protected function define_joinlist() {
        global $CFG, $DB;
        require_once($CFG->dirroot .'/mod/facetoface/lib.php');

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'facetoface']);

        $joinlist = array(
            new rb_join(
                'sessions',
                'INNER',
                '{facetoface_sessions}',
                'sessions.id = base.sessionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'facetoface',
                'INNER',
                '{facetoface}',
                'facetoface.id = sessions.facetoface',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'sessions'
            ),
            new rb_join(
                'cmdl',
                'INNER',
                '{course_modules}',
                "(cmdl.module = {$moduleid} AND cmdl.instance = facetoface.id)",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'facetoface'
            ),
            new rb_join(
                'sessiondate',
                'LEFT',
                '{facetoface_sessions_dates}',
                '(sessiondate.sessionid = base.sessionid)',
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
                'attendees',
                'LEFT',
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
                'bookedby',
                'LEFT',
                '{user}',
                'bookedby.id = CASE WHEN base.bookedby = 0 OR base.bookedby IS NULL THEN base.userid ELSE base.bookedby END',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'eventdateinfo',
                'LEFT',
                '(  SELECT  sd.sessionid,
                            sd.eventstart,
                            sd.eventfinish,
                            tzstart.sessiontimezone AS tzstart,
                            tzfinish.sessiontimezone AS tzfinish
                    FROM (
                            SELECT   sessionid,
                                     MIN(timestart) AS eventstart,
                                     MAX(timefinish) AS eventfinish
                            FROM     {facetoface_sessions_dates}
                            GROUP BY sessionid
                         ) sd
                    INNER JOIN {facetoface_sessions_dates} tzstart
                        ON sd.eventstart = tzstart.timestart AND sd.sessionid = tzstart.sessionid
                    INNER JOIN {facetoface_sessions_dates} tzfinish
                        ON sd.eventfinish = tzfinish.timefinish AND sd.sessionid = tzfinish.sessionid )',
                "eventdateinfo.sessionid = base.sessionid",
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            ),
        );

        $this->add_rooms_to_join_list($joinlist, 'sessiondate');

        // Include some standard joins.
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'facetoface', 'course', 'INNER');
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'facetoface', 'course');
        $this->add_core_tag_tables('core', 'course_modules', $joinlist, 'cmdl', 'id');
        $this->add_facetoface_session_roles_to_joinlist($joinlist);
        $this->add_totara_cohort_course_tables($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    /**
     * Define the column options available for this report.
     *
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;
        $usernamefieldsbooked  = totara_get_all_user_name_fields_join('bookedby', null, true);
        $allnamefields         = totara_get_all_user_name_fields_join('bookedby');

        $columnoptions = array(
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_signin'),
                'sessions.details',
                array(
                    'joins' => 'sessions',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'session\'',
                        'component' => '\'mod_facetoface\'',
                        'fileid' => 'sessions.id',
                        'context' => '\'context_module\'',
                        'recordid' => 'sessions.facetoface'
                    ),
                    'dbdatatype' => 'text',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_signin'),
                'status.statuscode',
                array(
                    'joins' => 'status',
                    'displayfunc' => 'signup_status',
                )
            ),
            new rb_column_option(
                'facetoface',
                'name',
                get_string('f2fname', 'rb_source_facetoface_signin'),
                'facetoface.name',
                array('joins' => 'facetoface',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'facetoface',
                'namelink',
                get_string('f2fnamelink', 'rb_source_facetoface_signin'),
                "facetoface.name",
                array(
                    'joins' => array('facetoface','sessions'),
                    'displayfunc' => 'seminar_name_link',
                    'defaultheading' => get_string('f2fname', 'rb_source_facetoface_signin'),
                    'extrafields' => array('activity_id' => 'sessions.facetoface'),
                )
            ),
            new rb_column_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_signin'),
                "CASE WHEN bookedby.id IS NULL THEN NULL ELSE " . $DB->sql_concat_join("' '", $usernamefieldsbooked) . " END",
                array(
                    'joins' => 'bookedby',
                    'displayfunc' => 'f2f_booked_by_link',
                    'extrafields' => array_merge(
                        ['id' => 'bookedby.id', 'deleted' => 'bookedby.deleted'],
                        $allnamefields
                    )
                )
            ),
            new rb_column_option(
                'user_signups',
                'signature',
                get_string('signature', 'mod_facetoface'),
                'base.id',
                array(
                    'displayfunc' => 'f2f_signature'
                )
            ),
            new rb_column_option(
                'session',
                'eventstartdate',
                get_string('eventstartdatetime', 'rb_source_facetoface_events'),
                "eventdateinfo.eventstart",
                array(
                    'joins' => array('eventdateinfo'),
                    'displayfunc' => 'event_date',
                    'extrafields' => array('timezone' => 'eventdateinfo.tzstart'),
                    'dbdatatype' => 'timestamp',
                )
            ),
            new rb_column_option(
                'session',
                'eventfinishdate',
                get_string('eventfinishdatetime', 'rb_source_facetoface_events'),
                "eventdateinfo.eventfinish",
                array(
                    'joins' => array('eventdateinfo'),
                    'displayfunc' => 'event_date',
                    'extrafields' => array('timezone' => 'eventdateinfo.tzfinish'),
                    'dbdatatype' => 'timestamp',
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_signin'),
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
                    get_string('discountcost', 'rb_source_facetoface_signin'),
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
                    get_string('discountcode', 'rb_source_facetoface_signin'),
                    'base.discountcode',
                    array(
                        'dbdatatype' => 'text',
                        'outputformat' => 'text',
                        'displayfunc' => 'format_string'
                    )
                );
            }
        }

        // Include some standard columns.
        $this->add_session_common_to_columns($columnoptions, 'sessiondate');
        $this->add_rooms_fields_to_columns($columnoptions, 'room', false, 'sessiondate');
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_core_tag_columns('core', 'course_modules', $columnoptions);
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Define the filter options available for this report.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'status',
                'statuscode',
                get_string('status', 'rb_source_facetoface_signin'),
                'select',
                array(
                    'selectfunc' => 'session_status_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'date',
                'timestart',
                get_string('sessstart', 'rb_source_facetoface_signin'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'date',
                'timefinish',
                get_string('sessfinish', 'rb_source_facetoface_signin'),
                'date',
                array('includetime' => true)
            ),
            new rb_filter_option(
                'session',
                'capacity',
                get_string('sesscapacity', 'rb_source_facetoface_signin'),
                'number'
            ),
            new rb_filter_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_signin'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'bookedby',
                get_string('bookedby', 'rb_source_facetoface_signin'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'reserved',
                get_string('reserved', 'rb_source_facetoface_signin'),
                'select',
                array(
                    'selectchoices' => array(
                        '0' => get_string('reserved', 'rb_source_facetoface_signin'),
                    )
                ),
                'base.userid'
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $filteroptions[] = new rb_filter_option(
                'session',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_signin'),
                'text'
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_signin'),
                    'text'
                );
                $filteroptions[] = new rb_filter_option(
                    'session',
                    'discountcode',
                    get_string('discountcode', 'rb_source_facetoface_signin'),
                    'text'
                );
            }
        }

        // Include some standard filters.
        $this->add_session_common_to_filters($filteroptions);
        $this->add_rooms_fields_to_filters($filteroptions);
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_core_tag_filters('core', 'course_modules', $filteroptions);
        $this->add_facetoface_session_role_fields_to_filters($filteroptions);
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('thedate', 'rb_source_facetoface_signin'),
            'sessiondate.timestart',
            'sessiondate'
        );

        return $contentoptions;
    }

    /**
     * Define the available param options for this report.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid'
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
                'facetofaceid',
                'sessions.facetoface',
                'sessions'
            ),
            new rb_param_option(
                'sessionid',
                'base.sessionid'
            ),
            new rb_param_option(
                'sessiondateid',
                'sessiondate.id',
                'sessiondate'
            ),
            new rb_param_option(
                'hasbooked',
                'CASE WHEN status.statuscode >= 70 THEN 1 ELSE 0 END',
                'status'
            ),
            new rb_param_option(
                'userdeleted',
                'auser.deleted',
                'auser'
            )
        );

        return $paramoptions;
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'session',
                'value' => 'sessionid',
            ),
            array(
                'type' => 'session',
                'value' => 'eventstartdate',
            ),
            array(
                'type' => 'session',
                'value' => 'eventfinishdate',
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'user_signups',
                'value' => 'signature',
            )
        );

        return $defaultcolumns;
    }

    /**
     * Joins required for totara_visibility_where() to function correctly in post_config.
     *
     * @return array
     */
    protected function define_requiredjoins() {
        $requiredjoins = [];
        $this->add_audiencevisibility_joins($requiredjoins);

        return $requiredjoins;
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
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

    /**
     * Define the custom fields for this report.
     *
     * @return void
     */
    protected function add_customfields() {
        $this->add_totara_customfield_component('facetoface_session', 'sessions', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_signup', 'base', 'facetofacesignupid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    /**
     * Filter option for session status list.
     *
     * @return array
     */
    function rb_filter_session_status_list() {

        $output = array();
        $states = \mod_facetoface\signup\state\state::get_all_states();
        foreach ($states as $state) {
            $code = $state::get_code();
            $output[$code] = $state::get_string();
        }

        // Show most completed option first in pulldown.
        return array_reverse($output, true);

    }

    /**
     * Filter option for course delivery list.
     *
     * @return array
     */
    function rb_filter_coursedelivery_list() {
        $coursedelivery = array();
        $coursedelivery[0] = get_string('no');
        $coursedelivery[1] = get_string('yes');
        return $coursedelivery;
    }

    /**
     * Report post config operations.
     *
     * @param reportbuilder $report
     */
    public function post_config(reportbuilder $report) {
        $this->add_audiencevisibility_config($report);
    }

    /**
     * Allows report source to override page header in reportbuilder exports.
     *
     * @param reportbuilder $report
     * @param string $format 'html', 'text', 'excel', 'ods', 'csv' or 'pdf'
     * @return mixed|null must be possible to cast to string[][]
     */
    public function get_custom_export_header(reportbuilder $report, $format) {
        global $DB;
        $sessiondateid = $report->get_param_value('sessiondateid');
        if (!$sessiondateid) {
            return null;
        }
        $sessiondate = $DB->get_record('facetoface_sessions_dates', array('id' => $sessiondateid));
        if (!$sessiondate) {
            return null;
        }

        $seminarevent = \mod_facetoface\seminar_event::seek($sessiondate->sessionid);
        if (!$seminarevent->exists()) {
            return null;
        }

        $helper = new \mod_facetoface\attendees_helper($seminarevent);
        $facetoface = $DB->get_record('facetoface', array('id' => $seminarevent->get_facetoface()));

        $dates = \mod_facetoface\output\session_time::format(
            $sessiondate->timestart,
            $sessiondate->timefinish,
            $sessiondate->sessiontimezone
        );

        $data = array();
        $data[] = array(get_string('facetoface', 'mod_facetoface'), format_string($facetoface->name));
        // Get session custom fields.
        $session = $seminarevent->to_record();
        $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);
        $sessioncustomfields = customfield_get_data($session, 'facetoface_session', 'facetofacesession');

        foreach ($sessioncustomfields as $name => $customdata) {
            $data[] = array($name, $customdata);
        }

        $data[] = array(get_string('sessionstartdate', 'mod_facetoface'), get_string('sessionstartdatewithtime', 'facetoface', $dates));
        $data[] = array(get_string('sessionenddate', 'mod_facetoface'), get_string('sessionenddatewithtime', 'facetoface', $dates));
        $data[] = array(get_string('maxbookings', 'mod_facetoface'), $seminarevent->get_capacity());
        $data[] = array(get_string('numberofattendees', 'mod_facetoface'), $helper->count_attendees());
        /** @var \mod_facetoface\room_list $room */
        $rooms = \mod_facetoface\room_list::from_session($sessiondate->id);
        if (!$rooms->is_empty()) {
            $i = 1;
            foreach ($rooms as $room) {
                $info = customfield_get_data($room->to_record(), 'facetoface_room', 'facetofaceroom');
                $values = array($room->get_name());
                foreach ($info as $name => $roomdata) {
                    if (!empty($roomdata)) {
                        $values[] = $roomdata;
                    }
                }
                $data[] = array(get_string('roomnumber', 'mod_facetoface', $i), $values);
                $i++;
            }
        } else {
            $data[] = array(get_string('place', 'mod_facetoface'), get_string('notapplicable', 'facetoface'));
        }
        unset($room);
        if ($format === 'pdf' or $format === 'html') {
            $table = new html_table();
            $table->size = array('200px');
            $table->head = array();
            $table->data = array();
            foreach ($data as $d) {
                $title = new html_table_cell($d[0]);
                $title->style = 'font-weight:bold;';
                if (is_array($d[1])) {
                    $table->data[] = new html_table_row(array($title, implode('<br>', $d[1])));
                } else {
                    $table->data[] = new html_table_row(array($title, $d[1]));
                }
            }

            $result = '<h1>' . get_string('sourcetitle', 'rb_source_facetoface_signin') . '</h1>';
            $result .= html_writer::table($table);
            return $result;
        }

        // No fancy html for the rest, just a list of lines
        $result = array();
        $result[] = get_string('sourcetitle', 'rb_source_facetoface_signin');
        $result[] = '';
        foreach ($data as $d) {
            if (is_array($d[1])) {
                $d[1] = implode("\n", array_map(function ($d1) {
                    return html_to_text($d1);
                }, $d[1]));
            } else {
                $d[1] = html_to_text($d[1]);
            }
            $result[] = $d;
        }

        return $result;
    }
} // end of rb_source_facetoface_signin class

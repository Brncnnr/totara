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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

/**
 * Seminar Events
 */
class rb_source_facetoface_events extends rb_facetoface_base_source {

    use \core_course\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_joins;
    use \mod_facetoface\rb\traits\post_config;
    use \totara_cohort\rb\source\report_trait;
    use \mod_facetoface\rb\traits\deprecated_events_source;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Global report restrictions are applied in define_joinlist() method.

        $this->base = '{facetoface_sessions}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->requiredjoins = $this->define_requiredjoins();
        $this->paramoptions = $this->define_paramoptions();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_events');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_facetoface_events');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_facetoface_events');
        $this->add_customfields();
        $this->usedcomponents[] = 'totara_cohort';

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

    public function define_joinlist() {
        $joinlist = array();

        $joinlist[] = new rb_join(
            'facetoface',
            'LEFT',
            '{facetoface}',
            '(facetoface.id = base.facetoface)',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $joinlist[] = new rb_join(
            'sessiondate',
            'LEFT',
            '{facetoface_sessions_dates}',
            'sessiondate.sessionid = base.id',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );

        $this->add_grouped_session_status_to_joinlist($joinlist, 'base', 'id');
        $this->add_core_course_tables($joinlist, 'facetoface', 'course');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_core_user_tables($joinlist, 'base', 'usermodified', 'modifiedby');
        $this->add_facetoface_session_roles_to_joinlist($joinlist, 'base.id');
        $this->add_facetoface_currentuserstatus_to_joinlist($joinlist);
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_totara_cohort_course_tables($joinlist, 'facetoface', 'course');

        return $joinlist;
    }

    public function define_columnoptions() {
        global $DB;
        $usernamefieldscreator = totara_get_all_user_name_fields_join('modifiedby');
        $global_restriction_join_su = $this->get_global_report_restriction_join('su', 'userid');

        $columnoptions = array(
            new rb_column_option(
                'session',
                'totalnumattendees',
                get_string('totalnumattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\requested::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'waitlistattendees',
                get_string('waitlistattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\waitlisted::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'numspaces',
                get_string('numspaces', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'f2f_session_spaces',
                    'extrafields' => array('overall_capacity' => 'base.capacity'),
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'cancelledattendees',
                get_string('cancelledattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND (ss.statuscode = " . \mod_facetoface\signup\state\user_cancelled::get_code() . " OR ss.statuscode = " . \mod_facetoface\signup\state\event_cancelled::get_code() . ") AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'fullyattended',
                get_string('fullyattended', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\fully_attended::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'partiallyattended',
                get_string('partiallyattended', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\partially_attended::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'unabletoattend',
                get_string('unabletoattend', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\unable_to_attend::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'noshowattendees',
                get_string('noshowattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\no_show::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'declinedattendees',
                get_string('declinedattendees', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                    FROM {facetoface_signups} su
                    {$global_restriction_join_su}
                    JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                   WHERE ss.superceded = 0 AND ss.statuscode = " . \mod_facetoface\signup\state\declined::get_code() . " AND su.sessionid = base.id)",
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'details',
                get_string('sessdetails', 'rb_source_facetoface_sessions'),
                'base.details',
                array(
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea',
                    'extrafields' => array(
                        'filearea' => '\'session\'',
                        'component' => '\'mod_facetoface\'',
                        'fileid' => 'base.id',
                        'context' => '\'context_module\'',
                        'recordid' => 'base.facetoface'
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'overbookingallowed',
                get_string('overbookingallowed', 'rb_source_facetoface_summary'),
                'base.allowoverbook',
                array(
                    'displayfunc' => 'yes_or_no'
                )
            ),
            new rb_column_option(
                'facetoface',
                'minbookings',
                get_string('minbookings', 'rb_source_facetoface_summary'),
                'base.mincapacity',
                array(
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer'
                )
            ),
        );

        if (!get_config(null, 'facetoface_hidecost')) {
            $columnoptions[] = new rb_column_option(
                'facetoface',
                'normalcost',
                get_string('normalcost', 'rb_source_facetoface_summary'),
                'base.normalcost',
                array(
                    'dbdatatype' => 'decimal',
                    'displayfunc' => 'format_string'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'facetoface',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_summary'),
                    'base.discountcost',
                    array(
                        'dbdatatype' => 'decimal',
                        'displayfunc' => 'format_string'
                    )
                );
            }
        }

        $this->add_deprecated_event_columns($columnoptions);

        $columnoptions[] = new rb_column_option(
            'session',
            'sessionid',
            get_string('sessionid', 'rb_source_facetoface_sessions'),
            'base.id',
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'capacity',
            get_string('sesscapacity', 'rb_source_facetoface_sessions'),
            'base.capacity',
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'session',
            'numattendees',
            get_string('numattendees', 'rb_source_facetoface_sessions'),
            "(SELECT COUNT('x')
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
               WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\booked::get_code() ." AND su.sessionid = base.id)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'numattendeeslink',
            get_string('numattendeeslink', 'rb_source_facetoface_summary'),
            "(SELECT COUNT('x')
                FROM {facetoface_signups} su
                {$global_restriction_join_su}
                JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
               WHERE ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\booked::get_code() ." AND su.sessionid = base.id)",
            array(
                'dbdatatype' => 'integer',
                'displayfunc' => 'f2f_num_attendees_link',
                'defaultheading' => get_string('numattendees', 'rb_source_facetoface_sessions'),
                'extrafields' => array(
                    'session' => 'base.id'
                ),
                'iscompound' => true,
                'issubquery' => true,
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventattendance',
            get_string('eventattendance', 'rb_source_facetoface_summary'),
            "base.id",
            array(
                'capability' => 'mod/facetoface:takeattendance',
                'nosort' => true,
                'extracontext' => array('link' => false),
                'displayfunc' => 'f2f_event_attendance',
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventattendancelink',
            get_string('eventattendancelink', 'rb_source_facetoface_summary'),
            "base.id",
            array(
                'capability' => 'mod/facetoface:takeattendance',
                'nosort' => true,
                'extracontext' => array('link' => true),
                'displayfunc' => 'f2f_event_attendance',
                'defaultheading' => get_string('eventattendance', 'rb_source_facetoface_summary'),
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimecreated',
            get_string('eventtimecreated', 'rb_source_facetoface_events'),
            "base.timecreated",
            array(
                'joins' => 'sessiondate',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimemodified',
            get_string('lastupdated', 'rb_source_facetoface_events'),
            "base.timemodified",
            array(
                'joins' => 'sessiondate',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp'
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'eventmodifiedby',
            get_string('lastupdatedby', 'rb_source_facetoface_events'),
            "CASE WHEN base.usermodified = 0 THEN null
                  ELSE " . $DB->sql_concat_join("' '", $usernamefieldscreator) . " END",
            array(
                'joins' => 'modifiedby',
                'displayfunc' => 'user_link',
                'extrafields' => array_merge(
                    ['id' => 'modifiedby.id', 'deleted' => 'modifiedby.deleted'],
                    $usernamefieldscreator
                ),
            )
        );

        $this->add_grouped_session_status_to_columns($columnoptions, 'base');
        $this->add_facetoface_common_to_columns($columnoptions, 'base');
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_facetoface_currentuserstatus_to_columns($columnoptions);

        // Include some standard columns.
        $this->add_core_course_category_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);

        return $columnoptions;
    }


    /**
     * Add joins required by @see rb_source_facetoface_events::add_grouped_session_status_to_columns()
     * @param array $joinlist
     * @param string $join 'sessions' table to join to
     * @param string $field 'id' field (from sessions table) to join to
     */
    protected function add_grouped_session_status_to_joinlist(&$joinlist, $join, $field) {
        // No global restrictions here because status is absolute (e.g if it is overbooked then it is overbooked, even if user
        // cannot see all participants).
        $joinlist[] =  new rb_join(
            'cntbookings',
            'LEFT',
            "(SELECT s.id sessionid, COUNT(ss.id) cntsignups
                FROM {facetoface_sessions} s
                LEFT JOIN {facetoface_signups} su ON (su.sessionid = s.id)
                LEFT JOIN {facetoface_signups_status} ss
                    ON (su.id = ss.signupid AND ss.superceded = 0 AND ss.statuscode >= " . \mod_facetoface\signup\state\booked::get_code() . ")
                WHERE 1=1
                GROUP BY s.id)",

            "cntbookings.sessionid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );

        $joinlist[] = new rb_join(
            'eventdateinfo',
            'LEFT',
            '(  SELECT  sd.sessionid,
                        sd.eventstart,
                        sd.eventfinish,
                        (
                            SELECT tzstart.sessiontimezone AS tzstart
                            FROM {facetoface_sessions_dates} tzstart
                            WHERE sd.eventstart = tzstart.timestart
                              AND sd.sessionid = tzstart.sessionid
                        ) AS tzstart,
                        (
                            SELECT tzfinish.sessiontimezone AS tzfinish
                            FROM {facetoface_sessions_dates} tzfinish
                            WHERE sd.eventfinish = tzfinish.timefinish
                              AND sd.sessionid = tzfinish.sessionid
                        ) AS tzfinish
                FROM (
                        SELECT   sessionid,
                                 MIN(timestart) AS eventstart,
                                 MAX(timefinish) AS eventfinish
                        FROM     {facetoface_sessions_dates}
                        GROUP BY sessionid
                     ) sd
                )',
            "eventdateinfo.sessionid = {$join}.{$field}",
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );
    }

    /**
     * Add session booking and overall status columns for sessions (so it also groups all sessions (dates) in an event)
     * Requires 'eventdateinfo' join, and 'cntbookings' join provided by
     * @see rb_source_facetoface_events::add_grouped_session_status_to_joinlist()
     *
     * If you call this function in order to get the correct highlighting you will need to extend the CSS rules in
     * mod/facetoface/styles.css and add a line like the following:
     *     .reportbuilder-table[data-source="rb_source_facetoface_summary"] tr
     *
     * Search for that and you'll see what you need to do.
     *
     * @param array $columnoptions
     * @param string $joinsessions Join name that provide {facetoface_sessions}
     */
    protected function add_grouped_session_status_to_columns(&$columnoptions, $joinsessions = 'sessions') {
        $now = time();

        $columnoptions[] = new rb_column_option(
            'session',
            'overallstatus',
            get_string('overallstatus', 'rb_source_facetoface_summary'),
            "( CASE WHEN cancelledstatus <> 0 THEN 'cancelled'
                    WHEN eventdateinfo.eventstart IS NULL OR eventdateinfo.eventstart = 0 OR eventdateinfo.eventstart > {$now} THEN 'upcoming'
                    WHEN {$now} > eventdateinfo.eventstart AND {$now} < eventdateinfo.eventfinish THEN 'started'
                    WHEN {$now} > eventdateinfo.eventfinish THEN 'ended'
                    ELSE NULL END
             )",
            array(
                'joins' => array('eventdateinfo'),
                'displayfunc' => 'overall_status',
                'extrafields' => array(
                    'timestart' => "eventdateinfo.eventstart",
                    'timefinish' => "eventdateinfo.eventfinish",
                )
            )
        );

        $columnoptions[] = new rb_column_option(
            'session',
            'bookingstatus',
            get_string('bookingstatus', 'rb_source_facetoface_summary'),
            "(CASE WHEN {$now} > eventdateinfo.eventfinish AND cntsignups < {$joinsessions}.capacity THEN 'ended'
                   WHEN {$joinsessions}.registrationtimefinish <> 0 AND {$now} > {$joinsessions}.registrationtimefinish THEN 'ended'
                   WHEN {$joinsessions}.registrationtimestart <> 0 AND {$now} < {$joinsessions}.registrationtimestart THEN 'notopen'
                   WHEN cancelledstatus <> 0 THEN 'cancelled'
                   WHEN cntsignups < {$joinsessions}.mincapacity THEN 'underbooked'
                   WHEN cntsignups < {$joinsessions}.capacity THEN 'available'
                   WHEN cntsignups = {$joinsessions}.capacity THEN 'fullybooked'
                   WHEN cntsignups > {$joinsessions}.capacity THEN 'overbooked'
                   ELSE NULL END)",
            array(
                'joins' => array('eventdateinfo', 'cntbookings', $joinsessions),
                'displayfunc' => 'booking_status',
                'dbdatatype' => 'char',
                'extrafields' => array(
                    'mincapacity' => "{$joinsessions}.mincapacity",
                    'capacity' => "{$joinsessions}.capacity",
                    'timestart' => "eventdateinfo.eventstart",
                    'timefinish' => "eventdateinfo.eventfinish"
                )
            )
        );

        $columnoptions[] = new rb_column_option(
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
        );

        $columnoptions[] = new rb_column_option(
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
        );
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'facetoface',
                'name',
                get_string('ftfname', 'rb_source_facetoface_sessions'),
                'text'
            ),
            new rb_filter_option(
                'session',
                'bookingstatus',
                get_string('bookingstatus', 'rb_source_facetoface_events'),
                'select',
                array(
                    'selectchoices' => self::get_bookingstatus_options(),
                )
            ),
            new rb_filter_option(
                'session',
                'overallstatus',
                get_string('overallstatus', 'rb_source_facetoface_events'),
                'select',
                array(
                    'selectfunc' => 'overallstatus',
                )
            ),
            new rb_filter_option(
                'session',
                'eventtimecreated',
                get_string('eventtimecreated', 'rb_source_facetoface_events'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'eventtimemodified',
                get_string('lastupdated', 'rb_source_facetoface_events'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'eventmodifiedby',
                get_string('lastupdatedby', 'rb_source_facetoface_events'),
                'text'
            ),
        );

        $this->add_facetoface_session_role_fields_to_filters($filteroptions);
        $this->add_facetoface_currentuserstatus_to_filters($filteroptions);

        // Add session custom fields to filters.
        $this->add_core_course_category_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        $contentoptions[] = new rb_content_option(
            'session_roles',
            get_string('sessionroles', 'rb_source_facetoface_events'),
            'base.id'
        );

        return $contentoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'facetoface',
                'value' => 'namelink',
            ),
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
                'type' => 'session',
                'value' => 'eventattendancelink',
            ),
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

    protected function add_customfields() {
        $this->add_totara_customfield_component('facetoface_session', 'base', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_sessioncancel', 'base', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    protected function define_paramoptions() {
        $paramoptions = array(
        );

        return $paramoptions;
    }

    /**
     * Report post config operations.
     *
     * @param reportbuilder $report
     */
    public function post_config(reportbuilder $report) {
        $this->add_audiencevisibility_config($report);
    }
}

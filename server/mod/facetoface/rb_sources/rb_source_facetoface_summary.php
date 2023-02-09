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
 * @author Michael Gwynne <michael.gwynne@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_base_source.php');

/**
 * Seminar Sessions
 */
class rb_source_facetoface_summary extends rb_facetoface_base_source {

    use \core_course\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \mod_facetoface\rb\traits\required_joins;
    use \mod_facetoface\rb\traits\post_config;
    use \mod_facetoface\rb\traits\facilitator;
    use \mod_facetoface\rb\traits\rooms;
    use \mod_facetoface\rb\traits\assets;
    use \mod_facetoface\rb\traits\deprecated_summary_source;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Global report restrictions are applied in define_joinlist() method.

        $this->base = '{facetoface_sessions_dates}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->requiredjoins = $this->define_requiredjoins();
        $this->paramoptions = $this->define_paramoptions();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_facetoface_summary');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_facetoface_summary');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_facetoface_summary');
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

    public function define_joinlist() {
        $joinlist = array();

        $this->add_session_common_to_joinlist($joinlist);
        $this->add_rooms_to_join_list($joinlist, 'base');
        $this->add_assets_to_join_list($joinlist, 'base');
        $this->add_facilitators_to_join_list($joinlist, 'base');
        $this->add_session_status_to_joinlist($joinlist);
        $this->add_core_course_tables($joinlist, 'facetoface', 'course');
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_core_user_tables($joinlist, 'sessions', 'usermodified', 'modifiedby');
        $this->add_facetoface_session_roles_to_joinlist($joinlist);
        $this->add_facetoface_currentuserstatus_to_joinlist($joinlist, 'base', 'sessionid');
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');

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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode >= " . \mod_facetoface\signup\state\requested::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\waitlisted::get_code() . ")",
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'integer',
                    'iscompound' => true,
                    'issubquery' => true,
                )
            ),
            new rb_column_option(
                'session',
                'numspaces', // NOTE: ignore global report restrictions, free space is not affected by restrictions!
                get_string('numspaces', 'rb_source_facetoface_summary'),
                "(SELECT COUNT('x')
                  FROM {facetoface_signups} su
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode >= " . \mod_facetoface\signup\state\waitlisted::get_code() . ")",
                array(
                    'joins' => array('sessions'),
                    'displayfunc' => 'f2f_session_spaces',
                    'extrafields' => array('overall_capacity' => 'sessions.capacity'),
                    'dbdatatype' => 'integer',
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode IN (" . \mod_facetoface\signup\state\user_cancelled::get_code() . ", " . \mod_facetoface\signup\state\event_cancelled::get_code() . "))",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\fully_attended::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\partially_attended::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\unable_to_attend::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\no_show::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                  JOIN {facetoface_signups_status} ss
                    ON su.id = ss.signupid
                  WHERE ss.superceded = 0 AND su.sessionid = sessions.id
                    AND ss.statuscode = " . \mod_facetoface\signup\state\declined::get_code() . ")",
                array(
                    'joins' => array('sessions'),
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
                'sessions.details',
                array('joins' => 'sessions',
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
                'overbookingallowed',
                get_string('overbookingallowed', 'rb_source_facetoface_summary'),
                'sessions.allowoverbook',
                array(
                    'joins' => 'sessions',
                    'displayfunc' => 'yes_or_no'
                )
            ),
            new rb_column_option(
                'session',
                'signupperiod',
                get_string('signupperiod', 'rb_source_facetoface_summary'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'outputformat' => 'text',
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_dates_period',
                    'extrafields' => array(
                        'finishdate' => 'sessions.registrationtimefinish',
                    ),
                )
            ),
            new rb_column_option(
                'session',
                'signupstartdate',
                get_string('signupstartdate', 'rb_source_facetoface_summary'),
                'sessions.registrationtimestart',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'session',
                'signupenddate',
                get_string('signupenddate', 'rb_source_facetoface_summary'),
                'sessions.registrationtimefinish',
                array(
                    'joins' => array('sessions'),
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'event_date',
                    'outputformat' => 'text'
                )
            ),
            new rb_column_option(
                'facetoface',
                'minbookings',
                get_string('minbookings', 'rb_source_facetoface_summary'),
                'sessions.mincapacity',
                array(
                    'joins' => 'sessions',
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
                'sessions.normalcost',
                array(
                    'joins' => 'sessions',
                    'dbdatatype' => 'decimal',
                    'displayfunc' => 'format_string'
                )
            );
            if (!get_config(null, 'facetoface_hidediscount')) {
                $columnoptions[] = new rb_column_option(
                    'facetoface',
                    'discountcost',
                    get_string('discountcost', 'rb_source_facetoface_summary'),
                    'sessions.discountcost',
                    array(
                        'joins' => 'sessions',
                        'dbdatatype' => 'decimal',
                        'displayfunc' => 'format_string'
                    )
                );
            }
        }

        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimecreated',
            get_string('eventtimecreated', 'rb_source_facetoface_events'),
            "sessions.timecreated",
            array(
                'joins' => 'sessions',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp',
            )
        );
        $columnoptions[] = new rb_column_option(
            'session',
            'eventtimemodified',
            get_string('lastupdated', 'rb_source_facetoface_summary'),
            "sessions.timemodified",
            array(
                'joins' => 'sessions',
                'displayfunc' => 'event_date',
                'dbdatatype' => 'timestamp',
            )
        );
        $columnoptions[] = new rb_column_option(
            'session',
            'eventmodifiedby',
            get_string('lastupdatedby', 'rb_source_facetoface_summary'),
            "CASE WHEN sessions.usermodified = 0 THEN null
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

        $this->add_session_status_to_columns($columnoptions);
        $this->add_session_common_to_columns($columnoptions);
        $this->add_facetoface_common_to_columns($columnoptions);
        $this->add_facetoface_session_roles_to_columns($columnoptions);
        $this->add_facetoface_currentuserstatus_to_columns($columnoptions);

        // Include some standard columns.
        $this->add_core_course_category_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_assets_fields_to_columns($columnoptions, 'asset', false);
        $this->add_rooms_fields_to_columns($columnoptions, 'room', false, 'base');
        $this->add_facilitators_fields_to_columns($columnoptions, 'facilitator', false);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
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
                'session',
                'bookingstatus',
                get_string('bookingstatus', 'rb_source_facetoface_summary'),
                'select',
                array(
                    'selectchoices' => self::get_bookingstatus_options(),
                )
            ),
            new rb_filter_option(
                'session',
                'overallstatus',
                get_string('overallstatus', 'rb_source_facetoface_summary'),
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
                get_string('lastupdated', 'rb_source_facetoface_summary'),
                'date'
            ),
            new rb_filter_option(
                'session',
                'eventmodifiedby',
                get_string('lastupdatedby', 'rb_source_facetoface_summary'),
                'text'
            ),
            new rb_filter_option(
                'asset',
                'assetavailable',
                get_string('assetavailable', 'rb_source_facetoface_asset'),
                'f2f_assetavailable',
                array(),
                'asset.id',
                array('asset')
            ),
            new rb_filter_option(
                'room',
                'roomavailable',
                get_string('roomavailable', 'rb_source_facetoface_rooms'),
                'f2f_roomavailable',
                array(),
                'room.id',
                array('room')
            )
        );

        $this->add_session_common_to_filters($filteroptions);
        $this->add_facetoface_session_role_fields_to_filters($filteroptions);
        $this->add_facetoface_currentuserstatus_to_filters($filteroptions);

        // Add session custom fields to filters.
        $this->add_core_course_category_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);

        $this->add_assets_fields_to_filters($filteroptions);
        $this->add_rooms_fields_to_filters($filteroptions);
        $this->add_facilitators_fields_to_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'modifiedby');

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('thedate', 'rb_source_facetoface_sessions'),
            'base.timestart'
        );
        $contentoptions[] = new rb_content_option(
            'session_roles',
            get_string('sessionroles', 'rb_source_facetoface_summary'),
            'base.sessionid'
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
                'value' => 'capacity',
            ),
            array(
                'type' => 'date',
                'value' => 'sessiondateid',
            ),
            array(
                'type' => 'date',
                'value' => 'sessionstartdate',
            ),
            array(
                'type' => 'date',
                'value' => 'sessionfinishdate',
            ),
            array(
                'type' => 'session',
                'value' => 'totalnumattendees',
            ),
            array(
                'type' => 'session',
                'value' => 'numspaces',
            ),
        );

        return $defaultcolumns;
    }

    /**
     * Required columns.
     */
    protected function define_requiredcolumns() {
        // Session_id is needed so when grouping we can keep the information grouped by sessions.
        // This is done to cover the case when we have several sessions which are identical.
        $requiredcolumns = array(
            new rb_column(
                'sessions',
                'id',
                '',
                "sessions.id",
                array('joins' => 'sessions')
            )
        );

        return $requiredcolumns;
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
        $this->add_totara_customfield_component('facetoface_session', 'sessions', 'facetofacesessionid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_sessioncancel', 'sessions', 'facetofacesessioncancelid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_room', 'room', 'facetofaceroomid', $this->joinlist, $this->columnoptions, $this->filteroptions);
        $this->add_totara_customfield_component('facetoface_asset', 'asset', 'facetofaceassetid', $this->joinlist, $this->columnoptions, $this->filteroptions);
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'roomid',
                'room.id',
                'room'
            ),
            new rb_param_option(
                'assetid',
                'asset.id',
                'asset'
            ),
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

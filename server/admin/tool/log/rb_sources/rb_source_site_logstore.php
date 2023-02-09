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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_site_logstore extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{logstore_standard_log}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_site_logstore');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_site_logstore');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_site_logstore');
        $this->sourcewhere = 'anonymous = 0';

        // No caching!!! The table is way too big and there are tons of extra fields.
        $this->cacheable = false;
        $this->usedcomponents[] = 'tool_log';
        $this->usedcomponents[] = 'totara_cohort';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    protected function define_joinlist() {

        $joinlist = array();

        // Include some standard joins.
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'base', 'courseid');
        // Requires the course join.
        $this->add_core_course_category_tables($joinlist,
            'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'base', 'courseid');
        $this->add_totara_cohort_course_tables($joinlist, 'base', 'courseid');

        // Add related user support.
        $this->add_core_user_tables($joinlist, 'base', 'relateduserid', 'ruser');

        // Add real user support.
        $this->add_core_user_tables($joinlist, 'base', 'realuserid', 'realuser');
        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $eventextrafields = array(
            'eventname' =>'base.eventname',
            'component' => 'base.component',
            'action' => 'base.action',
            'target' => 'base.target',
            'objecttable' => 'base.objecttable',
            'objectid' => 'base.objectid',
            'crud' => 'base.crud',
            'edulevel' => 'base.edulevel',
            'contextid' => 'base.contextid',
            'contextlevel' => 'base.contextlevel',
            'contextinstanceid' => 'base.contextinstanceid',
            'userid' => 'base.userid',
            'courseid' => 'base.courseid',
            'relateduserid' => 'base.relateduserid',
            'anonymous' => 'base.anonymous',
            'other' => 'base.other',
            'timecreated' => 'base.timecreated',
        );

        $columnoptions = array(
            new rb_column_option(
                'logstore_standard_log',
                'timecreated',
                get_string('time', 'rb_source_site_logstore'),
                'base.timecreated',
                array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'ip',
                get_string('ip', 'rb_source_site_logstore'),
                'base.ip',
                array('displayfunc' => 'ip_lookup_link')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'targetaction',
                get_string('targetaction', 'rb_source_site_logstore'),
                $DB->sql_concat('base.target', "' '", 'base.action'),
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'other',
                get_string('other', 'rb_source_site_logstore'),
                'base.other',
                array('displayfunc' => 'log_serialized_preformated')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'eventname',
                get_string('eventclass', 'rb_source_site_logstore'),
                'base.eventname',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'component',
                get_string('component', 'rb_source_site_logstore'),
                'base.component',
                array('displayfunc' => 'log_component')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'context',
                get_string('context', 'rb_source_site_logstore'),
                'base.contextid',
                array('displayfunc' => 'log_context')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'action',
                get_string('action', 'rb_source_site_logstore'),
                'base.action',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'target',
                get_string('target', 'rb_source_site_logstore'),
                'base.target',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'objecttable',
                get_string('objecttable', 'rb_source_site_logstore'),
                'base.objecttable',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'objectid',
                get_string('objectid', 'rb_source_site_logstore'),
                'base.objectid',
                array('dbdatatype' => 'integer',
                      'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'origin',
                get_string('origin', 'rb_source_site_logstore'),
                'base.origin',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'crud',
                get_string('crud', 'rb_source_site_logstore'),
                'base.crud',
                array('dbdatatype' => 'char',
                      'displayfunc' => 'log_crud')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'edulevel',
                get_string('edulevel', 'moodle'),
                'base.edulevel',
                array('dbdatatype' => 'char',
                      'displayfunc' => 'log_educational_level')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'name',
                get_string('name', 'rb_source_site_logstore'),
                'base.eventname',
                array('displayfunc' => 'log_event_name')
            ),
            new rb_column_option(
                'logstore_standard_log',
                'namelink',
                get_string('namelink', 'rb_source_site_logstore'),
                'base.id',
                array('displayfunc' => 'log_event_name_link',
                      'extrafields' => $eventextrafields
                     )
            ),
            new rb_column_option(
                'logstore_standard_log',
                'description',
                get_string('description', 'moodle'),
                'base.id',
                array('displayfunc' => 'log_description',
                      'extrafields' => $eventextrafields
                )
            ),
        );

        // Add composite real + on-behalf-of fullname column option.
        $userusednamefields = totara_get_all_user_name_fields_join('auser', null, true);
        $userallnamefields = totara_get_all_user_name_fields(false, 'auser', null, 'auser');
        $realallnamefields = totara_get_all_user_name_fields(false, 'realuser', null, 'realuser');

        $allnamefields = array_merge($userallnamefields, $realallnamefields);
        foreach ($allnamefields as $key => $field) {
            $allnamefields[$key] = "COALESCE($field,'')";
        }
        $allnamefields['auserid'] = 'auser.id';
        $allnamefields['realuserid'] = 'realuser.id';

        $columnoptions[] = new rb_column_option(
            'logstore_standard_log',
            'userfullnameincludingonbehalfof',
            get_string('userfullnameincludingonbehalfof', 'rb_source_site_logstore'),
            $DB->sql_concat_join("' '", $userusednamefields),
            array(
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'joins' => array('auser', 'realuser'),
                'displayfunc' => 'log_user_full_name_including_on_behalf_of',
                'extrafields' => $allnamefields,
                'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
            )
        );

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);
        // Add related user support.
        $this->add_core_user_columns($columnoptions, 'ruser', 'relateduser', true);

        // Add real user support.
        $this->add_core_user_columns($columnoptions, 'realuser', 'realuser', true);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'logstore_standard_log',
                'action',
                get_string('action', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'eventname',
                get_string('eventclass', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'component',
                get_string('component', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'objecttable',
                get_string('objecttable', 'rb_source_site_logstore'),
                'text',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'objectid',
                get_string('objectid', 'rb_source_site_logstore'),
                'number',
                array()
            ),
            new rb_filter_option(
                'logstore_standard_log',
                'timecreated',
                get_string('time', 'rb_source_site_logstore'),
                'date',
                array()
            ),
        );

        // Include some standard filters.
        $this->add_core_user_filters($filteroptions);
        $this->add_core_user_filters($filteroptions, 'relateduser', true);
        $this->add_core_user_filters($filteroptions, 'realuser', true);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('date', 'rb_source_site_logstore'),
            'base.timecreated'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid',
                null
            ),
            new rb_param_option(
                'courseid',
                'base.courseid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'logstore_standard_log',
                'value' => 'timecreated',
            ),
            array(
                'type' => 'user',
                'value' => 'namelink',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'ip',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'other',
            ),
        );

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'eventname',
            ),
            array(
                'type' => 'logstore_standard_log',
                'value' => 'action',
                'advanced' => 1,
            ),
            array(
                'type' => 'course',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'course_category',
                'value' => 'id',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositions',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisations',
                'advanced' => 1,
            ),
        );

        return $defaultfilters;
    }


    protected function define_requiredcolumns() {
        $requiredcolumns = array(
        );
        return $requiredcolumns;
    }

    /**
     * Get list of event names
     * @return array
     */
    function rb_filter_event_names_list() {
        global $DB;

        $completelist = $DB->get_recordset_sql("SELECT DISTINCT(eventname) FROM $this->base");

        if (empty($completelist)) {
            return array("" => get_string("nofilteroptions", "totara_reportbuilder"));
        }

        $events = array();
        foreach ($completelist as $eventfullpath => $eventname) {
            if (method_exists($eventfullpath, 'get_static_info')) {
                $ref = new \ReflectionClass($eventfullpath);
                if (!$ref->isAbstract()) {
                    // Get additional information.
                    $strdata = new stdClass();
                    $strdata->eventfullpath = $eventfullpath;
                    $strdata->eventname = $eventfullpath::get_name();
                    // Add to list.
                    $events[$eventfullpath] = get_string('eventandcomponent', 'rb_source_site_logstore', $strdata);
                }
            }
        }
        uasort($events, 'strcoll');

        return $events;
    }
}


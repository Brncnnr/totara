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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class rb_source_comp_status_history extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'user_id');

        $this->base = '{totara_competency_achievement}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_comp_status_history');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_comp_status_history');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_comp_status_history');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('competencies');
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'competency',
                'LEFT',
                '{comp}',
                'competency.id = base.competency_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'scalevalue',
                'LEFT',
                '{comp_scale_values}',
                'scalevalue.id = base.scale_value_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        $this->add_core_user_tables($joinlist, 'base', 'user_id');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $columnoptions = array(
            new rb_column_option(
                'competency',
                'competencyid',
                get_string('compidcolumn', 'rb_source_comp_status_history'),
                'base.competency_id',
                array('selectable' => false)
            ),
            new rb_column_option(
                'history',
                'scalevalue_id',
                get_string('compscalevalueidcolumn', 'rb_source_comp_status_history'),
                'base.scale_value_id',
                array('selectable' => false)
            ),
            new rb_column_option(
                'competency',
                'fullname',
                get_string('compnamecolumn', 'rb_source_comp_status_history'),
                'competency.fullname',
                array('defaultheading' => get_string('compnameheading', 'rb_source_comp_status_history'),
                      'joins' => 'competency',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'history',
                'scalevalue',
                get_string('compscalevaluecolumn', 'rb_source_comp_status_history'),
                'scalevalue.name',
                array('joins' => 'scalevalue',
                      'defaultheading' => get_string('compscalevalueheading', 'rb_source_comp_status_history'),
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'history',
                'scalevaluedate',
                get_string('scalevaluedatecolumn', 'rb_source_comp_status_history'),
                'base.time_scale_value',
                array(
                    'defaultheading' => get_string('scalevaluedateheading', 'rb_source_comp_status_history'),
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'history',
                'proficientdate',
                get_string('proficientdate', 'rb_source_comp_status_history'),
                'base.time_proficient',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'competency',
                'competencyid',
                get_string('compnamecolumn', 'rb_source_comp_status_history'),
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'comp'
                )
            ),
            new rb_filter_option(
                'history',
                'scalevaluedate',
                get_string('scalevaluedateheading', 'rb_source_comp_status_history'),
                'date',
                array()
            ),
            new rb_filter_option(
                'history',
                'proficientdate',
                get_string('proficientdate', 'rb_source_comp_status_history'),
                'date',
                array()
            ),

        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'user_id');

        return $filteroptions;
    }


    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.user_id'
            ),
            new rb_param_option(
                'competencyid',
                'base.competency_id'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink'
            ),
            array(
                'type' => 'competency',
                'value' => 'fullname'
            ),
            array(
                'type' => 'history',
                'value' => 'scalevalue'
            ),
            array(
                'type' => 'history',
                'value' => 'scalevaluedate'
            ),
        );
        return $defaultcolumns;
    }

    /**
     * Returns expected result for column_test.
     *
     * @codeCoverageIgnore
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }

        // TODO: This needs to be fixed during implementation of TL_19974
        //       The problem is that during testing of the other reports users are assigned to competencies
        //       This results in additional records in totara_competency_achievements - the number can not be predicted.
        //       Therefore retrieving the number here

        global $DB;
        return $DB->count_records('totara_competency_achievement');
    }

}

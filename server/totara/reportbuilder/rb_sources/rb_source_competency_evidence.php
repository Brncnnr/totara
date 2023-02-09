<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @package totara
 * @subpackage reportbuilder
 *
 * @deprecated since Totara 13
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

// Report source rb_source_competency_evidence has been deprecated. Please use rb_source_competency_status.
// No debugging notice to keep this source testable until it is removed

class rb_source_competency_evidence extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    /** @var bool true if source is selectable for new reports */
    public $selectable = false;

    /** @var bool true if reports with this source can be scheduled */
    public $scheduleable = false;

    /** @var bool true if reports with this source are compatible with report caching */
    public $cacheable = false;


    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{comp_record}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = 'Competency Status';
        $this->sourcesummary = 'Learners\' progress on their assigned competencies.';
        $this->sourcelabel = 'Performance';
        $this->usedcomponents[] = 'totara_plan';
        $this->usedcomponents[] = 'totara_hierarchy';

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
        // Source is deprecated
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'competency',
                'LEFT',
                '{comp}',
                'competency.id = base.competencyid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'scale_values',
                'LEFT',
                '{comp_scale_values}',
                'scale_values.id = base.proficiency',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'assessor',
                'LEFT',
                '{user}',
                'assessor.id = base.assessorid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = base.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'completion_position',
                'LEFT',
                '{pos}',
                'completion_position.id = base.positionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        );

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;

        $usednamefields = totara_get_all_user_name_fields_join('assessor', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('assessor');

        $columnoptions = array(
            new rb_column_option(
                'competency_evidence',  // Type.
                'proficiency',          // Value.
                'Proficiency', // Name.
                'scale_values.name',    // Field.
                array('joins' => 'scale_values',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string') // Options.
            ),
            new rb_column_option(
                'competency_evidence',
                'proficiencyid',
                'Proficiency ID',
                'base.proficiency',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency_evidence',
                'timemodified',
                'Last Modified',
                'base.timemodified',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'competency_evidence',
                'proficientdate',
                'Date Proficiency Achieved',
                'base.timeproficient',
                array('displayfunc' => 'nice_date', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'competency_evidence',
                'organisationid',
                'Completion Organisation ID',
                'base.organisationid',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency_evidence',
                'organisationid2',
                'Completion Organisation ID',
                'base.organisationid',
                array('selectable' => false)
            ),
            new rb_column_option(
                'competency_evidence',
                'organisationpath',
                'Completion Organisation Path',
                'completion_organisation.path',
                array('joins' => 'completion_organisation',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'competency_evidence',
                'organisation',
                'Completion Organisation Name',
                'completion_organisation.fullname',
                array('joins' => 'completion_organisation',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'competency_evidence',
                'positionid',
                'Completion Position ID',
                'base.positionid',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency_evidence',
                'positionid2',
                'Completion Position ID',
                'base.positionid',
                array('selectable' => false,
                      'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency_evidence',
                'positionpath',
                'Completion Position Path',
                'completion_position.path',
                array('joins' => 'completion_position',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'competency_evidence',
                'position',
                'Completion Position Name',
                'completion_position.fullname',
                array('joins' => 'completion_position',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'competency_evidence',
                'assessor',
                'Assessor Name',
                $DB->sql_concat_join("' '", $usednamefields),
                array('joins' => 'assessor',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'user',
                      'extrafields' => $allnamefields
                )
            ),
            new rb_column_option(
                'competency_evidence',
                'assessorname',
                'Assessor Organisation',
                'base.assessorname',
                array('dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'competency',
                'fullname',
                'Competency Name',
                'competency.fullname',
                array('joins' => 'competency',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'competency',
                'shortname',
                'Competency Short Name',
                'competency.shortname',
                array('joins' => 'competency',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'competency',
                'idnumber',
                'Competency ID Number',
                'competency.idnumber',
                array('joins' => 'competency',
                      'displayfunc' => 'plaintext',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'competency',
                'competencylink',
                'Competency Name (linked to competency page)',
                'competency.fullname',
                array(
                    'joins' => 'competency',
                    'displayfunc' => 'competency_link',
                    'defaultheading' => 'Competency Name',
                    'extrafields' => array('competency_id' => 'competency.id'),
                )
            ),
            new rb_column_option(
                'competency',
                'id',
                'Competency ID Number',
                'base.competencyid',
                array('displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency',
                'id2',
                'Competency ID Number',
                'base.competencyid',
                array('selectable' => false,
                      'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'competency',
                'path',
                'Competency Path',
                'competency.path',
                array('joins' => 'competency',
                      'displayfunc' => 'plaintext')
            ),
            new rb_column_option(
                'competency',
                'statushistorylink',
                'User\'s Status History Link',
                'base.userid',
                array('defaultheading' => 'Status History',
                      'displayfunc' => 'plan_competency_status_history_link',
                      'extrafields' => array('competencyid' => 'base.competencyid'),
                      'noexport' => true,
                      'nosort' => true)
            )
        );

        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'competency_evidence',  // type
                'timemodified',        // value
                'Last Modified',       // label
                'date',                 // filtertype
                array()                 // options
            ),
            new rb_filter_option(
                'competency_evidence',
                'proficiencyid',
                'Proficiency',
                'select',
                array(
                    'selectfunc' => 'proficiency_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'competency_evidence',  // type
                'proficientdate',        // value
                'Date Proficiency Achieved',       // label
                'date',                 // filtertype
                array()                 // options
            ),
            new rb_filter_option(
                'competency_evidence',
                'organisationid',
                'Office when completed (basic)',
                'select',
                array(
                    'selectfunc' => 'organisations_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'organisationid2',
                'Organisations when completed',
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'org',
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'organisationpath',
                'Organisation when completed',
                'hierarchy',
                array(
                    'hierarchytype' => 'org',
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'positionid',
                'Position when completed (basic)',
                'select',
                array(
                    'selectfunc' => 'positions_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'positionid2',
                'Positions when completed',
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'pos',
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'positionpath',
                'Position when completed',
                'hierarchy',
                array(
                    'hierarchytype' => 'pos',
                )
            ),
            new rb_filter_option(
                'competency_evidence',
                'assessor',
                'Assessor Name',
                'text'
            ),
            new rb_filter_option(
                'competency_evidence',
                'assessorname',
                'Assessor Organisation',
                'text'
            ),
            new rb_filter_option(
                'competency',
                'path',
                'Competency',
                'hierarchy',
                array(
                    'hierarchytype' => 'comp',
                )
            ),
            new rb_filter_option(
                'competency',
                'fullname',
                'Competency Name',
                'text'
            ),
            new rb_filter_option(
                'competency',
                'shortname',
                'Competency Short Name',
                'text'
            ),
            new rb_filter_option(
                'competency',
                'idnumber',
                'Competency ID Number',
                'text'
            ),
            new rb_filter_option(
                'competency',
                'id2',
                'Competencies',
                'hierarchy_multi',
                array(
                    'hierarchytype' => 'comp',
                )
            ),

        );
        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'completed_org',
            'The organisation when completed',
            'completion_organisation.path',
            'completion_organisation'
        );

        $contentoptions[] = new rb_content_option(
            'date',
            'The completion date',
            'base.timemodified'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',       // parameter name
                'base.userid',  // field
                null            // joins
            ),
            new rb_param_option(
                'compid',
                'base.competencyid'
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type'  => 'user',
                'value' => 'namelink'
            ),
            array(
                'type'  => 'competency',
                'value' => 'competencylink',
            ),
            array(
                'type'  => 'job_assignment',
                'value' => 'allorganisationnames',
            ),
            array(
                'type'  => 'competency_evidence',
                'value' => 'organisation',
            ),
            array(
                'type'  => 'job_assignment',
                'value' => 'allpositionnames',
            ),
            array(
                'type'  => 'competency_evidence',
                'value' => 'position',
            ),
            array(
                'type'  => 'competency_evidence',
                'value' => 'proficiency',
            ),
            array(
                'type'  => 'competency_evidence',
                'value' => 'timemodified',
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allorganisations',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency_evidence',
                'value' => 'organisationpath',
                'advanced' => 1,
            ),
            array(
                'type' => 'job_assignment',
                'value' => 'allpositions',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency_evidence',
                'value' => 'positionpath',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency',
                'value' => 'fullname',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency_evidence',
                'value' => 'timemodified',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency_evidence',
                'value' => 'proficiencyid',
                'advanced' => 1,
            ),
        );
        return $defaultfilters;
    }

    /**
     * Display status history link
     *
     * @deprecated Since Totara 12.0
     * @param $userid
     * @param $row
     * @param bool $isexport
     * @return string
     */
    public function rb_display_status_history_link($userid, $row, $isexport = false) {
        debugging('rb_source_competency_evidence::rb_display_status_history_link has been deprecated since Totara 12.0. Use totara_plan\rb\display\plan_competency_status_history_link::display', DEBUG_DEVELOPER);
        if ($isexport) {
            return '';
        }

        if ($userid == 0) {
            return '';
        }

        $url = new moodle_url('/totara/hierarchy/prefix/competency/statushistoryreport.php',
                array('userid' => $userid, 'competencyid' => $row->competencyid));

        return html_writer::link($url, 'Status History');
    }

    //
    //
    // Source specific column display methods
    //
    //

    /**
     * Displays link competency to competency view page requires the competency_id extra field in column definition.
     *
     * @deprecated Since Totara 12.0
     * @param string $name
     * @param object Report row $row
     * @return string html link
     */
    public function rb_display_link_competency($comp, $row) {
        debugging('rb_source_competency_evidence::rb_display_link_competency has been deprecated since Totara 12.0. Use totara_hierarchy\rb\display\competency_link::display', DEBUG_DEVELOPER);
        if (empty($comp)) {
            return '';
        }
        $compid = $row->competency_id;
        $url = new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => 'competency', 'id' => $compid));
        return html_writer::link($url, $comp);
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_proficiency_list() {
        global $DB;

        $values = $DB->get_records_menu('comp_scale_values', null, 'scaleid, sortorder', 'id, name');

        $scales = array();
        foreach ($values as $index => $value) {
            $scales[$index] = format_string($value);
        }

        // include all possible scale values (from every scale)
        return $scales;
    }

} // end of rb_source_competency_evidence class


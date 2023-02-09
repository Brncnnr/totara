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
 * @package totara_plan
 */

use totara_competency\entity\competency_achievement;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/plan/lib.php');

/**
 * A report builder source for DP competencies
 */
class rb_source_dp_competency extends rb_base_source {
    use \totara_job\rb\source\report_trait;

    public $dp_plans;

    /**
     * Constructor
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $global_restriction_join_tca = $this->get_global_report_restriction_join('tca', 'user_id');
        $global_restriction_join_p1 = $this->get_global_report_restriction_join('p1', 'userid');

        $tcajoin = $DB->sql_concat_join("','", array($DB->sql_cast_2char('tca.user_id'), $DB->sql_cast_2char('tca.competency_id')));
        $pjoin  = $DB->sql_concat_join("','", array($DB->sql_cast_2char('p1.userid'), $DB->sql_cast_2char('pca1.competencyid')));
        $active_assignment = \totara_competency\entity\competency_achievement::ACTIVE_ASSIGNMENT;
        $this->base = "(
            SELECT DISTINCT {$tcajoin} AS id, tca.user_id AS userid, tca.competency_id AS competencyid
            FROM {totara_competency_achievement} tca
            {$global_restriction_join_tca}
            WHERE tca.scale_value_id IS NOT NULL
              AND tca.status = $active_assignment
            UNION
                SELECT DISTINCT {$pjoin} AS id, p1.userid AS userid, pca1.competencyid AS competencyid
                FROM {dp_plan_competency_assign} pca1
                INNER JOIN {dp_plan} p1 ON pca1.planid = p1.id
                {$global_restriction_join_p1}
        )";

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = array();
        $this->dp_plans = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_competency');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_dp_competency');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_dp_competency');
        $this->usedcomponents[] = 'totara_plan';
        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     *
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist
     *
     * @global object $CFG
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = array();

        /**
         * dp_plan has userid, dp_plan_competency_assign has competencyid. In order to
         * avoid multiplicity we need to join them together before we join
         * against the rest of the query
         */
        $sql = "SELECT p.id AS planid, p.templateid, p.userid, p.name AS planname, p.description AS plandescription,
                    p.startdate AS planstartdate, p.enddate AS planenddate, p.status AS planstatus,
                    pc.id, pc.competencyid, pc.priority, pc.duedate, pc.approved, pc.scalevalueid
                FROM {dp_plan} p
                INNER JOIN {dp_plan_competency_assign} pc ON p.id = pc.planid";
        $joinlist[] = new rb_join(
            'dp_competency',
            'LEFT',
            "($sql)",
            'dp_competency.userid = base.userid and dp_competency.competencyid = base.competencyid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY
        );
        $joinlist[] = new rb_join(
            'competency_value',
            'LEFT',
            '{dp_plan_competency_value}',
            'competency_value.competency_id = base.competencyid',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );
        $joinlist[] = new rb_join(
            'template',
            'LEFT',
            '{dp_template}',
            'dp_competency.templateid = template.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'dp_competency'
        );
        $joinlist[] = new rb_join(
            'competency',
            'LEFT',
            '{comp}',
            'base.competencyid = competency.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );
        $joinlist[] = new rb_join(
            'priority',
            'LEFT',
            '{dp_priority_scale_value}',
            'dp_competency.priority = priority.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'dp_competency'
        );
        $joinlist[] = new rb_join(
            'scale_value_2',
            'LEFT',
            '{comp_scale_values}',
            'competency_value.scale_value_id = scale_value_2.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'competency_value'
        );
        $joinlist[] = new rb_join(
            'scale_value',
            'LEFT',
            '{comp_scale_values}',
            'dp_competency.scalevalueid = scale_value.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'dp_competency'
        );
        $joinlist[] = new rb_join(
            'linkedcourses',
            'LEFT',
            "(SELECT itemid1 AS compassignid, COUNT(id) AS count
              FROM {dp_plan_component_relation}
              WHERE component1 = 'competency' AND component2 = 'course'
              GROUP BY itemid1)",
            'dp_competency.id = linkedcourses.compassignid',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'dp_competency'
        );
        $joinlist[] = new rb_join(
            'achievement',
            'LEFT',
            '{totara_competency_achievement}',
            '(base.competencyid = achievement.competency_id
              AND achievement.user_id = base.userid 
              AND achievement.status = ' . competency_achievement::ACTIVE_ASSIGNMENT . ')',
              REPORT_BUILDER_RELATION_MANY_TO_ONE
        );
        $joinlist[] = new rb_join(
            'evidence_scale_value',
            'LEFT',
            '{comp_scale_values}',
            'achievement.scale_value_id = evidence_scale_value.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            'achievement'
        );
        $joinlist[] = new rb_join(
            'comp_type',
            'LEFT',
            '{comp_type}',
            'competency.typeid = comp_type.id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'competency'
        );

        $this->add_core_user_tables($joinlist, 'base','userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = array();

        $columnoptions[] = new rb_column_option(
            'plan',
            'name',
            get_string('planname', 'rb_source_dp_competency'),
            'dp_competency.planname',
            array(
                'defaultheading' => get_string('plan', 'rb_source_dp_competency'),
                'joins' => 'dp_competency',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'planlink',
            get_string('plannamelinked', 'rb_source_dp_competency'),
            'dp_competency.planname',
            array(
                'defaultheading' => get_string('plan', 'rb_source_dp_competency'),
                'joins' => 'dp_competency',
                'displayfunc' => 'plan_link',
                'extrafields' => array( 'plan_id' => 'dp_competency.planid' )
            )
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'startdate',
            get_string('planstartdate', 'rb_source_dp_competency'),
            'dp_competency.planstartdate',
            array(
                'joins' => 'dp_competency',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'enddate',
            get_string('planenddate', 'rb_source_dp_competency'),
            'dp_competency.planenddate',
            array(
                'joins' => 'dp_competency',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'status',
            get_string('planstatus', 'rb_source_dp_competency'),
            'dp_competency.planstatus',
            array(
                'joins' => 'dp_competency',
                'displayfunc' => 'plan_status'
            )
        );
        $columnoptions[] = new rb_column_option(
            'template',
            'name',
            get_string('templatename', 'rb_source_dp_competency'),
            'template.shortname',
            array(
                'defaultheading' => get_string('plantemplate', 'rb_source_dp_competency'),
                'joins' => 'template',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'template',
            'startdate',
            get_string('templatestartdate', 'rb_source_dp_competency'),
            'template.startdate',
            array(
                'joins' => 'template',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'template',
            'enddate',
            get_string('templateenddate', 'rb_source_dp_competency'),
            'template.enddate',
            array(
                'joins' => 'template',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'fullname',
            get_string('competencyname', 'rb_source_dp_competency'),
            'competency.fullname',
            array(
                'defaultheading' => get_string('competencyname', 'rb_source_dp_competency'),
                'joins' => 'competency',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'duedate',
            get_string('competencyduedate', 'rb_source_dp_competency'),
            'dp_competency.duedate',
            array(
                'displayfunc' => 'nice_date',
                'joins' => 'dp_competency',
                'dbdatatype' => 'timestamp'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'priority',
            get_string('competencypriority', 'rb_source_dp_competency'),
            'priority.name',
            array(
                'joins' => 'priority',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'plan_competency_priority'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'status',
            get_string('competencystatus', 'rb_source_dp_competency'),
            'dp_competency.approved',
            array(
                'displayfunc' => 'plan_item_status',
                'joins' => 'dp_competency'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'competencyeditstatus',
            get_string('competencyeditstatus', 'rb_source_dp_competency'),
            'dp_competency.competencyid',
            array(
                'defaultheading' => 'Plan',
                'joins' => 'dp_competency',
                'displayfunc' => 'plan_competency_edit_status',
                'extrafields' => array( 'planid' => 'dp_competency.planid')
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'type',
            get_string('competencytype', 'rb_source_dp_competency'),
            'comp_type.fullname',
            array(
                'joins' => 'comp_type',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'type_id',
            get_string('competencytypeid', 'rb_source_dp_competency'),
            'competency.typeid',
            array(
                'joins' => 'competency',
                'displayfunc' => 'integer'
            )
        );
        // returns 1 for 'proficient' competencies, 0 otherwise
        $columnoptions[] = new rb_column_option(
            'competency',
            'proficient',
            get_string('competencyproficient', 'rb_source_dp_competency'),
            // source of proficient status depends on plan status
            // take 'live' value for active plans and static
            // stored value for completed plans
            'CASE WHEN dp_competency.planstatus = ' . DP_PLAN_STATUS_COMPLETE . '
                THEN
                    scale_value.proficient
                ELSE
                    evidence_scale_value.proficient
                END',
            array(
                'joins' => array('dp_competency', 'scale_value', 'evidence_scale_value'),
                'displayfunc' => 'yes_or_no'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'proficiencyandapproval',
            get_string('overall_achievement_level', 'rb_source_dp_competency'),
            // source of proficiency depends on plan status
            // take 'live' value for active plans and static
            // stored value for completed plans
            'CASE WHEN dp_competency.planstatus = ' . DP_PLAN_STATUS_COMPLETE . '
                THEN
                    scale_value.name
                ELSE
                    evidence_scale_value.name
                END',
            array(
                'joins' => array('dp_competency', 'scale_value', 'evidence_scale_value', 'competency'),
                'displayfunc' => 'plan_overall_achievement_level',
                'dbdatatype' => 'char',
                'outputformat' => 'text'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'linkedcourses',
            get_string('courses', 'rb_source_dp_competency'),
            'linkedcourses.count',
            array(
                'joins' => 'linkedcourses',
                'displayfunc' => 'integer'
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'statushistorylink',
            get_string('statushistorylinkcolumn', 'rb_source_dp_competency'),
            'base.userid',
            array(
                'defaultheading' => get_string('statushistorylinkheading', 'rb_source_dp_competency'),
                'displayfunc' => 'plan_competency_status_history_link',
                'extrafields' => array('competencyid' => 'base.competencyid'),
                'noexport' => true,
                'nosort' => true
            )
        );
        $columnoptions[] = new rb_column_option(
            'competency',
            'learning_plan_status',
            get_string('learning_plan_status', 'rb_source_dp_competency'),
            'scale_value_2.name',
            array(
                'joins' => ['competency_value', 'scale_value_2'],
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            )
        );
        /**
         * @deprecated since Totara 13.0
         */
        $columnoptions[] = new rb_column_option(
            'competency',
            'proficiency',
            'Competency proficiency',
            // source of proficiency depends on plan status
            // take 'live' value for active plans and static
            // stored value for completed plans
            'CASE WHEN dp_competency.planstatus = ' . DP_PLAN_STATUS_COMPLETE . '
            THEN
                scale_value.name
            ELSE
                evidence_scale_value.name
            END',
            array(
                'joins' => array('dp_competency', 'scale_value', 'evidence_scale_value'),
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string',
                'deprecated' => true
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new rb_filter_option(
            'competency',
            'fullname',
            get_string('competencyname', 'rb_source_dp_competency'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'competency',
            'priority',
            get_string('competencypriority', 'rb_source_dp_competency'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'competency',
            'duedate',
            get_string('competencyduedate', 'rb_source_dp_competency'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'plan',
            'name',
            get_string('planname', 'rb_source_dp_competency'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'competency',
            'type_id',
            get_string('competencytype', 'rb_source_dp_competency'),
            'select',
            array(
                'selectfunc' => 'competency_type_list',
                'attributes' => rb_filter_option::select_width_limiter(),
            )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * Define the available param options for this report.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = array();
        $paramoptions[] = new rb_param_option(
            'userid',
            'base.userid'
        );
        $paramoptions[] = new rb_param_option(
            'competencyid',
            'base.competencyid'
        );
        $paramoptions[] = new rb_param_option(
            'rolstatus',
            'CASE WHEN dp_competency.planstatus = ' . DP_PLAN_STATUS_COMPLETE . '
            THEN
                CASE WHEN scale_value.proficient = 1
                THEN \'completed\' ELSE \'active\'
                END
            ELSE
                CASE WHEN evidence_scale_value.proficient = 1
                THEN \'completed\' ELSE \'active\'
                END
            END',
            array('dp_competency', 'scale_value', 'evidence_scale_value'),
            'string'
        );
        return $paramoptions;
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_defaultcolumns() {
        return self::get_default_columns();
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_defaultfilters() {
        return [];
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns() {
        return array(
            array(
                'type' => 'plan',
                'value' => 'planlink',
                'heading' => get_string('plan', 'totara_plan'),
            ),
            array(
                'type' => 'plan',
                'value' => 'status',
                'heading' => get_string('planstatus', 'totara_plan'),
            ),
            array(
                'type' => 'competency',
                'value' => 'fullname',
                'heading' => get_string('competency', 'totara_hierarchy'),
            ),
            array(
                'type' => 'competency',
                'value' => 'priority',
                'heading' => get_string('priority', 'rb_source_dp_competency'),
            ),
            array(
                'type' => 'competency',
                'value' => 'duedate',
                'heading' => get_string('duedate', 'rb_source_dp_competency'),
            ),
            array(
                'type' => 'competency',
                'value' => 'proficiencyandapproval',
                'heading' => get_string('overall_achievement_level', 'rb_source_dp_competency'),
            ),
        );
    }

    /**
     * The default filters for embedded reports.
     *
     * @return array
     */
    public static function get_default_filters() {
        return array(
            array(
                'type' => 'competency',
                'value' => 'fullname',
                'advanced' => 0,
            ),
            array(
                'type' => 'competency',
                'value' => 'priority',
                'advanced' => 1,
            ),
            array(
                'type' => 'competency',
                'value' => 'duedate',
                'advanced' => 1,
            ),
            array(
                'type' => 'plan',
                'value' => 'name',
                'advanced' => 1,
            ),
        );
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return (
            !advanced_feature::is_enabled('recordoflearning') or
            !advanced_feature::is_enabled('competencies') ||
            advanced_feature::is_enabled('competency_assignment')
        );
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

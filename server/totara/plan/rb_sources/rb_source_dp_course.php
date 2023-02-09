<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_plan
 * @subpackage reportbuilder
 */

use totara_core\advanced_feature;
use totara_plan\record_of_learning;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// needed for approval constants etc
require_once($CFG->dirroot . '/totara/plan/lib.php');
// needed to access completion status codes
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * A report builder source for DP courses
 */
class rb_source_dp_course extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

    /**
     * Stored during post_params() so that it can be used later to update our source joins.
     *
     * @var int
     */
    protected $userid;

    /**
     * Constructor
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = '(
            SELECT id, userid, instanceid as courseid 
            FROM {dp_record_of_learning} 
            WHERE type = ' . record_of_learning::TYPE_COURSE . '
        )';

        $this->add_global_report_restriction_join('base', 'userid');

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = array();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_dp_course');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_dp_course');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_dp_course');
        $this->usedcomponents[] = 'totara_plan';
        $this->usedcomponents[] = 'totara_cohort';

        // Caching is disabled because visibility needs to be calculated using live data that cannot be cached.
        $this->cacheable = false;

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
     * @return array
     * @global object $CFG
     */
    protected function define_joinlist() {
        global $CFG, $DB;
        $joinlist = [];

        // to get access to position type constants
        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join.php');
        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_join_nonpruneable.php');

        /**
         * dp_plan has userid, dp_plan_course_assign has courseid. In order to
         * avoid multiplicity we need to join them together before we join
         * against the rest of the query
         */
        $joinlist[] = new rb_join(
            'dp_course',
            'LEFT',
            "(select
                    p.id as planid,
                    p.templateid as templateid,
                    p.userid as userid,
                    p.name as planname,
                    p.description as plandescription,
                    p.startdate as planstartdate,
                    p.enddate as planenddate,
                    p.status as planstatus,
                    pc.id as id,
                    pc.courseid as courseid,
                    pc.priority as priority,
                    pc.duedate as duedate,
                    pc.approved as approved,
                    pc.completionstatus as completionstatus,
                    pc.grade as grade
                  from
                    {dp_plan} p
                  inner join {dp_plan_course_assign} pc
                    on p.id = pc.planid)",
            'dp_course.userid = base.userid and dp_course.courseid = base.courseid',
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            ['base']
        );

        $joinlist[] = new rb_join(
            'dp_template',
            'LEFT',
            '{dp_template}',
            'dp_course.templateid = dp_template.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            ['dp_course', 'base']
        );

        $joinlist[] = new rb_join(
            'priority',
            'LEFT',
            '{dp_priority_scale_value}',
            'dp_course.priority = priority.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            ['dp_course', 'base']
        );
        // Ideally, this wouldn't have to be set as nonpruneable.
        // The prune_joins() method may need to be updated to not prune joins in required columns
        // or some other solution if we change/remove required columns in the future.
        $joinlist[] = new rb_join_nonpruneable(
            'course_completion',
            'LEFT',
            '{course_completions}',
            '(base.courseid = course_completion.course
                    AND base.userid = course_completion.userid)',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'criteria',
            'LEFT',
            '{course_completion_criteria}',
            '(criteria.course = base.courseid AND ' .
            'criteria.criteriatype = ' .
            COMPLETION_CRITERIA_TYPE_GRADE . ')',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'grade_items',
            'LEFT',
            '{grade_items}',
            '(grade_items.courseid = base.courseid AND ' .
            'grade_items.itemtype = \'course\')',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'grade_grades',
            'LEFT',
            '{grade_grades}',
            '(grade_grades.itemid = grade_items.id AND ' .
            'grade_grades.userid = base.userid)',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'grade_items'
        );
        // Join course_completion_history is deprecated and should be no longer used.
        // Check the course_completion_previous_completion column to see how to fetch this information instead.
        $joinlist[] = new rb_join(
            'course_completion_history',
            'LEFT',
            '(SELECT ' . $DB->sql_concat('userid', 'courseid') . ' uniqueid,
                    userid,
                    courseid,
                    COUNT(id) historycount
                    FROM {course_completion_history}
                    GROUP BY userid, courseid)',
            '(course_completion_history.courseid = base.courseid AND ' .
            'course_completion_history.userid = base.userid)',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );
        $joinlist[] = new rb_join(
            'enrolment',
            'LEFT',
            '(SELECT DISTINCT ue.userid, enrol.courseid, 1 AS enrolled
                    FROM {user_enrolments} ue
                    JOIN {enrol} enrol ON ue.enrolid = enrol.id)',
            '(enrolment.userid = base.userid AND ' .
            'enrolment.courseid = base.courseid)',
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        $this->add_core_course_tables($joinlist, 'base', 'courseid', 'INNER');
        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_totara_cohort_course_tables($joinlist, 'base', 'courseid');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for
     * $this->columnoptions
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [];

        $this->add_core_course_columns($columnoptions);

        $columnoptions[] = new rb_column_option(
            'plan',
            'name',
            get_string('planname', 'rb_source_dp_course'),
            'dp_course.planname',
            [
                'defaultheading' => get_string('plan', 'rb_source_dp_course'),
                'joins' => 'dp_course',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'planlink',
            get_string('plannamelink', 'rb_source_dp_course'),
            'dp_course.planname',
            [
                'defaultheading' => get_string('plan', 'rb_source_dp_course'),
                'joins' => 'dp_course',
                'displayfunc' => 'plan_link',
                'extrafields' => ['plan_id' => 'dp_course.planid']
            ]
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'startdate',
            get_string('planstartdate', 'rb_source_dp_course'),
            'dp_course.planstartdate',
            [
                'joins' => 'dp_course',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'enddate',
            get_string('planenddate', 'rb_source_dp_course'),
            'dp_course.planenddate',
            [
                'joins' => 'dp_course',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'status',
            get_string('planstatus', 'rb_source_dp_course'),
            'dp_course.planstatus',
            [
                'joins' => 'dp_course',
                'displayfunc' => 'plan_status'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'plan',
            'courseduedate',
            get_string('courseduedate', 'rb_source_dp_course'),
            'dp_course.duedate',
            [
                'joins' => 'dp_course',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'plan',
            'coursepriority',
            get_string('coursepriority', 'rb_source_dp_course'),
            'priority.name',
            [
                'joins' => 'priority',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'plan',
            'coursestatus',
            get_string('coursestatus', 'rb_source_dp_course'),
            'dp_course.approved',
            [
                'joins' => 'dp_course',
                'displayfunc' => 'plan_item_status'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'plan',
            'statusandapproval',
            get_string('progressandapproval', 'rb_source_dp_course'),
            "course_completion.status",
            [
                'joins' => ['course_completion', 'dp_course'],
                'displayfunc' => 'plan_course_completion_progress_and_approval',
                'defaultheading' => get_string('progress', 'rb_source_dp_course'),
                'extrafields' => [
                    'approved' => 'dp_course.approved',
                    'userid' => 'base.userid',
                    'courseid' => 'base.courseid'
                ],
            ]
        );

        $columnoptions[] = new rb_column_option(
            'course_completion',
            'timecompleted',
            get_string('coursecompletedate', 'rb_source_dp_course'),
            'course_completion.timecompleted',
            [
                'joins' => 'course_completion',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );

        $columnoptions[] = new rb_column_option(
            'course_completion',
            'coursestatus',
            get_string('completionstatus', 'rb_source_dp_course'),
            'course_completion.status',
            [
                'displayfunc' => 'course_completion_status',
                'outputformat' => 'text',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'template',
            'name',
            get_string('templatename', 'rb_source_dp_course'),
            'dp_template.shortname',
            [
                'defaultheading' => get_string('plantemplate', 'rb_source_dp_course'),
                'joins' => 'dp_template',
                'dbdatatype' => 'char',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'template',
            'startdate',
            get_string('templatestartdate', 'rb_source_dp_course'),
            'dp_template.startdate',
            [
                'joins' => 'dp_template',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'template',
            'enddate',
            get_string('templateenddate', 'rb_source_dp_course'),
            'dp_template.enddate',
            [
                'joins' => 'dp_template',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'plan',
            'courseprogress',
            get_string('courseprogress', 'rb_source_dp_course'),
            // use 'live' values except for completed plans
            "CASE WHEN dp_course.planstatus = " . DP_PLAN_STATUS_COMPLETE . "
                THEN
                    dp_course.completionstatus
                ELSE
                    course_completion.status
                END",
            [
                'joins' => [
                    'course_completion',
                    'dp_course'
                ],
                'displayfunc' => 'plan_course_completion_progress',
                'extrafields' => [
                    'userid' => 'base.userid',
                    'courseid' => 'base.courseid'
                ],
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'progresspercentage',
            get_string('progresspercentage', 'rb_source_dp_course'),
            "course_completion.status",
            [
                'joins' => ['course_completion'],
                'displayfunc' => 'plan_course_completion_progress_percentage',
                'extrafields' => [
                    'userid' => 'base.userid',
                    'courseid' => 'base.courseid'
                ],
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'enroldate',
            get_string('enrolled', 'totara_core'),
            "course_completion.timeenrolled",
            [
                'joins' => ['course_completion'],
                'displayfunc' => 'nice_date',
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'grade',
            get_string('grade', 'rb_source_course_completion'),
            'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
            [
                'joins' => [
                    'grade_grades',
                    'course_completion'
                ],
                'extrafields' => [
                    'maxgrade' => 'grade_grades.rawgrademax',
                    'rplgrade' => 'course_completion.rplgrade',
                    'status' => 'course_completion.status'
                ],
                'displayfunc' => 'course_grade_percent',
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'passgrade',
            get_string('passgrade', 'rb_source_course_completion'),
            'grade_items.gradepass',
            [
                'joins' => 'grade_items',
                'displayfunc' => 'percent',
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion',
            'gradestring',
            get_string('requiredgrade', 'rb_source_course_completion'),
            'CASE WHEN course_completion.status = ' . COMPLETION_STATUS_COMPLETEVIARPL . ' THEN course_completion.rplgrade
                      ELSE grade_grades.finalgrade END',
            [
                'joins' => [
                    'criteria',
                    'grade_grades'
                ],
                'displayfunc' => 'course_grade_string',
                'extrafields' => [
                    'gradepass' => 'criteria.gradepass',
                ],
                'defaultheading' => get_string('grade', 'rb_source_course_completion'),
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion_history',
            'course_completion_previous_completion',
            get_string('course_completion_previous_completion', 'rb_source_dp_course'),
            '(SELECT COUNT(*) FROM {course_completion_history} cch1 
                        WHERE cch1.userid = base.userid AND cch1.courseid = base.courseid)',
            [
                'defaultheading' => get_string('course_completion_previous_completion', 'rb_source_dp_course'),
                'displayfunc' => 'plan_course_completion_previous_completion',
                'extrafields' => [
                    'courseid' => 'base.courseid',
                    'userid' => 'base.userid',
                ],
            ]
        );
        $columnoptions[] = new rb_column_option(
            'course_completion_history',
            'course_completion_history_count',
            get_string('course_completion_history_count', 'rb_source_dp_course'),
            '(SELECT COUNT(*) FROM {course_completion_history} cch2
                        WHERE cch2.userid = base.userid AND cch2.courseid = base.courseid)',
            [
                'displayfunc' => 'integer',
            ]
        );

        $columnoptions[] = new rb_column_option(
            'course_completion',
            'duedate',
            get_string('coursecompleteduedate', 'rb_source_dp_course'),
            'course_completion.duedate',
            [
                'joins' => 'course_completion',
                'displayfunc' => 'nice_date',
                'dbdatatype' => 'timestamp'
            ]
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_totara_cohort_course_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'user',
            'id',
            get_string('userid', 'rb_source_dp_course'),
            'number'
        );
        $filteroptions[] = new rb_filter_option(
            'course',
            'courselink',
            get_string('coursetitle', 'rb_source_dp_course'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'plan',
            'courseprogress',
            get_string('completionstatus', 'rb_source_dp_course'),
            'select',
            [
                'selectfunc' => 'coursecompletion_status',
                'attributes' => rb_filter_option::select_width_limiter(),
            ]
        );
        $filteroptions[] = new rb_filter_option(
            'course_completion',
            'timecompleted',
            get_string('coursecompletedate', 'rb_source_dp_course'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'plan',
            'name',
            get_string('planname', 'rb_source_dp_course'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'plan',
            'courseduedate',
            get_string('courseduedate', 'rb_source_dp_course'),
            'date'
        );
        $filteroptions[] = new rb_filter_option(
            'course_completion',
            'grade',
            get_string('grade', 'rb_source_course_completion'),
            'number'
        );
        $filteroptions[] = new rb_filter_option(
            'course_completion',
            'passgrade',
            get_string('reqgrade', 'rb_source_course_completion'),
            'number'
        );
        $filteroptions[] = new rb_filter_option(
            'course_completion_history',
            'course_completion_history_count',
            get_string('course_completion_history_count', 'rb_source_dp_course'),
            'number'
        );

        $filteroptions[] = new rb_filter_option(
            'course_completion',
            'duedate',
            get_string('coursecompleteduedate', 'rb_source_dp_course'),
            'date'
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_totara_cohort_course_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = [];
        $paramoptions[] = new rb_param_option(
            'userid',
            'base.userid',
            'base'
        );
        $paramoptions[] = new rb_param_option(
            'rolstatus',
            // if plan complete use completion status from within plan
            // otherwise use 'live' completion status
            "(CASE WHEN dp_course.planstatus = " . DP_PLAN_STATUS_COMPLETE . "
                THEN
                    CASE WHEN dp_course.completionstatus >= " . COMPLETION_STATUS_COMPLETE . "
                    THEN
                        'completed'
                    ELSE
                        'active'
                    END
                ELSE
                    CASE WHEN course_completion.status >= " . COMPLETION_STATUS_COMPLETE . "
                    THEN
                        'completed'
                    ELSE
                        'active'
                    END
                END)",
            [
                'course_completion',
                'dp_course'
            ],
            'string'
        );
        $paramoptions[] = new rb_param_option(
            'enrolled',
            "enrolment.enrolled",
            ['enrolment']
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = [
            [
                'type' => 'course',
                'value' => 'coursetypeicon',
            ],
            [
                'type' => 'course',
                'value' => 'courselink',
            ],
            [
                'type' => 'plan',
                'value' => 'planlink',
            ],
            [
                'type' => 'plan',
                'value' => 'courseduedate',
            ],
            [
                'type' => 'plan',
                'value' => 'statusandapproval',
            ],
        ];
        return $defaultcolumns;
    }

    /**
     * Used to inject $userid into the base sql to improve base sub-query performance,
     * and to apply totara_visibility_where SQL restrictions.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {
        global $DB;

        $this->userid = (int)$report->get_param_value('userid');

        if ($this->userid) {
            // Visibility checks are only applied if viewing a single user's records.
            [$sql, $params] = totara_visibility_where(
                $this->userid,
                'course.id',
                'course.visible',
                'course.audiencevisible',
                'course',
                'course',
                false,
                true
            );
            $this->sourcewhere = "(course_completion.status > :notyetstarted OR ({$sql}))";

            $params['notyetstarted'] = COMPLETION_STATUS_NOTYETSTARTED;

            $this->sourceparams = $params;

            $this->sourcejoins = ['ctx', 'course_completion'];

            // Replace course_completion_history join after we know if we a looking at one user RoL or not.
            // This override will be removed when the join course_completion_history is removed.
            foreach ($this->joinlist as $key => $join) {
                // Join course_completion_history is deprecated and should be no longer used.
                // Check the course_completion_previous_completion column to see how to fetch this information instead.
                if ($join->name === 'course_completion_history') {
                    $this->joinlist[$key] = new rb_join(
                        'course_completion_history',
                        'LEFT',
                        "(SELECT " . $DB->sql_concat('userid', 'courseid') . " uniqueid,
                            userid,
                            courseid,
                            COUNT(id) AS historycount
                        FROM {course_completion_history}
                        WHERE userid = {$this->userid}
                        GROUP BY userid, courseid)",
                        'course_completion_history.courseid = base.courseid',
                        REPORT_BUILDER_RELATION_ONE_TO_ONE
                    );
                    break;
                }
            }
        }
    }

    public function rb_filter_coursecompletion_status() {
        global $COMPLETION_STATUS;

        $out = [];
        foreach ($COMPLETION_STATUS as $code => $statusstring) {
            $out[$code] = get_string($statusstring, 'completion');
        }
        return $out;
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('recordoflearning');
    }

    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        $task = new \totara_plan\task\update_record_of_learning_task();
        $task->execute();
    }
}

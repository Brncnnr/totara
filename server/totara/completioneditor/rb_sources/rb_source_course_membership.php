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
 * @package totara_completioneditor
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');

class rb_source_course_membership extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_course\rb\traits\post_config;
    use \core_course\rb\traits\required_joins;
    use \core_tag\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_reportbuilder\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;

    protected $courseid;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = $this->define_base();
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = $this->define_sourcetitle();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->requiredjoins = $this->define_requiredjoins();
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_course_membership');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_course_membership');
        $this->usedcomponents[] = 'totara_completioneditor';
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

    protected function define_sourcetitle() {
        return get_string('sourcetitle', 'rb_source_course_membership');
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
     * Columns required to be present in the report.
     *
     * @return array
     */
    protected function define_requiredcolumns() {
        return [];
    }

    /**
     * Required Joins.
     * Joins required for totara_visibility_where() to function correctly in post_config.
     *
     * @return array
     */
    protected function define_requiredjoins() {
        $requiredjoins = [];
        $this->add_audiencevisibility_joins($requiredjoins);

        return $requiredjoins;
    }

    protected function define_base(int $course_id = null) {
        global $DB;

        $global_restriction_join = $this->get_global_report_restriction_join('basesub', 'userid');

        $course_type = container_course\course::get_type();
        $site_type = container_site\site::get_type();

        $uniqueid = $DB->sql_concat_join("','", array('userid', 'courseid'));

        $course_sql = '';
        if (!empty($course_id)) {
            $course_sql = " AND c.id = {$course_id}";
        }

        $sql = "(SELECT " . $uniqueid . " AS id, userid, courseid
                    FROM (SELECT ue.userid AS userid, e.courseid AS courseid
                           FROM {user_enrolments} ue
                           JOIN {enrol} e ON ue.enrolid = e.id
                           JOIN {course} c ON e.courseid = c.id AND 
                                (c.containertype = '{$course_type}' OR c.containertype = '{$site_type}')
                                {$course_sql}
                          UNION
                         SELECT cc.userid AS userid, cc.course AS courseid
                           FROM {course_completions} cc
                           JOIN {course} c ON cc.course = c.id AND 
                                (c.containertype = '{$course_type}' OR c.containertype = '{$site_type}')
                                {$course_sql}
                          UNION
                         SELECT cch.userid AS userid, cch.courseid AS courseid
                           FROM {course_completion_history} cch
                           JOIN {course} c ON cch.courseid = c.id AND 
                                (c.containertype = '{$course_type}' OR c.containertype = '{$site_type}')
                                {$course_sql}
                          UNION
                         SELECT p1.userid AS userid, pca1.courseid AS courseid
                           FROM {dp_plan_course_assign} pca1
                           JOIN {dp_plan} p1 ON pca1.planid = p1.id
                           JOIN {course} c ON pca1.courseid = c.id AND
                                (c.containertype = '{$course_type}' OR c.containertype = '{$site_type}')
                                {$course_sql}
                    )
                basesub
                {$global_restriction_join})";

        return $sql;
    }

    /**
     * Creates the array of rb_join objects required for this->joinlist.
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = array();

        $this->add_context_tables($joinlist, 'course', 'id', CONTEXT_COURSE, 'INNER');
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'base', 'courseid', 'INNER');

        return $joinlist;
    }

    /**
     * Creates the array of rb_column_option objects required for $this->columnoptions.
     *
     * @return array
     */
    protected function define_columnoptions() {

        $columnoptions = array(
            new rb_column_option(
                'coursemembership',
                'editcoursecompletion',
                get_string('coursecompletionedit', 'totara_completioneditor'),
                'base.id',
                array(
                    'displayfunc' => 'completioneditor_edit_completion',
                    'extrafields' => array(
                        'userid' => 'base.userid',
                        'courseid' => 'base.courseid',
                    ),
                    'noexport' => true,
                )
            ),
            new rb_column_option(
                'coursemembership',
                'enrolled',
                get_string('enrolled', 'rb_source_course_membership'),
                '(SELECT CASE WHEN COUNT(ue.id) >= 1 THEN 1 ELSE 0 END
                        FROM {user_enrolments} ue
                        JOIN {enrol} e ON e.id = ue.enrolid
                        WHERE ue.userid = base.userid
                        AND e.courseid = course.id
                        AND e.status = 0)',
                array(
                    'displayfunc' => 'yes_or_no',
                    'issubquery' => true,
                    'dbdatatype' => 'boolean',
                )
            )
        );

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Creates the array of rb_filter_option objects required for $this->filteroptions.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = array();

        $filteroptions[] = new \rb_filter_option(
            'coursemembership',
            'enrolled',
            get_string('enrolled', 'rb_source_course_membership'),
            'select',
            array(
                'selectchoices' => array(0 => get_string('no'), 1 => get_string('yes')),
                'simplemode' => true
            )
        );

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);

        return $filteroptions;
    }

    /**
     * Creates the array of rb_content_option objects required for $this->contentoptions.
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
     * Creates the array of rb_param_option objects required for $this->paramoptions.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = array();

        $paramoptions[] = new rb_param_option(
            'userid',
            'base.userid',
            'base'
        );
        $paramoptions[] = new rb_param_option(
            'courseid',
            'base.courseid',
            'base'
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
                'value' => 'coursetypeicon',
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
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
                'type' => 'course',
                'value' => 'fullname',
            ),
        );

        return $defaultfilters;
    }

    /**
     * Display the edit completion link
     *
     * @deprecated Since Totara 12.0
     * @param $id
     * @param $row
     * @param $isexport
     * @return string
     */
    public function rb_display_edit_completion($id, $row, $isexport) {
        debugging('rb_source_course_membership::rb_display_edit_completion has been deprecated since Totara 12.0. Use totara_completioneditor\rb\display\completioneditor_edit_completion::display', DEBUG_DEVELOPER);
        // Ignores $id == course_completions->id, because the user might have been unassigned and only history records exist.
        $url = new moodle_url('/totara/completioneditor/edit_course_completion.php',
            array('courseid' => $row->courseid, 'userid' => $row->userid));
        return html_writer::link($url, get_string('coursecompletionedit', 'totara_completioneditor'));
    }

    /**
     * Returns expected result for column_test.
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        if (get_class($this) === 'rb_source_course_membership') {
            return 2; // One record is in course_completion, one in course_completion_history, with different userids.
        }
        return parent::phpunit_column_test_expected_count($columnoption);
    }

    /**
     * Used to inject $userid into the base sql to improve base sub-query performance,
     * and to apply totara_visibility_where SQL restrictions.
     *
     * @param reportbuilder $report
     */
    public function post_params(reportbuilder $report) {
        $this->courseid = (int) $report->get_param_value('courseid');

        if (!empty($this->courseid)) {
            $this->base = $this->define_base($this->courseid);
        }
    }
}

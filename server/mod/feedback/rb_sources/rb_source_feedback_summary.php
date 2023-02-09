<?php // $Id$

/*
 * mod/feedback/rb_sources/rb_source_feedback_summary.php
 *
 * Report Builder source for generating summary (high-level) reports on feedback
 * activities.
 *
 * @copyright Catalyst IT Limited
 * @author Simon Coggins
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package totara
 */

defined('MOODLE_INTERNAL') || die();

class rb_source_feedback_summary extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_tag\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid', 'auser');

        $this->base = '{feedback_completed}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_feedback_summary');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_feedback_summary');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_feedback_summary');
        $this->usedcomponents[] = 'mod_feedback';
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

    /**
     * @inheritDoc
     */
    protected function define_joinlist() {
        global $CFG, $DB;

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'feedback']);

        // get the trainer role's id (or set a dummy value)
        $trainerroleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
        if (!$trainerroleid) {
            $trainerroleid = 0;
        }

        // to get access to position type constants
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

        // joinlist for this source
        $joinlist = array(
            new rb_join(
                'feedback',
                'LEFT',
                '{feedback}',
                'feedback.id = base.feedback',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'cmdl',
                'LEFT',
                '{course_modules}',
                "(cmdl.module = {$moduleid} AND cmdl.instance = feedback.id)",
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'feedback'
            ),

            /**
             * @deprecated since Totara 17.0
             */
            new rb_join(
                'session_value',
                'LEFT',
                // subquery as table
                "(SELECT i.feedback, v.value
                    FROM {feedback_item} i
                    JOIN {feedback_value} v
                        ON v.item=i.id AND i.typ='trainer')",
                'session_value.feedback = base.feedback',
                // potentially could be multiple trainer questions
                // in a feedback instance
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            ),

            /**
             * @deprecated since Totara 17.0
             */
            new rb_join(
                'sessiontrainer',
                'LEFT',
                '{facetoface_session_roles}',
                '(sessiontrainer.userid = ' .
                    $DB->sql_cast_char2int('session_value.value', true) . ' AND ' .
                    "sessiontrainer.roleid = $trainerroleid)",
                // potentially multiple trainers in a session
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'session_value'
            ),

            new rb_join(
                'ctx',
                'INNER',
                '{context}',
                'ctx.instanceid = course.id AND ctx.contextlevel = ' . CONTEXT_COURSE,
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course'
            ),
            new rb_join(
                'role_assignments',
                'LEFT',
                '{role_assignments}',
                'ctx.id = role_assignments.contextid AND role_assignments.roleid = '. $trainerroleid,
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'ctx'
            ),
            new rb_join(
                'trainer',
                'LEFT',
                '{user}',
                'trainer.id = role_assignments.userid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'role_assignments'
            ),
            new rb_join(
                'trainer_job_assignment',
                'LEFT',
                '{job_assignment}',
                '(trainer_job_assignment.userid = role_assignments.userid AND trainer_job_assignment.sortorder = 1)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'role_assignments'
            ),
            new rb_join(
                'trainer_position',
                'LEFT',
                '{pos}',
                'trainer_position.id = ' .
                    'trainer_job_assignment.positionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'trainer_job_assignment'
            ),
            new rb_join(
                'trainer_organisation',
                'LEFT',
                '{org}',
                'trainer_organisation.id = ' .
                    'trainer_job_assignment.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'trainer_job_assignment'
            ),
        );

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_core_course_tables($joinlist, 'feedback', 'course');
        // requires the course join
        $this->add_core_course_category_tables($joinlist, 'course', 'category');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_tag_tables('core', 'course', $joinlist, 'feedback', 'course');
        $this->add_core_tag_tables('core', 'course_modules', $joinlist, 'cmdl', 'id');

        return $joinlist;
    }

    /**
     * @inheritDoc
     */
    protected function define_columnoptions() {
        global $DB;

        $usednamefields = totara_get_all_user_name_fields_join('trainer', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('trainer');

        $columnoptions = array(
            new rb_column_option(
                'responses',
                'timecompleted',
                get_string('timecompleted', 'rb_source_feedback_summary'),
                'base.timemodified',
                array('displayfunc' => 'nice_datetime', 'dbdatatype' => 'timestamp')
            ),
            new rb_column_option(
                'feedback',
                'name',
                get_string('feedbackactivity', 'rb_source_feedback_summary'),
                'feedback.name',
                array('joins' => 'feedback',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'feedback',
                'activitylink',
                get_string('feedbackactivitylink', 'rb_source_feedback_summary'),
                'feedback.name',
                [
                    'joins' => ['feedback', 'cmdl'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'activity_name_link',
                    'extrafields' => [
                        'id' => 'cmdl.id'
                    ],
                    'defaultheading' => get_string('feedbackactivity', 'rb_source_feedback_summary'),
                ]
            ),
            new rb_column_option(
                'trainer',
                'id',
                get_string('trainerid', 'rb_source_feedback_summary'),
                'role_assignments.userid',
                [
                    'joins' => 'role_assignments',
                    'displayfunc' => 'integer'
                ]
            ),
            new rb_column_option(
                'trainer',
                'fullname',
                get_string('trainerfullname', 'rb_source_feedback_summary'),
                "CASE WHEN trainer.id IS NULL THEN NULL ELSE " . $DB->sql_concat_join("' '", $usednamefields) . " END",
                [
                    'joins' => 'trainer',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'extrafields' => array_merge(
                        ['id' => 'trainer.id'],
                        $allnamefields
                    ),
                    'displayfunc' => 'user'
                ]
            ),
            new rb_column_option(
                'trainer',
                'namelink',
                get_string('trainerfullnamelink', 'rb_source_feedback_summary'),
                $DB->sql_concat_join("' '", $usednamefields),
                [
                    'joins' => 'trainer',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'extrafields' => array_merge(
                        ['id' => 'trainer.id', 'deleted' => 'trainer.deleted'],
                        $allnamefields
                    ),
                    'displayfunc' => 'user_link',
                    'defaultheading' => get_string('trainerfullname', 'rb_source_feedback_summary'),
                ]
            ),
            new rb_column_option(
                'trainer',
                'organisationid',
                get_string('trainerorgid', 'rb_source_feedback_summary'),
                'trainer_job_assignment.organisationid',
                array('joins' => 'trainer_job_assignment',
                      'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'trainer',
                'organisation',
                get_string('trainerorg', 'rb_source_feedback_summary'),
                'trainer_organisation.fullname',
                array('joins' => 'trainer_organisation',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
            new rb_column_option(
                'trainer',
                'positionid',
                get_string('trainerposid', 'rb_source_feedback_summary'),
                'trainer_job_assignment.positionid',
                array('joins' => 'trainer_job_assignment',
                      'displayfunc' => 'integer')
            ),
            new rb_column_option(
                'trainer',
                'position',
                get_string('trainerpos', 'rb_source_feedback_summary'),
                'trainer_position.fullname',
                array('joins' => 'trainer_position',
                      'dbdatatype' => 'char',
                      'outputformat' => 'text',
                      'displayfunc' => 'format_string')
            ),
        );
        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_core_course_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_tag_columns('core', 'course', $columnoptions);
        $this->add_core_tag_columns('core', 'course_modules', $columnoptions);

        return $columnoptions;
    }

    /**
     * @inheritDoc
     */
    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'feedback',
                'name',
                get_string('feedbackname', 'rb_source_feedback_summary'),
                'text'
            ),
            new rb_filter_option(
                'responses',
                'timecompleted',
                get_string('timecompleted', 'rb_source_feedback_summary'),
                'date'
            ),
            new rb_filter_option(
                'trainer',
                'fullname',
                get_string('trainerfullname', 'rb_source_feedback_summary'),
                'text'
            ),
            new rb_filter_option(
                'trainer',
                'organisationid',
                get_string('trainerorg', 'rb_source_feedback_summary'),
                'select',
                array(
                    'selectfunc' => 'organisations_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'trainer',
                'positionid',
                get_string('trainerpos', 'rb_source_feedback_summary'),
                'select',
                array(
                    'selectfunc' => 'positions_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
        );

        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_core_course_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_core_tag_filters('core', 'course', $filteroptions);
        $this->add_core_tag_filters('core', 'course_modules', $filteroptions);

        return $filteroptions;
    }

    /**
     * @inheritDoc
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('responsetime', 'rb_source_feedback_summary'),
            'base.timemodified'
        );
        return $contentoptions;
    }

    /**
     * @inheritDoc
     */
    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',         // parameter name
                'base.userid'     // field
            ),
            new rb_param_option(
                'courseid',
                'feedback.course',
                'feedback'
            ),
        );
        return $paramoptions;
    }

    /**
     * @inheritDoc
     */
    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('user', 'rb_source_feedback_summary'),
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursename', 'rb_source_feedback_summary'),
            ),
            array(
                'type' => 'feedback',
                'value' => 'name',
            ),
            array(
                'type' => 'responses',
                'value' => 'timecompleted',
            ),
        );
        return $defaultcolumns;
    }

    /**
     * @inheritDoc
     */
    protected function define_defaultfilters() {
        $defaultfilters = array(
            array(
                'type' => 'course',
                'value' => 'fullname',
            ),
            array(
                'type' => 'user',
                'value' => 'fullname',
            ),
            array(
                'type' => 'feedback',
                'value' => 'name',
                'advanced' => 1,
            ),
            array(
                'type' => 'responses',
                'value' => 'timecompleted',
                'advanced' => 1,
            ),
        );
        return $defaultfilters;
    }
}
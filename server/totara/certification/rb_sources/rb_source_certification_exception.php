<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

class rb_source_certification_exception extends rb_base_source {
    use \core_course\rb\source\report_trait;
    use \core_user\rb\source\report_trait;
    use \totara_cohort\rb\source\report_trait;
    use \totara_job\rb\source\report_trait;
    use \totara_certification\rb\source\certification_trait;

    public function __construct() {
        $this->base = '{prog_exception}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_certification_exception');
        $this->sourcejoins = $this->get_source_joins();
        $this->sourcewhere = $this->define_sourcewhere();
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_certification_exception');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_certification_exception');
        $this->usedcomponents[] = 'totara_certification';
        $this->usedcomponents[] = 'totara_program';
        $this->usedcomponents[] = 'totara_cohort'; // Needed for visibility.

        parent::__construct();
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('certifications');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return false;
    }

    protected function get_source_joins() {
        return array('certif');
    }

    protected function define_joinlist() {
        $joinlist = [];
        $this->add_totara_certification_tables($joinlist, 'base', 'programid', 'INNER');
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        $this->add_totara_job_tables($joinlist, 'base', 'userid');
        $this->add_core_course_category_tables($joinlist, 'certif', 'category');
        $this->add_totara_cohort_program_tables($joinlist, 'certif', 'id');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions[] = new rb_column_option(
            'exceptioninfo',
            'exceptiontype',
            get_string('exceptiontype','rb_source_certification_exception'),
            'base.exceptiontype',
            [
                'displayfunc' => 'certif_exception_type'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'exceptioninfo',
            'timeraised',
            get_string('timeraised', 'rb_source_certification_exception'),
            'base.timeraised',
            [
                'displayfunc' => 'nice_datetime',
                'dbdatatype' => 'timestamp',
            ]
        );

        // Include some standard columns.
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);
        $this->add_core_course_category_columns($columnoptions, 'course_category', 'certif', 'programcount');
        $this->add_totara_cohort_program_columns($columnoptions);
        $this->add_totara_certification_columns($columnoptions, 'certif');

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = [

            new rb_filter_option(
                'exceptioninfo',
                'exceptiontype',
                get_string('exceptiontype', 'rb_source_certification_exception'),
                'select',
                [
                    'selectchoices' => self::get_exception_options(),
                    'attributes' => rb_filter_option::select_width_limiter(),
                    'simplemode' => true,
                ]
            ),

            new rb_filter_option(
                'exceptioninfo',
                'timeraised',
                get_string('timeraised', 'rb_source_certification_exception'),
                'date'
            ),
        ];

        // Include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'userid');
        $this->add_totara_certification_filters($filteroptions);
        $this->add_totara_cohort_program_filters($filteroptions);
        $this->add_core_course_category_filters($filteroptions);

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        $contentoptions[] = new rb_content_option(
            'date',
            get_string('completeddate', 'rb_source_program_completion'),
            ['date' => 'base.timeraised']
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = [
            new rb_param_option(
                'programid',
                'certif.id'
            ),
            new rb_param_option(
                'visible',
                'certif.visible'
            ),
            new rb_param_option(
                'category',
                'certif.category'
            ),
        ];

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = [
            [
                'type' => 'certif',
                'value' => 'proglinkicon',
            ],
            [
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'exceptioninfo',
                'value' => 'exceptiontype',
            ],
            [
                'type' => 'exceptioninfo',
                'value' => 'timeraised',
            ],
        ];

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = [
            [
                'type' => 'program',
                'value' => 'fullname',
                'advanced' => 0,
            ],
            [
                'type' => 'exceptioninfo',
                'value' => 'exceptiontype',
                'advanced' => 0,
            ],
            [
                'type' => 'exceptioninfo',
                'value' => 'timeraised',
                'advanced' => 1,
            ],
        ];

        return $defaultfilters;
    }

    protected function define_sourcewhere() {
        return '(certif.id IS NOT NULL)';
    }

    /**
     * returns array with exception code as key and text label as value
     * @return array
     */
    public static function get_exception_options() {
        $options = [
            \totara_program\exception\manager::EXCEPTIONTYPE_TIME_ALLOWANCE          => get_string('timeallowance', 'totara_program'),
            \totara_program\exception\manager::EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN => get_string('completiontimeunknown', 'totara_program'),
            \totara_program\exception\manager::EXCEPTIONTYPE_UNKNOWN                 => get_string('unknown', 'totara_program'),
            \totara_program\exception\manager::EXCEPTIONTYPE_DUPLICATE_COURSE        => get_string('exceptiontypeduplicatecourse', 'totara_program'),
        ];

        return $options;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $certid = $DB->insert_record('certif', ['learningcomptype' => 1, 'recertifydatetype' => 2]);
        $programid = $DB->insert_record('prog', ['certifid' => $certid]);
        $DB->insert_record('prog_exception', ['programid' => $programid, 'userid' => 2, 'exceptiontype' => 2]);
    }
}

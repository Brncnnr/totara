<?php
/**
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_competency
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

// Currently only required to re-use the constants
global $CFG;
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

class rb_source_competency extends rb_base_source {

    public function __construct() {
        $this->base = '{comp}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_competency');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_competency');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_competency');
        $this->usedcomponents[] = 'totara_competency';

        parent::__construct();
    }

    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('competencies');
    }

    public function global_restrictions_supported() {
        return false;
    }

    /**
     * Define table join list
     * @return array
     */
    protected function define_joinlist() {

        $joinlist = [
            new rb_join(
                'framework',
                'INNER',
                '{comp_framework}',
                'base.frameworkid = framework.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'scale_assignments',
                'LEFT',
                '{comp_scale_assignments}',
                'base.frameworkid = scale_assignments.frameworkid',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'scale',
                'LEFT',
                '{comp_scale}',
                'scale_assignments.scaleid = scale.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'scale_assignments'
            ),
            new rb_join(
                'type',
                'LEFT',
                '{comp_type}',
                'base.typeid = type.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'parent',
                'LEFT',
                '{comp}',
                'base.parentid = parent.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'assign_availability_self',
                'LEFT',
                '{comp_assign_availability}',
                'base.id = assign_availability_self.comp_id AND assign_availability_self.availability = '
                    . \competency::ASSIGNMENT_CREATE_SELF,
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'assign_availability_other',
                'LEFT',
                '{comp_assign_availability}',
                'base.id = assign_availability_other.comp_id AND assign_availability_other.availability = '
                    . \competency::ASSIGNMENT_CREATE_OTHER,
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
        ];

        return $joinlist;
    }

    /**
     * Define column options
     * @return array
     */
    protected function define_columnoptions(): array {

        $columnoptions = [
            new rb_column_option(
                'competency',
                'fullname',
                get_string('competency_name', 'rb_source_competency'),
                'base.fullname',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'competency',
                'idnumber',
                get_string('competency_id_number', 'rb_source_competency'),
                'base.idnumber',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'competency',
                'timemodified',
                get_string('competency_last_modified', 'rb_source_competency'),
                'base.timemodified',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'competency',
                'description',
                get_string('competency_description', 'rb_source_competency'),
                'base.description',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'editor_textarea',
                ]
            ),
            new rb_column_option(
                'competency',
                'framework_id',
                get_string('competency_framework_id', 'rb_source_competency'),
                'base.frameworkid',
                [
                    'displayfunc' => 'integer',
                    'hidden' => 1,
                    'selectable' => false
                ]
            ),
            new rb_column_option(
                'framework',
                'fullname',
                get_string('competency_framework', 'rb_source_competency'),
                'framework.fullname',
                [
                    'joins' => 'framework',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'framework',
                'idnumber',
                get_string('competency_framework_id_number', 'rb_source_competency'),
                'framework.idnumber',
                [
                    'joins' => 'framework',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'scale',
                'name',
                get_string('competency_scale_name', 'rb_source_competency'),
                'scale.name',
                [
                    'joins' => ['scale_assignments', 'scale'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_text',
                ]
            ),
            new rb_column_option(
                'parent_competency',
                'idnumber',
                get_string('competency_parent_id_number', 'rb_source_competency'),
                'parent.idnumber',
                [
                    'joins' => 'parent',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'parent_competency',
                'fullname',
                get_string('competency_parent_name', 'rb_source_competency'),
                'parent.fullname',
                [
                    'joins' => 'parent',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'type',
                'idnumber',
                get_string('competency_type_id_number', 'rb_source_competency'),
                'type.idnumber',
                [
                    'joins' => 'type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'type',
                'fullname',
                get_string('competency_type_name', 'rb_source_competency'),
                'type.fullname',
                [
                    'joins' => 'type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'assign_availability',
                'assign_availability',
                get_string('assign_availability', 'rb_source_competency'),
                "CASE
                    WHEN assign_availability_self.availability IS NULL AND assign_availability_other.availability IS NULL
                        THEN 'none'
                    WHEN assign_availability_self.availability IS NOT NULL AND assign_availability_other.availability IS NOT NULL
                        THEN 'any'
                    WHEN assign_availability_self.availability IS NOT NULL
                        THEN 'self'
                    WHEN assign_availability_other.availability IS NOT NULL
                        THEN 'other'
                END",
                [
                    'joins' => ['assign_availability_self', 'assign_availability_other'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'assignment_availability',
                ]
            ),

        ];

        return $columnoptions;
    }

    /**
     * Define filter options
     * @return array
     */
    protected function define_filteroptions(): array {
        $filteroptions = [
            new rb_filter_option(
                'competency',
                'framework_id',
                get_string('competency_framework', 'rb_source_competency'),
                'select',
                [
                    'selectfunc' => 'competency_frameworks',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
        ];

        return $filteroptions;
    }

    /**
     * Define default columns
     * @return array
     */
    protected function define_defaultcolumns(): array {
        return [
            [
                'type' => 'competency',
                'value' => 'fullname'
            ],
            [
                'type' => 'competency',
                'value' => 'idnumber'
            ],
            [
                'type' => 'framework',
                'value' => 'fullname'
            ],
            [
                'type' => 'type',
                'value' => 'fullname'
            ],
            [
                'type' => 'scale',
                'value' => 'name'
            ],
            [
                'type' => 'parent_competency',
                'value' => 'fullname'
            ],
            [
                'type' => 'assign_availability',
                'value' => 'assign_availability'
            ],
        ];
    }

    /**
     * Define default filters
     * @return array
     */
    protected function define_defaultfilters(): array {
        return [
            [
                'type' => 'competency',
                'value' => 'framework_id',
            ],
        ];
    }

    /**
     * @return array
     */
    public function rb_filter_competency_frameworks(): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

        $frameworks = [];
        foreach ((new competency())->get_frameworks() as $id => $record) {
            $frameworks[$id] = $record->fullname;
        }
        return $frameworks;
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

        // column_test already creates competencies and frameworks, so expected row count is the number of competencies.
        global $DB;
        return $DB->count_records('comp');
    }

}

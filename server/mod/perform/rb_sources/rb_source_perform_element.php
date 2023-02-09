<?php
/**
 *
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 *
 */

use core\entity\user;
use mod_perform\models\activity\element_plugin;
use mod_perform\rb\traits\activity_trait;
use mod_perform\rb\traits\course_visibility_trait;
use mod_perform\rb\traits\element_trait;
use mod_perform\rb\traits\section_element_trait;
use mod_perform\rb\traits\section_trait;
use mod_perform\rb\util;
use totara_core\advanced_feature;
use totara_core\entity\relationship;
use totara_core\relationship\relationship as relationship_model;

defined('MOODLE_INTERNAL') || die();

/**
 * Performance response report.
 *
 * Class rb_source_perform_element
 */
class rb_source_perform_element extends rb_base_source {
    use section_element_trait;
    use element_trait;
    use section_trait;
    use activity_trait;
    use course_visibility_trait;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_element');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_element');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_element');

        $this->usedcomponents[] = 'mod_perform';
        $this->base = '{perform_element}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        $this->add_element_to_base();

        $this->add_section_element(
            new rb_join(
                'section_element',
                'INNER',
                '{perform_section_element}',
                '(base.parent IS NULL AND base.id = section_element.element_id) 
                    OR (base.parent = section_element.element_id)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            )
        );

        $this->add_section(
            new rb_join(
                'perform_section',
                'INNER',
                '{perform_section}',
                'section_element.section_id = perform_section.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'section_element'
            )
        );

        $this->add_activity(
            new rb_join(
                'perform',
                'INNER',
                '{perform}',
                'perform_section.activity_id = perform.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'perform_section'
            )
        );

        $this->add_course_visibility('perform');

        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();

        $non_respondable_elements = array_keys(element_plugin::get_does_not_display_responses_plugins());
        if (!empty($non_respondable_elements)) {
            $sql = $DB->sql_not_in($non_respondable_elements);
            $this->sourcewhere = 'base.plugin_name ' . $sql->get_sql();
            $this->sourceparams = $sql->get_params();
        }

        // Caching is disabled because visibility needs to be calculated using live data that cannot be cached.
        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * Define join table list.
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [];

        return $joinlist;
    }

    /**
     * Define the column options available for this report.
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [
        ];

        return $columnoptions;
    }

    /**
     * Define the filter options available for this report.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [];

        $filteroptions[] = new rb_filter_option(
            'section',
            'involved_relationships',
            get_string('element_reporting_title_responding_relationship', 'mod_perform'),
            'correlated_subquery_select',
            [
                'simplemode' => true,
                'selectchoices' => $this->get_responding_relationship_options(),
                'searchfield' => 'cr.id',
                'subquery' => "EXISTS(SELECT 'x'
                                        FROM {perform_section_relationship} sr
                                        JOIN {totara_core_relationship} cr ON cr.id = sr.core_relationship_id
                                        WHERE (%1\$s) = sr.section_id
                                          AND (%2\$s)
                                          AND sr.can_answer = 1)",
            ],
            'perform_section.id',
            'perform_section'
        );

        return $filteroptions;
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
        return self::get_default_filters();
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns() {
        return [
            [
                'type' => 'element',
                'value' => 'title',
                'heading' => get_string('element_title', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'type',
                'heading' => get_string('element_type', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'identifier',
                'heading' => get_string('element_identifier', 'mod_perform'),
            ],
            [
                'type' => 'section',
                'value' => 'title',
                'heading' => get_string('section_title', 'mod_perform'),
            ],
            [
                'type' => 'activity',
                'value' => 'name',
                'heading' => get_string('activity_name', 'mod_perform'),
            ],
        ];
    }

    /**
     * The default filters for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_filters() {
        return [
            [
                'type' => 'element',
                'value' => 'title',
            ],
            [
                'type' => 'element',
                'value' => 'type',
            ],
            [
                'type' => 'element',
                'value' => 'identifier',
            ],
            [
                'type' => 'section',
                'value' => 'title',
            ],
            [
                'type' => 'activity',
                'value' => 'name',
            ],
        ];
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        return $contentoptions;
    }

    /**
     * Define the available param options for this report.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = [
            new rb_param_option(
                'activity_id',
                'perform_section.activity_id',
                'perform_section'
            ),
            new rb_param_option(
                'element_id',
                'base.id',
                'section_element'
            ),
            new rb_param_option(
                'element_identifier',
                'base.identifier_id'
            )
        ];

        return $paramoptions;
    }

    /**
     * Disable subject reports if the performance activities feature is disabled.
     *
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('performance_activities');
    }

    /**
     * Global report restrictions are implemented in this source.
     *
     * @return boolean
     */
    public function global_restrictions_supported() {
        // Not relevant as elements aren't user data.
        return false;
    }

    public function post_config(reportbuilder $report) {
        $restrictions = util::get_report_on_subjects_activities_sql($report->reportfor, "perform.id");
        $restrictions = $this->create_course_visibility_restrictions($report, $restrictions);

        $report->set_post_config_restrictions($restrictions);
    }

    /**
     * Inject column_test data into database.
     *
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        // This creates a complete activity with two elements and related tables we need to test this source.
        $perform_generator = \mod_perform\testing\generator::instance();
        $perform_generator->create_subject_instance([
            'activity_name' => 'Weekly catchup',
            'subject_is_participating' => true,
            'subject_user_id' => user::repository()->get()->last()->id,
            'include_questions' => true,
        ]);
    }

    /**
     * Adjust expected result of column test for columns in this source.
     *
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        // create_subject_instances() creates an activity with two elements, so should expect two results from this report.
        return 2;
    }

    /**
     * Get an array relationship names keyed by id.
     *
     * @return string[] [id => Display Name]
     */
    private function get_responding_relationship_options(): array {
        return relationship::repository()
            ->order_by('idnumber')
            ->get()
            ->key_by('id')
            ->map(function (relationship $relationship) {
                return (new relationship_model($relationship))->get_name();
            })->all(true);
    }
}

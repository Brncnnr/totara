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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 *
 */

use container_perform\perform as perform_container;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\participant_source;
use mod_perform\rb\traits\activity_trait;
use mod_perform\rb\traits\element_trait;
use mod_perform\rb\traits\participant_instance_trait;
use mod_perform\rb\traits\section_element_trait;
use mod_perform\rb\traits\section_trait;
use mod_perform\rb\traits\subject_instance_trait;
use mod_perform\state\participant_section\complete;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Performance response report.
 *
 * Class rb_source_perform_response
 */
class rb_source_perform_response_base extends rb_base_source {

    use participant_instance_trait;
    use subject_instance_trait;
    use section_element_trait;
    use element_trait;
    use section_trait;
    use activity_trait;

    private $default_context;

    /**
     * Extra fields passed to the response column, can be extended by element plugins
     *
     * @var string[]
     */
    protected $response_extra_fields = [
        'element_id' => "perform_element.id",
        'element_type' => "perform_element.plugin_name",
        'element_data' => "perform_element.data",
        'element_context_id' => "perform_element.context_id",
        'response_id' => "base.id",
    ];

    /**
     * If an element plugin wants to override the response fields it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_response = [];

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB, $CFG;

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->default_category_context = context_coursecat::instance(perform_container::get_default_category_id());

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('subject_instance', 'subject_user_id', 'subject_instance');

        // This source is not available for user selection - it is used by the embedded report only.
        $this->selectable = false;

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_response_base');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_response_base');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_response_base');

        // We do have sourcejoins so make sure it's used by having a sourcewhere
        $this->sourcewhere = '1 = 1';

        $this->usedcomponents[] = 'mod_perform';

        // This report query can get stuck on some MariaDB versions. Adjusting optimizer_search_depth is necessary.
        $mariadb_force_search_depth = $CFG->mariadb_search_depth_perform_response_reporting ?? 0;
        $optimizer_hint = $DB->get_optimizer_hint('mariadb_force_search_depth', $mariadb_force_search_depth);

        /*
         *  Base is split into two queries:
         *    - Responses on sections that a participant has a completed participant_section for (that can be
         *      both direct and referenced responses).
         *    - Referenced responses (e.g. aggregation element) on any sections. These only exist when the participant
         *      has completed the source section, so no need to check that.
         */
        $this->base = $optimizer_hint . "(
            SELECT es.* FROM (
                SELECT completed_section_responses.*
                FROM {perform_element_response} completed_section_responses
                JOIN {perform_section_element} se ON completed_section_responses.section_element_id = se.id
                JOIN {perform_participant_section} ps
                    ON ps.section_id = se.section_id
                        AND ps.participant_instance_id = completed_section_responses.participant_instance_id
                        AND ps.progress = " . complete::get_code() . "

                UNION

                SELECT reference_responses.*
                FROM {perform_element_response} reference_responses
                JOIN {perform_section_element} se_referencing ON reference_responses.section_element_id = se_referencing.id
                JOIN {perform_section_element_reference} ser ON se_referencing.element_id = ser.referencing_element_id
            ) es

            JOIN {perform_participant_instance} ppi ON es.participant_instance_id = ppi.id
            LEFT JOIN {user} u ON ppi.participant_id = u.id
                AND ppi.participant_source = " . participant_source::INTERNAL . "
            WHERE ppi.participant_source = " . participant_source::EXTERNAL . "
                OR u.deleted = 0
        )";

        // Element plugins can override data of certain fields to make sure the report displays specific information related to the element plugin
        $plugins = element_plugin::get_element_plugins();
        foreach ($plugins as $plugin) {
            if (!$plugin->get_response_report_builder_helper()) {
                continue;
            }

            $helper = $plugin->get_response_report_builder_helper();
            $this->add_response_extra_fields($helper->get_response_extra_fields());
            $this->set_element_plugin_override_title($plugin->get_plugin_name(), $helper->get_element_override_title());
            $this->set_element_plugin_override_type($plugin->get_plugin_name(), $helper->get_element_override_type());
            $this->set_element_plugin_override_identifier($plugin->get_plugin_name(), $helper->get_element_override_identifier());
            $this->set_element_plugin_override_required($plugin->get_plugin_name(), $helper->get_element_override_required());
            $this->set_element_plugin_override_response($plugin->get_plugin_name(), $helper->get_element_override_response());
        }

        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        $this->add_section_element(
            new rb_join(
                'section_element',
                'INNER',
                '{perform_section_element}',
                'base.section_element_id = section_element.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            )
        );

        $this->add_element(
            new rb_join(
                'perform_element',
                'INNER',
                '{perform_element}',
                'section_element.element_id = perform_element.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'section_element'
            )
        );

        $this->add_section(
            new rb_join(
                'perform_section',
                'INNER',
                '{perform_section}',
                'section_element.section_id = perform_section.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'section_element'
            )
        );

        $this->add_participant_instance(
            new rb_join(
                'participant_instance',
                'INNER',
                '{perform_participant_instance}',
                'base.participant_instance_id = participant_instance.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            )
        );

        $this->add_subject_instance();
        $this->add_activity();

        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();

        // Element plugins can add columns/filters to make sure the report displays specific information related to the element plugin
        foreach ($plugins as $plugin) {
            if (!$plugin->get_response_report_builder_helper()) {
                continue;
            }

            $helper = $plugin->get_response_report_builder_helper();
            $this->usedcomponents = array_merge($this->usedcomponents, $helper->get_used_components());
            $this->sourcejoins = array_merge($this->sourcejoins, $helper->get_additional_sourcejoins());
            $this->columnoptions = array_merge($this->columnoptions, $helper->get_columns());
            $this->filteroptions = array_merge($this->filteroptions, $helper->get_filters());
            $this->joinlist = array_merge($this->joinlist, $helper->get_joins());
        }

        parent::__construct();
    }

    /**
     * Add extra fields for use in the response column
     *
     * @param array $extra_fields
     */
    protected function add_response_extra_fields(array $extra_fields) {
        if (!empty($extra_fields)) {
            $this->response_extra_fields = array_merge($this->response_extra_fields, $extra_fields);
        }
    }

    /**
     * Allows a plugin to override the response column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $response
     */
    protected function set_element_plugin_override_response(string $plugin_name, string $response) {
        if (!empty($response)) {
            $this->element_plugin_override_response[$plugin_name] = $response;
        }
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
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
        global $DB;

        // A plugin can override the response field, in this case build a CASE statement
        // to make sure the plugin specific data is used
        $default_response_field = 'base.response_data';
        $response_field = $default_response_field;
        if (!empty($this->element_plugin_override_response)) {
            $response_field = "CASE ";
            foreach ($this->element_plugin_override_response as $plugin_name => $override) {
                $response_field .= " WHEN perform_element.plugin_name = '{$plugin_name}' THEN {$override}";
            }
            $response_field .= " ELSE {$default_response_field} END";
        }

        $columnoptions = [
            new rb_column_option(
                'response',
                'response_data',
                get_string('response_data', 'rb_source_perform_response_base'),
                $response_field,
                [
                    'joins' => ['perform_element'],
                    'displayfunc' => 'element_response',
                    'extrafields' => $this->response_extra_fields
                ]
            ),
            // Column for sorting that combines activity name, section and element sorts, relationship and participant
            // to get sensible overall order for responses
            new rb_column_option(
                'response',
                'default_sort',
                get_string('default_sort', 'mod_perform'),
                // This will ensure elements are grouped by activity and order within but isn't perfect, particularly for
                // multiple identically named activities (which we don't prevent). Having an activity.sort_order would be better.
                $DB->sql_concat_join(
                    "' '",
                    [
                        'perform.name',
                        'perform_section.sort_order',
                        'section_element.sort_order',
                        'totara_core_relationship.sort_order',
                        'participant_instance.participant_source',
                        'participant_instance.participant_id'
                    ]
                ),
                [
                    'joins' => ['perform', 'perform_section', 'section_element', 'participant_instance', 'totara_core_relationship'],
                    'hidden' => true,
                    'noexport' => true,
                    'selectable' => false,
                ]
            ),
        ];

        return $columnoptions;
    }

    /**
     * Define the filter options available for this report.
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [
            new rb_filter_option(
                'response',
                'response_data',
                get_string('response_data', 'rb_source_perform_response_base'),
                'text'
            ),
        ];

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
        $default_columns = [
            [
                'type' => 'activity',
                'value' => 'name',
                'heading' => get_string('activity_name', 'rb_source_perform_response'),
            ],
            [
                'type' => 'subject_user',
                'value' => 'namelink',
                'heading' => get_string('subject_name', 'rb_source_perform_response'),
            ],
            [
                'type' => 'participant_instance',
                'value' => 'participant_name',
                'heading' => get_string('participant_name', 'rb_source_perform_response'),
            ],
            [
                'type' => 'participant_instance',
                'value' => 'relationship_name',
                'heading' => get_string('participant_relationship_name', 'rb_source_perform_response'),
            ],
            [
                'type' => 'participant_instance',
                'value' => 'participant_email',
                'heading' => get_string('participant_email', 'rb_source_perform_response'),
            ],
            [
                'type' => 'element',
                'value' => 'identifier',
                'heading' => get_string('element_identifier', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'type',
                'heading' => get_string('element_type', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'title',
                'heading' => get_string('element_title', 'rb_source_perform_response'),
            ],
            [
                'type' => 'response',
                'value' => 'response_data',
                'heading' => get_string('element_response', 'rb_source_perform_response'),
            ],
            [
                'type' => 'participant_instance',
                'value' => 'updated_at',
                'heading' => get_string('section_submission_date', 'rb_source_perform_response'),
            ],
        ];

        // Element plugins can add additional default columns
        $plugins = element_plugin::get_element_plugins();
        foreach ($plugins as $plugin) {
            if (!$plugin->get_response_report_builder_helper()) {
                continue;
            }

            $helper = $plugin->get_response_report_builder_helper();
            $default_columns = array_merge($default_columns, $helper->get_default_columns());
        }

        return $default_columns;
    }

    /**
     * The default filters for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_filters() {
        $default_filters = [];

        // Element plugins can add additional default filters
        $plugins = element_plugin::get_element_plugins();
        foreach ($plugins as $plugin) {
            if (!$plugin->get_response_report_builder_helper()) {
                continue;
            }

            $helper = $plugin->get_response_report_builder_helper();
            $default_filters = array_merge($default_filters, $helper->get_default_filters());
        }

        return $default_filters;
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'subject_user');

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
                'subject_user_id',
                'subject_instance.subject_user_id',
                'subject_instance'
            ),
            new rb_param_option(
                'subject_instance_id',
                'participant_instance.subject_instance_id',
                'participant_instance'
            ),
            new rb_param_option(
                'element_id',
                $this->get_element_id_filter_column('perform_element.id'),
                'perform_element'
            ),
            new rb_param_option(
                'element_identifier',
                $this->get_element_identifier_filter_column('perform_element.identifier_id'),
                'perform_element'
            ),
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
     * Inject column_test data into database.
     *
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $perform_generator = \mod_perform\testing\generator::instance();
        $si = $perform_generator->create_subject_instance([
            'activity_name' => 'Weekly catchup',
            'subject_is_participating' => true,
            'subject_user_id' => \core\entity\user::repository()->get()->last()->id,
            'include_questions' => true,
            'update_participant_sections_status' => 'complete',
        ]);
        $perform_generator->create_responses($si, 1);
    }
}

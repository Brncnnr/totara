<?php
/**
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
 */

namespace mod_perform\rb\traits;

use coding_exception;
use mod_perform\models\activity\element_plugin;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait element_trait
 */
trait element_trait {
    /** @var string $element_join */
    protected $element_join = null;

    /**
     * If an element plugin wants to override the id field it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_id = [];

    /**
     * If an element plugin wants to override the title field it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_title = [];

    /**
     * If an element plugin wants to override the type (aka plugin_name) field it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_type = [];

    /**
     * If an element plugin wants to override the identifier field it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_identifier = [];

    /**
     * If an element plugin wants to override the require field it needs to be added in this array
     *
     * @var array
     */
    protected $element_plugin_override_required = [];

    /**
     * Add element info where element is the base table.
     *
     * @throws coding_exception
     */
    protected function add_element_to_base() {
        /** @var element_trait|rb_base_source $this */
        if (isset($this->element_join)) {
            throw new coding_exception('Element info can be added only once!');
        }

        $this->element_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_element_joins();
        $this->add_element_columns();
        $this->add_element_filters();
    }

    /**
     * Allows a plugin to override the id column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $id
     */
    protected function set_element_plugin_override_id(string $plugin_name, string $id) {
        if (!empty($id)) {
            $this->element_plugin_override_id[$plugin_name] = $id;
        }
    }

    /**
     * Allows a plugin to override the title column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $title
     */
    protected function set_element_plugin_override_title(string $plugin_name, string $title) {
        if (!empty($title)) {
            $this->element_plugin_override_title[$plugin_name] = $title;
        }
    }

    /**
     * Allows a plugin to override the type column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $type
     */
    protected function set_element_plugin_override_type(string $plugin_name, string $type) {
        if (!empty($type)) {
            $this->element_plugin_override_type[$plugin_name] = $type;
        }
    }

    /**
     * Allows a plugin to override the idnumber column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $identifier
     */
    protected function set_element_plugin_override_identifier(string $plugin_name, string $identifier) {
        if (!empty($identifier)) {
            $this->element_plugin_override_identifier[$plugin_name] = $identifier;
        }
    }

    /**
     * Allows a plugin to override the required column field. This is part of the report builder sql statement.
     *
     * @param string $plugin_name
     * @param string $required
     */
    protected function set_element_plugin_override_required(string $plugin_name, string $required) {
        if (!empty($required)) {
            $this->element_plugin_override_required[$plugin_name] = $required;
        }
    }

    /**
     * Add element info where element is a joined table.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_element(rb_join $join) {
        /** @var element_trait|rb_base_source $this */
        if (isset($this->element_join)) {
            throw new coding_exception('Element info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->element_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_element_joins();
        $this->add_element_columns();
        $this->add_element_filters();
    }

    /**
     * Add joins required for element column and filter options to report.
     */
    protected function add_element_joins() {
        /** @var element_trait|rb_base_source $this */
        $join = $this->element_join;

        // Add in element_identifier table so we can add identifier columns/filters too.
        $identifier_table_alias = "perform_element_identifier_{$join}";
        $this->joinlist[] = new rb_join(
            $identifier_table_alias,
            'LEFT',
            '{perform_element_identifier}',
            "{$join}.identifier_id = {$identifier_table_alias}.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );
    }

    /**
     * Add columnoptions for element to report.
     */
    protected function add_element_columns() {
        /** @var element_trait|rb_base_source $this */
        $join = $this->element_join;

        $this->columnoptions[] = new rb_column_option(
            'element',
            'title',
            get_string('question_title', 'mod_perform'),
            $this->get_element_title_field("{$join}.title"),
            [
                'joins' => [$join],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'format_string',
            ]
        );

        // Element identifier is known to end users as Reporting ID.
        $identifier_table_alias = "perform_element_identifier_{$join}";

        $this->columnoptions[] = new rb_column_option(
            'element',
            'identifier',
            get_string('element_identifier', 'mod_perform'),
            $this->get_element_identifier_field("{$identifier_table_alias}.identifier"),
            [
                'joins' => [$join, $identifier_table_alias],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'format_string',
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'element',
            'is_required',
            get_string('element_is_required', 'mod_perform'),
            $this->get_element_required_field("{$join}.is_required"),
            [
                'displayfunc' => 'yes_or_no',
                'dbdatatype' => 'boolean',
                'joins' => [$join],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'element',
            'type',
            get_string('element_type', 'mod_perform'),
            $this->get_element_type_field("{$join}.plugin_name"),
            [
                'joins' => [$join],
                'displayfunc' => 'element_type',
                'extrafields' => ['data' => "$join.data"]
            ]
        );
    }

    /**
     * Returns the field name for the title, which could be different on a per-plugin base
     *
     * @param string $default
     * @return string
     */
    private function get_element_title_field(string $default): string {
        return $this->build_conditional_field($this->element_plugin_override_title, $default);
    }

    /**
     * Returns the field name for the type, which could be different on a per-plugin base
     *
     * @param string $default
     * @return string
     */
    private function get_element_type_field(string $default): string {
        return $this->build_conditional_field($this->element_plugin_override_type, $default);
    }

    /**
     * Returns the field name for the identifier, which could be different on a per-plugin base
     *
     * @param string $default
     * @return string
     */
    private function get_element_identifier_field(string $default): string {
        return $this->build_conditional_field($this->element_plugin_override_identifier, $default);
    }

    /**
     * Returns the field name for required, which could be different on a per-plugin base
     *
     * @param string $default
     * @return string
     */
    private function get_element_required_field(string $default): string {
        return $this->build_conditional_field($this->element_plugin_override_required, $default);
    }

    /**
     * Depending on whether there's an override either builds a CASE statement or directly returns the default field
     *
     * @param array $override
     * @param string $default
     * @return string
     */
    private function build_conditional_field(array $override, string $default): string {
        $field = $default;
        if (!empty($override)) {
            $field = "CASE ";
            foreach ($override as $plugin_name => $override) {
                $field .= " WHEN {$this->element_join}.plugin_name = '{$plugin_name}' THEN {$override}";
            }
            $field .= " ELSE {$default} END";
        }

        return $field;
    }


    /**
     * Add filteroptions for elements to report.
     */
    protected function add_element_filters() {
        $this->filteroptions[] = new rb_filter_option(
            'element',
            'title',
            get_string('question_title', 'mod_perform'),
            'text'
        );

        $this->filteroptions[] = new rb_filter_option(
            'element',
            'type',
            get_string('element_type', 'mod_perform'),
            'perform_element_type',
        );

        $this->filteroptions[] = new rb_filter_option(
            'element',
            'identifier',
            get_string('element_identifier', 'mod_perform'),
            'text'
        );
    }

    /**
     * Get an array of element type options to use for filtering.
     *
     * @return string[] of [plugin_name => Display Name]
     * @depreacted since Totara 17.0
     */
    protected function get_element_type_options(): array {
        debugging(
            'mod_perform\rb\traits\element_trait::get_element_type_options() is deprecated, use mod_perform\rb\filter\element_type',
            DEBUG_DEVELOPER
        );
        $displayable_responses_elements = element_plugin::get_displays_responses_plugins();

        return array_map(function (element_plugin $element_plugin) {
            return $element_plugin->get_name();
        }, $displayable_responses_elements);
    }


    /**
     * Element plugins can add extend the element_id filter column. Currently the
     * only achievable way is to create a COALESCE and therefore the order of plugins could
     * make a difference to what column is used. Without greater changes to the structure this
     * is currently the most practical but not 100% reliable way.
     *
     * @param string $default_column
     * @return string
     */
    protected function get_element_id_filter_column(string $default_column): string {
        $element_id_filter_columns = [];
        $plugins = element_plugin::get_element_plugins();
        foreach ($plugins as $plugin) {
            if ($helper = $plugin->get_response_report_builder_helper()) {
                $filter_column = $helper->get_element_id_filter_column();
                if (!empty($filter_column)) {
                    $element_id_filter_columns[] = $filter_column;
                }
            }
        }

        return $this->create_filter_coalesce($element_id_filter_columns, $default_column);
    }

    /**
     * Element plugins can add extend the element_identifier filter column.
     * See @see element_trait::get_element_id_filter_column() for more information
     *
     * @param string $default_column
     * @return string
     */
    protected function get_element_identifier_filter_column(string $default_column): string {
        $element_identifier_filter_columns = [];
        $plugins = element_plugin::get_element_plugins();
        foreach ($plugins as $plugin) {
            if ($helper = $plugin->get_response_report_builder_helper()) {
                $filter_column = $helper->get_element_identifier_filter_column();
                if (!empty($filter_column)) {
                    $element_identifier_filter_columns[] = $filter_column;
                }
            }
        }

        return $this->create_filter_coalesce($element_identifier_filter_columns, $default_column);
    }

    /**
     * Build coalesce which we need for the filtering over multiple columns
     *
     * @param array $filter_columns
     * @param string $default_column
     * @return string
     */
    private function create_filter_coalesce(array $filter_columns, string $default_column): string {
        $filter_by = $default_column;
        if (!empty($filter_columns)) {
            $filter_by = 'COALESCE(';
            foreach ($filter_columns as $column) {
                $filter_by .= "{$column}, ";
            }
            $filter_by .= "{$default_column})";
        }

        return $filter_by;
    }
}

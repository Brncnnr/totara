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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\helper;

use mod_perform\models\activity\element_plugin;
use rb_column_option;
use rb_filter_option;
use rb_join;

/**
 * This provides a base for a pluggable system to modify the response report based on the element plugin.
 *
 * An element plugin implementing this @see element_plugin::get_response_report_builder_helper() can add joins, columns and filters to the report,
 * as well as add additional default columns and filters.
 *
 * By implementing the get_element_override_... methods it can replace the related fields with data specific to the plugin.
 */
abstract class element_plugin_response_report_builder {

    /**
     * Returns an array of joins to add to the response report
     *
     * @return rb_join[]
     */
    abstract public function get_joins(): array;

    /**
     * Returns an array of additional columns to add to the response report
     *
     * @return rb_column_option[]
     */
    abstract public function get_columns(): array;

    /**
     * Returns filters
     *
     * @return rb_filter_option[]
     */
    abstract public function get_filters(): array;

    /**
     * Returns an array of extra default collumns to be added to the response report.
     * This needs to follow the same format for default columns as in an normal report source
     *
     * @return array
     */
    abstract public function get_default_columns(): array;

    /**
     * Returns an array of extra default filters to be added to the response report.
     * This needs to follow the same format for default filters as in an normal report source
     *
     * @return array
     */
    abstract public function get_default_filters(): array;

    /**
     * Can add extra fields to the response column if needed
     *
     * @return array
     */
    abstract public function get_response_extra_fields(): array;

    /**
     * Returns a field name which should be used instead of the original element title field
     *
     * @return string
     */
    abstract public function get_element_override_title(): string;

    /**
     * Returns a field name which should be used instead of the original element type field
     *
     * @return string
     */
    abstract public function get_element_override_type(): string;

    /**
     * Returns a field name which should be used instead of the original element identifier field
     *
     * @return string
     */
    abstract public function get_element_override_identifier(): string;

    /**
     * Returns a field name which should be used instead of the original element required field
     *
     * @return string
     */
    abstract public function get_element_override_required(): string;

    /**
     * Returns a field name which should be used instead of the original response field
     *
     * Please note that for this to work properly you also need to implement the
     * @see element_plugin_response_report_builder::get_response_extra_fields() method
     *
     * @return string
     *
     */
    abstract public function get_element_override_response(): string;

    /**
     * Return an array of additional sourcejoins in case the plugin needs
     * to force certain joins to be used regardless whether there's a column using it
     *
     * @return string[]
     */
    abstract public function get_additional_sourcejoins(): array;

    /**
     * Returns any additional used components
     *
     * @return array
     */
    abstract public function get_used_components(): array;

    /**
     * Element plugins can extend the element_id filter column. Currently the
     * only achievable way is to create a COALESCE and therefore the order of plugins could
     * make a difference to what column is used. Without greater changes to the structure this
     * is currently the most practical but not 100% reliable way.
     * 
     * @return string
     */
    abstract public function get_element_id_filter_column(): string;

    /**
     * Element plugins can extend the element_identifier filter column.
     *
     * @see element_plugin_response_report_builder::get_element_id_filter_column() for more information.
     *
     * @return string
     */
    abstract public function get_element_identifier_filter_column(): string;

}
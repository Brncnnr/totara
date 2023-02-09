<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\controllers\reporting\performance\filters;

use mod_perform\rb\filter\section_titles_by_activity as rb_filter_section_title;

class section_title extends filter_controller {

    /**
     * Get an array of section title options to use for filtering.
     *
     * @return string[] of [plugin_name => Display Name]
     */
    protected static function get_item_options(): array {
        return rb_filter_section_title::get_item_options();
    }

    /**
     * Return the search type the totara multi-select dialog modal
     *
     * @return string
     */
    protected static function get_search_type(): string {
        return 'section_title';
    }

    /**
     * Given an element type returns the HTML to display it as a filter selection
     *
     * @param string $id
     * @param string $display_name
     * @param string $filtername The identifying name of the current filter
     *
     * @return string HTML to display a selected item
     */
    protected static function display_selected_items($id, $display_name, $filtername): string {
        return rb_filter_section_title::display_selected_items($id, $display_name, $filtername);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/perform/reporting/performance/filters/section_title.php';
    }
}

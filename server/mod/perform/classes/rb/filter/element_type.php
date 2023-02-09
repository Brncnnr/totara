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
 * @subpackage totara_reportbuilder
 */

namespace mod_perform\rb\filter;

use mod_perform\models\activity\element_plugin;

/**
 * Filter based on selecting multiple element types via a dialog
 */
class element_type extends perform_filter_type {

    /**
     * @inheritDoc
     */
    protected static function get_modal_title(): array {
        return ['choose_element_type_plural', 'mod_perform'];
    }

    /**
     * @inheritDoc
     */
    protected static function get_modal_css(): string {
        return 'rb-filter-choose-element-type';
    }

    /**
     * Get an array of element type options to use for filtering.
     *
     * @return string[] of [plugin_name => Display Name]
     */
    public static function get_item_options(): array {
        static $element_types = [];
        if (!empty($element_types)) {
            return $element_types;
        }

        $displayable_responses_elements = element_plugin::get_displays_responses_plugins();

        usort( $displayable_responses_elements, function (element_plugin $a, element_plugin $b) {
            if ($a->get_sortorder() === $b->get_sortorder()) {
                return $a->get_name() <=> $b->get_name();
            }
            return $a->get_sortorder() <=> $b->get_sortorder();
        });

        foreach ($displayable_responses_elements as $element_plugin) {
            $plugin_name = $element_plugin::get_plugin_name();
            $element_types[$plugin_name] = format_string($element_plugin->get_name());
        }

        return $element_types;
    }
}
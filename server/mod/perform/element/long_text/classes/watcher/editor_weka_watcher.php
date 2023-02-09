<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

namespace performelement_long_text\watcher;

use editor_weka\hook\find_context;
use mod_perform\models\activity\section_element;
use performelement_long_text\long_text;

/**
 * Watcher for editor_weka hooks.
 */
class editor_weka_watcher {

    /**
     * @param find_context $hook
     * @return void
     */
    public static function load_context(find_context $hook): void {
        $component = $hook->get_component();
        if ($component !== long_text::get_response_files_component_name()) {
            return;
        }

        $area = $hook->get_area();
        if ($area !== long_text::get_response_files_filearea_name()) {
            return;
        }

        $section_element_id = $hook->get_instance_id();
        if ($section_element_id === null) {
            return;
        }

        $context = section_element::load_by_id($section_element_id)
            ->get_element()
            ->get_context();

        $hook->set_context($context);
    }

}

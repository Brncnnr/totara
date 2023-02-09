<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\output;

use core\output\template;
use totara_reportbuilder\template_helper;

use totara_core\output\select_region_panel;
use totara_core\output\select_search_text;
use totara_core\output\select_multi;

use reportbuilder;

defined('MOODLE_INTERNAL') || die();

class create_report extends template {

    /**
     * @return create_report
     */
    public static function create() {
        // Unfortunately, we need to fetch everything to make sure that we have a complete view of the possible
        // filter options
        $source_groups = reportbuilder::get_source_groups();
        $template_groups = template_helper::get_template_groups();

        $display_source_groups = [];
        foreach ($source_groups as $key => $sources) {
            $display_source_groups[$key] = $key;
        }

        $display_template_groups = [];
        foreach ($template_groups as $key => $template) {
            $display_template_groups[$key] = $key;
        }

        $select_panel = select_region_panel::create(get_string('filters', 'totara_reportbuilder'), [
            select_search_text::create('search', get_string('search', 'core'), true),
            select_multi::create('template', get_string('templates', 'totara_reportbuilder'), true, $display_template_groups, []),
            select_multi::create('source', get_string('reportsources', 'totara_reportbuilder'), true, $display_source_groups, [])
        ], true, true, true);

        $data = [
            'items' => [], // we aren't pre-loading items
            'filter_template' => $select_panel->get_template_name(),
            'filter_data' => $select_panel->get_template_data(),
        ];

        return new static((array)$data);
    }
}
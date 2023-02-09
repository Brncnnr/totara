<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 */

namespace totara_competency\views\filters;

use totara_competency\entity\assignment;
use totara_core\output\select_region_panel;
use totara_core\output\select_search_text;
use totara_core\output\select_tree;

class assignments extends filters {

    public static function create_region_panel_filters(): array {
        return [
            self::create_search_filter(),
            self::create_assignment_type_filter(),
            self::create_framework_filter()
        ];
    }

    public static function create_region_panel_for_edit_proficiency_value(): select_region_panel {
        return select_region_panel::create(
            get_string('filter', 'totara_competency'),
            static::create_region_panel_filters_for_edit_proficiency_value(),
            true,
            true,
            true
        );
    }

    public static function create_region_panel_filters_for_edit_proficiency_value(): array {
        return [
            self::create_search_filter(),
            self::create_assignment_type_filter(),
        ];
    }

    public static function create_search_filter(): select_search_text {
        return select_search_text::create(
            'text',
            get_string('search', 'totara_core'),
            true
        );
    }

    public static function create_status_filter(): select_tree {
        return select_tree::create(
            'status',
            get_string('filter_status', 'totara_competency'),
            true,
            [
                (object)[
                    'name' => get_string('filter_status_all', 'totara_competency'),
                    'key' => '',
                    'default' => true
                ],
                (object)[
                    'name' => get_string('filter_status_draft', 'totara_competency'),
                    'key' => assignment::STATUS_DRAFT,
                ],
                (object)[
                    'name' => get_string('filter_status_active', 'totara_competency'),
                    'key' => assignment::STATUS_ACTIVE,
                ],
                (object)[
                    'name' => get_string('filter_status_archived', 'totara_competency'),
                    'key' => assignment::STATUS_ARCHIVED,
                ],
            ],
            null,
            true,
            false,
            null,
            true
        );
    }

    public static function create_framework_filter(): select_tree {
        return select_tree::create(
            'framework',
            get_string('filter_framework', 'totara_competency'),
            true,
            self::get_competency_frameworks_options(get_string('filter_framework_all', 'totara_competency')),
            null,
            true
        );
    }

    public static function create_framework_filter_for_edit_proficiency(): select_tree {
        return select_tree::create(
            'framework',
            get_string('filter_select_competency_framework', 'totara_competency'),
            false,
            self::get_competency_frameworks_options(null), // Excludes "All frameworks" option.
            null,
            true
        );
    }

}
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

use totara_core\output\select_multi;
use totara_core\output\select_search_text;
use totara_core\output\select_tree;
use totara_hierarchy\entity\competency_type;

class competencies extends filters {

    /**
     * Creates an array of filters for the region panel
     *
     * @return array
     */
    public static function create_region_panel_filters(): array {
        $filters = [
            self::create_search_filter(),
            self::create_assignment_status_filter(),
            self::create_assignment_type_filter(),
        ];

        // Add the competency type filter conditionally
        $competency_type_filter = self::create_competency_type_filter();
        if ($competency_type_filter) {
            $filters[] = $competency_type_filter;
        }
        return $filters;
    }

    public static function create_search_filter(): select_search_text {
        return select_search_text::create(
            'text',
            get_string('search', 'totara_core'),
            true
        );
    }

    public static function create_assignment_status_filter(): select_multi {
        return select_multi::create(
            'assignment_status',
            get_string('filter_assignment_status', 'totara_competency'),
            true,
            [
                '1' => get_string('filter_assignment_status_assigned', 'totara_competency'),
                '0' => get_string('filter_assignment_status_unassigned', 'totara_competency'),
            ]
        );
    }

    /**
     * Create a filter showing all competency types
     *
     * @return select_multi|null
     */
    public static function create_competency_type_filter(): ?select_multi {
        $competency_types = competency_type::repository()->get();
        if (!$competency_types->count()) {
            return null;
        }

        $competency_type_options = [];
        foreach ($competency_types as $competency_type) {
            $competency_type_options[$competency_type->id] = format_string($competency_type->fullname);
        }

        return select_multi::create(
            'type',
            get_string('filter_competency_type', 'totara_competency'),
            true,
            $competency_type_options
        );
    }

    /**
     * @return select_tree
     */
    public static function create_framework_filter(): select_tree {
        return select_tree::create(
            'framework',
            '',
            true,
            self::get_competency_frameworks_options(get_string('filter_framework_all_frameworks', 'totara_competency')),
            null,
            true,
            false,
            null,
            true
        );
    }

}
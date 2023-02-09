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
use totara_competency\user_groups;
use totara_core\output\select_multi;
use totara_core\output\select_region_panel;
use totara_hierarchy\entity\competency_framework;

abstract class filters {

    public static function create_region_panel(): select_region_panel {
        return select_region_panel::create(
            get_string('filter', 'totara_competency'),
            static::create_region_panel_filters(),
            true,
            true,
            true
        );
    }

    public static function create_region_panel_filters(): array {
        return [];
    }

    public static function create_assignment_type_filter(): select_multi {
        return select_multi::create(
            'assignment_type',
            get_string('header_assignment_type', 'totara_competency'),
            true,
            [
                user_groups::POSITION     => get_string('position', 'totara_hierarchy'),
                user_groups::ORGANISATION => get_string('organisation', 'totara_hierarchy'),
                user_groups::COHORT       => get_string('cohort', 'totara_cohort'),
                assignment::TYPE_ADMIN    => get_string('assignment_type_admin', 'totara_competency'),
                assignment::TYPE_SELF     => get_string('assignment_type_self', 'totara_competency'),
                assignment::TYPE_OTHER    => get_string('assignment_type_other', 'totara_competency'),
                assignment::TYPE_SYSTEM   => get_string('assignment_type_system', 'totara_competency'),
            ]
        );
    }


    /**
     * @param string|null $all_frameworks_string if null is supplied there is no all option
     * @return array
     */
    protected static function get_competency_frameworks_options(?string $all_frameworks_string): array {
        $options = competency_framework::repository()
            ->filter_by_visible()
            ->order_by('sortorder')
            ->get()
            ->map(function (competency_framework $framework) {
                return (object)[
                    'name' => format_string($framework->fullname),
                    'key' => $framework->id,
                ];
            })->all(false);

        if ($all_frameworks_string !== null) {
            array_unshift($options, (object)[
                'name' => $all_frameworks_string,
                'key' => '',
            ]);
        }

        $options[0]->default = true;

        return $options;
    }

}
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 */

use pathway_manual\external;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * @group totara_competency
 */
class pathway_manual_services_testcase extends advanced_testcase {

    public function test_get_roles_no_filter() {
        $this->setAdminUser();

        $return = external::get_roles([], 1, '', 'asc');
        $result = external::clean_returnvalue(external::get_roles_returns(), $return);

        $this->assertEquals($return, $result);

        $expected = [
            'page' => 1,
            'pages' => 1,
            'items_per_page' => 3,
            'total' => 3,
            'items' => [
                [
                    'id'   => self_role::get_display_order(),
                    'value' => self_role::get_name(),
                    'text' => self_role::get_display_name(),
                ],
                [
                    'id'   => manager::get_display_order(),
                    'value' => manager::get_name(),
                    'text' => manager::get_display_name(),
                ],
                [
                    'id'   => appraiser::get_display_order(),
                    'value' => appraiser::get_name(),
                    'text' => appraiser::get_display_name(),
                ],
            ],
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_roles_ids_filter() {
        $this->setAdminUser();

        $ids = [appraiser::get_display_order(), self_role::get_display_order()];
        $return = external::get_roles(['ids' => $ids], 1, '', 'asc');
        $result = external::clean_returnvalue(external::get_roles_returns(), $return);

        $this->assertEquals($return, $result);

        $expected = [
            'page' => 1,
            'pages' => 1,
            'items_per_page' => 2,
            'total' => 2,
            'items' => [
                [
                    'id'   => self_role::get_display_order(),
                    'value' => self_role::get_name(),
                    'text' => self_role::get_display_name(),
                ],
                [
                    'id'   => appraiser::get_display_order(),
                    'value' => appraiser::get_name(),
                    'text' => appraiser::get_display_name(),
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_roles_name_filter() {
        $this->setAdminUser();

        $return = external::get_roles(['name' => 'manage'], 1, '', 'asc');
        $result = external::clean_returnvalue(external::get_roles_returns(), $return);

        $this->assertEquals($return, $result);

        $expected = [
            'page' => 1,
            'pages' => 1,
            'items_per_page' => 1,
            'total' => 1,
            'items' => [
                [
                    'id'   => manager::get_display_order(),
                    'value' => manager::get_name(),
                    'text' => manager::get_display_name(),
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}

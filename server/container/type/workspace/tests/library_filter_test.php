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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\query\query;
use container_workspace\totara_engage\share\recipient\library;
use container_workspace\workspace;

class container_workspace_library_filter_testcas extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetching_type_options(): void {
        $query = new query();
        $query->set_component(workspace::get_type());
        $query->set_area(library::AREA);

        $options = $query->get_filter_options('TYPE');

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        // Checked component.
        $listed_component = [];

        foreach ($options as $option) {
            $this->assertIsArray($option);
            $this->assertArrayHasKey('id', $option);

            $listed_component[] = $option['id'];
        }

        $this->assertTrue(in_array('engage_article', $listed_component));
        $this->assertTrue(in_array('engage_survey', $listed_component));
        $this->assertTrue(in_array('totara_playlist', $listed_component));
    }
}
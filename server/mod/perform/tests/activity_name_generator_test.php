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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

/**
 * @coversDefaultClass \mod_perform\testing\activity_name_generator()
 *
 * @group perform
 */
class mod_perform_activity_name_generator_testcase extends advanced_testcase {
    public function test_generate_name() {
        $name_generator = new \mod_perform\testing\activity_name_generator();

        [$name, $type] = $name_generator->generate();
        $this->assertIsString($name);
        $this->assertGreaterThan(0, strlen($name));
        $this->assertContains($type, $name_generator->type);
    }

    public function test_generate_names() {
        $name_generator = new \mod_perform\testing\activity_name_generator();

        $names = $name_generator->generate_multiple(5);
        $this->assertIsArray($names);
        $this->assertCount(5, $names);
        foreach ($names as [$name, $type]) {
            $this->assertIsString($name);
            $this->assertGreaterThan(0, strlen($name));
            $this->assertContains($type, $name_generator->type);
        }
    }

}
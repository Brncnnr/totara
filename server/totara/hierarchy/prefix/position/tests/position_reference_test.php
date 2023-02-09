<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package hierarchy_position
 */

defined('MOODLE_INTERNAL') || die();

use core\exception\unresolved_record_reference;
use core_phpunit\testcase;
use hierarchy_position\reference\hierarchy_position_record_reference;

/**
 * @group hierarchy_position
 */
class hierarchy_position_position_reference_testcase extends testcase {

    /**
     * @return void
     */
    public function test_find_reference_by_id(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_pos_frame([]);
        $position1 = $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 1"
        ]);
        $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 2"
        ]);

        $reference = new hierarchy_position_record_reference();
        $record = $reference->get_record(['id' => $position1->id]);
        $this->assertEquals($position1->id, $record->id);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_nonexisting_id(): void {
        self::setAdminUser();

        $reference = new hierarchy_position_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['id' => 232]);
    }

    /**
     * @return void
     */
    public function test_find_reference_by_idnumber(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_pos_frame([]);
        $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 1",
            'idnumber' => 2,
        ]);
        $position2 = $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 2",
            'idnumber' => 22,
        ]);

        $reference = new hierarchy_position_record_reference();
        $record = $reference->get_record(['idnumber' => 22]);
        $this->assertEquals($position2->id, $record->id);
        $this->assertEquals($position2->idnumber, $record->idnumber);
    }

    /**
     * @return void
     */
    public function test_match_multiple_positions(): void {
        self::setAdminUser();
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $gen->create_pos_frame([]);
        $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 1",
            'idnumber' => 2,
        ]);
        $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => "position 2",
            'idnumber' => 2,
        ]);

        $reference = new hierarchy_position_record_reference();
        $this->expectException(unresolved_record_reference::class);
        $reference->get_record(['idnumber' => 2]);
    }
}

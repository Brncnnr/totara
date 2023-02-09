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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use totara_competency\plugin_types;
use totara_criteria\criterion;
use totara_criteria\criterion_factory;

/**
 * @group totara_competency
 */
class totara_criteria_criterion_factory_testcase extends advanced_testcase {

    /**
     * Test create
     */
    public function test_create() {

        $enabled_types = plugin_types::get_enabled_plugins('criteria', 'totara_criteria');
        foreach ($enabled_types as $plugin_type) {
            $instance = criterion_factory::create($plugin_type);
            $this->assertSame($plugin_type, $instance->get_plugin_type());
            $this->assertTrue(is_null($instance->get_id()));
        }
    }

    /**
     * Test fetch
     */
    public function test_fetch() {
        // Setup some data
        // Courses
        $courses = [];

        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Coursecompletion
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $record = [
            'aggregation' => criterion::AGGREGATE_ANY_N,
            'req_items' => 2,
            'courseids' => [$courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id],
        ];

        $cc = $generator->create_coursecompletion($record);

        $instance = criterion_factory::fetch('coursecompletion', $cc->get_id());
        $this->assertSame('coursecompletion', $instance->get_plugin_type());
        $this->assertSame($cc->get_id(), $instance->get_id());
        $this->assertSame(4, count($instance->get_item_ids()));
        foreach ($instance->get_item_ids() as $itemid) {
            $this->assertTrue(in_array($itemid, [$courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id]));
        }
    }


    /**
     * Test dump_criterion_configuration with invalid type
     */
    public function test_dump_criterion_configuration_invalid_type() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Invalid criterion type 'Invalid'");
        criterion_factory::dump_criterion_configuration('Invalid', 1);
    }

    /**
     * Test dump_criterion_configuration
     */
    public function test_dump_criterion_configuration() {
        global $DB;

        // Setup some data
        // Courses
        $courses = [];

        for ($i = 1; $i <= 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course();
        }

        // Coursecompletion
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $record = [
            'aggregation' => criterion::AGGREGATE_ANY_N,
            'req_items' => 2,
            'courseids' => [$courses[2]->id, $courses[3]->id, $courses[4]->id, $courses[5]->id],
        ];

        $cc = $generator->create_coursecompletion($record);

        $expected = $DB->get_record('totara_criteria', ['id' => $cc->get_id()]);
        $expected->items = $DB->get_records('totara_criteria_item', ['criterion_id' => $cc->get_id()]);
        $expected->metadata = [];

        $actual = criterion_factory::dump_criterion_configuration('coursecompletion', $cc->get_id());

        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}

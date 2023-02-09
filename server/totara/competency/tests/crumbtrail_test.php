<?php
/**
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
 * @category test
 */

use core_phpunit\testcase;
use totara_hierarchy\entity\competency as competency_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_crumbtrail_testcase extends testcase {

    /**
     * @var moodle_database
     */
    private $db;

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->db = $GLOBALS['DB'];
    }

    protected function tearDown(): void {
        $this->db = null;
        parent::tearDown();
    }

    public function test_generate_crumbtrail() {
        $test_data = $this->prepare_data();

        $competency = new competency_entity($test_data->comp4);
        $crumbtrail = $competency->crumbtrail;

        $this->assertCount(5, $crumbtrail);

        // First item is the framework
        $framework = array_shift($crumbtrail);
        $this->assertEquals(
            [
                'id' => $test_data->framework->id,
                'name' => $test_data->framework->fullname,
                'parent_id' => 0,
                'type' => 'framework',
                'active' => false,
                'first' => true,
                'last' => false
            ],
            $framework
        );

        // Last item is the current competency
        $self = array_pop($crumbtrail);
        $this->assertEquals(
            [
                'id' => $test_data->comp4->id,
                'name' => $test_data->comp4->fullname,
                'parent_id' => $test_data->comp4->parentid,
                'type' => 'competency',
                'active' => true,
                'first' => false,
                'last' => true
            ],
            $self
        );

        $expected_items = [
            $test_data->comp1,
            $test_data->comp2,
            $test_data->comp3
        ];
        foreach ($expected_items as $expected_item) {
            $actual_item = array_shift($crumbtrail);
            $this->assertEquals(
                [
                    'id' => $expected_item->id,
                    'name' => $expected_item->fullname,
                    'parent_id' => $expected_item->parentid,
                    'type' => 'competency',
                    'active' => false,
                    'first' => false,
                    'last' => false
                ],
                $actual_item
            );
        }

        // No more items should be in the crumbtrail array
        $this->assertEmpty($crumbtrail);
    }

    public function test_generate_crumbtrail_of_uppermost_level() {
        $test_data = $this->prepare_data();

        $competency = new competency_entity($test_data->comp1);
        $crumbtrail = $competency->crumbtrail;

        $this->assertCount(2, $crumbtrail);

        // First item is the framework
        $framework = array_shift($crumbtrail);
        $this->assertEquals(
            [
                'id' => $test_data->framework->id,
                'name' => $test_data->framework->fullname,
                'parent_id' => 0,
                'type' => 'framework',
                'active' => false,
                'first' => true,
                'last' => false
            ],
            $framework
        );

        // Last item is the current competency
        $self = array_pop($crumbtrail);
        $this->assertEquals(
            [
                'id' => $test_data->comp1->id,
                'name' => $test_data->comp1->fullname,
                'parent_id' => $test_data->comp1->parentid,
                'type' => 'competency',
                'active' => true,
                'first' => false,
                'last' => true
            ],
            $self
        );

        // No more items should be in the crumbtrail array
        $this->assertEmpty($crumbtrail);
    }

    public function test_invalid_object() {
        $competency = new competency_entity();
        $crumbtrail = $competency->crumbtrail;

        $this->assertEmpty($crumbtrail);
    }

    private function prepare_data() {
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->generator()->get_plugin_generator('totara_hierarchy');

        $test_data = new class() {
            public $framework;
            public $comp1;
            public $comp2;
            public $comp3;
            public $comp4;
            public $comp5;
            public $comp6;
        };

        $fw = $hierarchy_generator->create_comp_frame(['fullname' => 'Framework one', 'idnumber' => 'f1']);
        $test_data->comp1 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c1', 'parentid' => 0]);
        $test_data->comp2 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c2', 'parentid' => $test_data->comp1->id]);
        $test_data->comp3 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c3', 'parentid' => $test_data->comp2->id]);
        $test_data->comp4 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c4', 'parentid' => $test_data->comp3->id]);
        $test_data->comp5 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c5', 'parentid' => 0]);
        $test_data->comp6 = $hierarchy_generator->create_comp(['frameworkid' => $fw->id, 'idnumber' => 'c6', 'parentid' => 0]);

        $test_data->framework = $fw;

        return $test_data;
    }

}
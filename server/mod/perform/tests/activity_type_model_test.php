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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\models\activity\activity;
use mod_perform\models\activity\activity_type;
use mod_perform\entity\activity\activity_type as activity_type_entity;

/**
 * @coversDefaultClass \mod_perform\models\activity\activity_type
 *
 * @group perform
 */
class mod_perform_activity_type_model_testcase extends advanced_testcase {
    /**
     * @covers ::load_by_name
     */
    public function test_existing(): void {
        $types = [
            'appraisal',
            'check-in',
            'feedback'
        ];

        foreach ($types as $name) {
            $type = activity_type::load_by_name($name);

            $this->assertNotNull($type, "activity type not installed: '$name'");
            $this->assertEquals($name, $type->name, 'wrong name');
            $this->assertTrue($type->is_system, 'wrong system value');
            $this->assertEquals(
                $type->display_name,
                get_string("system_activity_type:$name", 'mod_perform')
            );
        }
    }

    /**
     * @covers ::create
     * @covers ::load_by_id
     */
    public function test_create(): void {
        $name = "my test type";
        $type = activity_type::create($name);
        $this->assertGreaterThan(0, $type->id, 'wrong id');

        $retrieved = activity_type::load_by_id($type->id);
        $this->assertEquals($name, $retrieved->name, 'wrong name');
        $this->assertFalse($type->is_system, 'wrong wrong system value');
        $this->assertEquals($type->display_name, format_string($name));
    }

    /**
     * @covers ::load_by_name
     */
    public function test_activities(): void {
        $this->setAdminUser();

        $types = [
            'appraisal' => 0,
            'check-in' => 2,
            'feedback' => 1
        ];

        $generator = \mod_perform\testing\generator::instance();
        foreach ($types as $type => $count) {
            $data = ['activity_type' => $type];

            for ($i = 0; $i < $count; $i++) {
                /** @var \mod_perform\testing\generator $perform_generator */
                $generator->create_activity_in_container($data);
            }
        }

        foreach ($types as $type => $count) {
            $activities = activity_type::load_by_name($type)->activities;
            $this->assertEquals($count, $activities->count(), 'wrong activity count');

            $wrong_types = $activities->filter(
                function (activity $activity) use ($type): bool {
                    return $activity->type->name !== $type;
                }
            );
            $this->assertEmpty($wrong_types, 'wrong activity types retrieved');
        }
    }

    /**
     * @covers \mod_perform\models\activity\activity_type::get_display_name
     */
    public function test_get_display_name(): void {
        $system_types = activity_type_entity::repository()
            ->where('is_system', 1)
            ->get();

        $this->assertNotEmpty($system_types);
        foreach ($system_types as $system_type) {
            $this->assertEquals(
                get_string('system_activity_type:' . $system_type->name, 'mod_perform'),
                activity_type::load_by_entity($system_type)->display_name
            );
        }

        $xss_string = 'Regular <script>alert(\'Bad!\')</script>Type!';
        $regular_type = activity_type::create($xss_string);
        $this->assertEquals(format_string($xss_string), $regular_type->display_name);
    }

}

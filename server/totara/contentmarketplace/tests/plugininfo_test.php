<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

use core\testing\generator;
use core_phpunit\testcase;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_plugininfo_testcase extends testcase {

    /**
     * @param string $marketplace
     * @dataProvider marketplace_plugins_provider
     */
    public function test_get_usage_for_registration_data(string $marketplace): void {
        $generator = generator::instance();
        self::setAdminUser();

        $plugininfo = contentmarketplace::plugin($marketplace);

        $registration_data = $plugininfo->get_usage_for_registration_data();

        $this->assertArrayHasKey("{$marketplace}enabled", $registration_data);
        $this->assertArrayHasKey("num{$marketplace}courses", $registration_data);

        $this->assertEquals(0, $registration_data["{$marketplace}enabled"]);
        $this->assertEquals(0, $registration_data["num{$marketplace}courses"]);

        $plugininfo->enable();

        $registration_data = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $registration_data["{$marketplace}enabled"]);
        $this->assertEquals(0, $registration_data["num{$marketplace}courses"]);

        $course1 = $generator->create_course();
        $this->create_module_and_source_record($course1, '1', $marketplace);
        $course2 = $generator->create_course();
        $this->create_module_and_source_record($course2, '2', $marketplace);
        $this->create_module_and_source_record($course2, '3', $marketplace);

        $registration_data = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $registration_data["{$marketplace}enabled"]);
        $this->assertEquals(2, $registration_data["num{$marketplace}courses"]);
    }

    /**
     * @return string[]
     */
    public function marketplace_plugins_provider(): array {
        $subplugins = core_component::get_subplugins('totara_contentmarketplace');
        return array_map(function (string $plugin) {
            return [$plugin];
        }, $subplugins['contentmarketplace']);
    }

    /**
     * @param object $course
     * @param string $learning_object_id
     * @param string $marketplace_component
     * @return course_module_source
     */
    private function create_module_and_source_record(
        object $course,
        string $learning_object_id,
        string $marketplace_component
    ): course_module_source {
        $module = generator::instance()->create_module('contentmarketplace', ['course' => $course]);

        $module_source = new course_module_source([
            'cm_id' => $module->cmid,
            'learning_object_id' => $learning_object_id,
            'marketplace_component' => "contentmarketplace_$marketplace_component",
        ]);
        return $module_source->save();
    }

}

<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_contentmarketplace\learning_object\factory;
use mod_contentmarketplace\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_generator_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_module_from_generator(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $module = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin',
            ]
        );

        self::assertObjectHasAttribute('cmid', $module);
        self::assertObjectHasAttribute('name', $module);
        self::assertObjectHasAttribute('course', $module);
        self::assertObjectHasAttribute('learning_object_marketplace_component', $module);
        self::assertObjectHasAttribute('time_modified', $module);
        self::assertObjectHasAttribute('learning_object_id', $module);

        $db = builder::get_db();
        self::assertTrue($db->record_exists('course_modules', ['id' => $module->cmid]));

        $resolver = factory::get_resolver($module->learning_object_marketplace_component);
        $model = $resolver->find($module->learning_object_id);

        self::assertEquals($model->get_name(), $module->name);
        self::assertEquals($model->get_id(), $module->learning_object_id);
    }

    /**
     * @return void
     */
    public function test_create_module_with_generator_for_behat(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course([
            'format' => 'singleactivity',
        ]);

        $mod_generator = generator::instance();
        $module = $mod_generator->create_content_marketplace_instance([
            'course' => $course->shortname,
            'marketplace_component' => 'contentmarketplace_linkedin',
            'name' => 'Something else'
        ]);

        self::assertObjectHasAttribute('cmid', $module);
        self::assertObjectHasAttribute('name', $module);
        self::assertObjectHasAttribute('course', $module);
        self::assertObjectHasAttribute('learning_object_marketplace_component', $module);
        self::assertObjectHasAttribute('time_modified', $module);
        self::assertObjectHasAttribute('learning_object_id', $module);

        // This is to make sure that the function update course format options correctly.
        $db = builder::get_db();

        $format_option = $db->get_record(
            'course_format_options',
            [
                'courseid' => $course->id,
                'sectionid' => 0,
            ],
            '*',
            MUST_EXIST
        );

        self::assertEquals('activitytype', $format_option->name);
        self::assertEquals('contentmarketplace', $format_option->value);
    }
}
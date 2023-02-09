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
use mod_contentmarketplace\exception\learning_object_not_found;
use totara_contentmarketplace\testing\generator as marketplace_generator;
use mod_contentmarketplace\local\helper;
use mod_contentmarketplace\entity\content_marketplace;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_local_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_content_marketplace_record(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $marketplace_generator = marketplace_generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $model = helper::create_content_marketplace(
            $course->id,
            $learning_object->get_id(),
            $learning_object::get_marketplace_component()
        );

        $db = builder::get_db();
        self::assertTrue($db->record_exists(content_marketplace::TABLE, ['id' => $model->id]));
        self::assertTrue(
            $db->record_exists(
                content_marketplace::TABLE,
                [
                    'id' => $model->id,
                    'name' => $learning_object->get_name()
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_content_marketplace_record_without_learning_object(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        try {
            helper::create_content_marketplace(
                $course->id,
                42,
                'contentmarketplace_linkedin'
            );
            self::fail("Expect the process of adding content marketplace instance should yield error");
        } catch (learning_object_not_found $e) {
            self::assertEquals(
                get_string('error:cannot_find_learning_object', 'mod_contentmarketplace', 'contentmarketplace_linkedin'),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_create_content_marketplace_with_invalid_marketplace_component(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        try {
            helper::create_content_marketplace($course->id, 42, 'linkedin');
            self::fail("Expects the process should yield coding exception");
        } catch (coding_exception $e) {
            self::assertStringContainsString(
                "Invalid marketplace type 'linkedin'",
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_update_content_marketplace_with_invalid_condition(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $marketplace_generator = marketplace_generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $model = helper::create_content_marketplace(
            $course->id,
            $learning_object->get_id(),
            $learning_object::get_marketplace_component()
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The completion condition is invalid");

        helper::update_content_marketplace($model->id, ['completion_condition' => 42]);
    }
}
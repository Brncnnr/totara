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

use core\format;
use core\webapi\formatter\field\text_field_formatter;
use core_phpunit\testcase;
use mod_contentmarketplace\model\content_marketplace;
use mod_contentmarketplace\webapi\resolver\type\learning_object as type_learning_object;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_learning_object_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_resolve_field_id(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);
        $learning_object = $content_marketplace->get_learning_object();

        self::assertEquals(
            $learning_object->get_id(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                'id',
                $learning_object,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_name(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);
        $learning_object = $content_marketplace->get_learning_object();

        self::assertEquals(
            $learning_object->get_name(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                'name',
                $learning_object,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_language(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);
        $learning_object = $content_marketplace->get_learning_object();

        self::assertEquals(
            $learning_object->get_language(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                'language',
                $learning_object,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_image_url(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);
        $learning_object = $content_marketplace->get_learning_object();

        self::assertEquals(
            $learning_object->get_image_url(),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                'image_url',
                $learning_object,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_description(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        /** @var detailed_model $learning_object */
        $learning_object = $content_marketplace->get_learning_object();
        self::assertInstanceOf(detailed_model::class, $learning_object);

        $context = $content_marketplace->get_context();
        $formatter = new text_field_formatter(format::FORMAT_PLAIN, $context);
        $formatter->disabled_pluginfile_url_rewrite();

        self::assertEquals(
            $formatter->format(
                $learning_object->get_description()->get_raw_value()
            ),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                'description',
                $learning_object,
                ['format' => format::FORMAT_PLAIN,],
                $context
            )
        );
    }
}
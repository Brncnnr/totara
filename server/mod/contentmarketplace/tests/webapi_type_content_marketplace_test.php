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
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_contentmarketplace\model\content_marketplace;
use mod_contentmarketplace\webapi\resolver\type\content_marketplace as type_content_marketplace;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_webapi_type_content_marketplace_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_resolve_field_name(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        self::assertEquals(
            $cm->name,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace::class),
                'name',
                $content_marketplace,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_course(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        $course_resolved = $this->resolve_graphql_type(
            $this->get_graphql_name(type_content_marketplace::class),
            'course',
            $content_marketplace,
            [],
            $content_marketplace->get_context()
        );

        self::assertEquals(
            $course->id,
            $course_resolved->id
        );
        self::assertEquals($course->fullname, $course_resolved->fullname);
    }

    /**
     * @return void
     */
    public function test_resolve_field_id(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        self::assertEquals(
            $cm->id,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace::class),
                'id',
                $content_marketplace,
                [],
                $content_marketplace->get_context(),
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_course_module(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $cm_info = \cm_info::create((object) ['id' => $cm->cmid, 'course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        self::assertEquals(
            $cm_info,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace::class),
                'course_module',
                $content_marketplace,
                [],
                $content_marketplace->get_context()
            )
        );
    }

    /**
     * @return void
     */
    public function test_resolve_field_completion_condition(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module('contentmarketplace', ['course' => $course->id]);
        $content_marketplace = content_marketplace::from_course_module_id($cm->cmid);

        self::assertEquals(
            $cm->completion_condition,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_content_marketplace::class),
                'completion_condition',
                $content_marketplace,
                [],
                $content_marketplace->get_context()
            )
        );
    }

}
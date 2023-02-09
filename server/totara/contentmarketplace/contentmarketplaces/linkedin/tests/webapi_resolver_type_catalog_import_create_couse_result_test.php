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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\dto\course_creation_result;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\webapi\resolver\type\catalog_import_create_course_result;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_type_catalog_import_create_course_result_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var course_creation_result|null
     */
    private $result;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->result = new course_creation_result(true);
        $this->result->set_message("hello world");
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->result = null;
    }

    /**
     * @return void
     */
    public function test_get_success(): void {
        self::assertTrue(
            $this->resolve_graphql_type(
                $this->get_graphql_name(catalog_import_create_course_result::class),
                "success",
                $this->result
            )
        );

        $this->result->set_successful(false);
        self::assertFalse(
            $this->resolve_graphql_type(
                $this->get_graphql_name(catalog_import_create_course_result::class,),
                "success",
                $this->result
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_message(): void {
        self::assertEquals(
            "hello world",
            $this->resolve_graphql_type(
                $this->get_graphql_name(catalog_import_create_course_result::class),
                "message",
                $this->result
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_redirect_url(): void {
        self::assertNull(
            $this->resolve_graphql_type(
                $this->get_graphql_name(catalog_import_create_course_result::class),
                "redirect_url",
                $this->result
            )
        );

        $this->result->set_redirect_url(new moodle_url("http://example.com"));
        self::assertEquals(
            "http://example.com",
            $this->resolve_graphql_type(
                $this->get_graphql_name(catalog_import_create_course_result::class),
                "redirect_url",
                $this->result
            )
        );
    }
}
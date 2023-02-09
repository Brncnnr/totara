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

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\model\classification;
use core\format;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\webapi\resolver\type\classification as type_classification;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_type_classification_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var classification
     */
    private $classification;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $generator = generator::instance();

        $entity = $generator->create_classification(
            "urn:li:category:152",
            [
                "type" => constants::CLASSIFICATION_TYPE_SUBJECT,
                "locale_language" => "en",
                "locale_country" => "US",
                "name" => /** @lang text */"<script>alert('BAD');</script>"
            ]
        );

        $this->classification = new classification($entity);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->classification = null;
    }

    /**
     * @return void
     */
    public function test_get_id(): void {
        self::assertEquals(
            $this->classification->id,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_classification::class),
                "id",
                $this->classification
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_type(): void {
        self::assertEquals(
            constants::CLASSIFICATION_TYPE_SUBJECT,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_classification::class),
                "type",
                $this->classification
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_name(): void {
        self::assertEquals(
            "alert('BAD');",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_classification::class),
                "name",
                $this->classification,
            )
        );

        self::assertEquals(
            /** @lang text */"<script>alert('BAD');</script>",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_classification::class),
                "name",
                $this->classification,
                ["format" => format::FORMAT_RAW]
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_children(): void {
        $children = $this->resolve_graphql_type(
            $this->get_graphql_name(type_classification::class),
            "children",
            $this->classification
        );

        self::assertIsArray($children);
        self::assertEmpty($children);
    }

    /**
     * @return void
     */
    public function test_get_parents(): void {
        $parents = $this->resolve_graphql_type(
            $this->get_graphql_name(type_classification::class),
            "parents",
            $this->classification
        );

        self::assertIsArray($parents);
        self::assertEmpty($parents);
    }
}
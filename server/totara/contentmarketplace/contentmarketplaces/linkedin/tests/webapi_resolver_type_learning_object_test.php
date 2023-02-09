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

use contentmarketplace_linkedin\formatter\timespan_field_formatter;
use core\date_format;
use core\format;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\webapi\resolver\type\learning_object as type_learning_object;
use contentmarketplace_linkedin\entity\learning_object as entity;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_type_learning_object_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @var learning_object|null
     */
    private $learning_object;

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->learning_object = null;
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $generator = generator::instance();
        $entity = $generator->create_learning_object(
            "urn:lyndaCourse:252",
            [
                "title" => /** @lang text */"<script>alert('super');</script>",
                "locale_language" => "en",
                "locale_country" => "US",
                "description" => "Who what",
                "description_include_html" => /** @lang text */"<script>alert('bad');</script>",
                "short_description" => /** @lang text */"<script>alert('bad');</script>",

                // Time to complete is around 30 minutes.
                "time_to_complete" => 30 * MINSECS,
                "last_updated_at" => time() - MINSECS,
                "published_at" => time() - DAYSECS,
                'sso_launch_url' => 'www.example.com'
            ]
        );

        $this->learning_object = new learning_object($entity);
    }

    /**
     * @return void
     */
    public function test_get_name(): void {
        self::assertEquals(
            "alert('super');",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "name",
                $this->learning_object
            )
        );

        self::assertEquals(
            /** @lang text */"<script>alert('super');</script>",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "name",
                $this->learning_object,
                ["format" => format::FORMAT_RAW],
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_id(): void {
        self::assertEquals(
            $this->learning_object->id,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "id",
                $this->learning_object,
                [],
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_language(): void {
        self::assertEquals(
            "en",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "language",
                $this->learning_object,
                [],
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_description(): void {
        self::assertEquals(
            "Who what",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "description",
                $this->learning_object,
                ["format" => format::FORMAT_RAW]
            )
        );

        self::assertEquals(
            "Who what",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "description",
                $this->learning_object,
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_description_include_html(): void {
        self::assertEquals(
            /** @lang text */"<script>alert('bad');</script>",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "description_include_html",
                $this->learning_object,
                ["format" => format::FORMAT_RAW]
            )
        );

        self::assertEquals(
            "alert('bad');",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "description_include_html",
                $this->learning_object,
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_short_description(): void {
        self::assertEquals(
        /** @lang text */"<script>alert('bad');</script>",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "short_description",
                $this->learning_object,
                ["format" => format::FORMAT_RAW]
            )
        );

        self::assertEquals(
            "alert('bad');",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "short_description",
                $this->learning_object,
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_display_level(): void {
        self::assertEquals(
            $this->learning_object->display_level,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "display_level",
                $this->learning_object,
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_last_updated_at(): void {
        self::assertEquals(
            userdate(
                $this->learning_object->last_updated_at,
                get_string("strftimedate", "langconfig")
            ),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "last_updated_at",
                $this->learning_object,
                ["format" => date_format::FORMAT_DATE]
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_published_at(): void {
        self::assertEquals(
            userdate(
                $this->learning_object->published_at,
                get_string("strftimedate", "langconfig")
            ),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "published_at",
                $this->learning_object,
                ["format" => date_format::FORMAT_DATE]
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_classifications(): void {
        $classifications = $this->resolve_graphql_type(
            $this->get_graphql_name(type_learning_object::class),
            "classifications",
            $this->learning_object
        );

        self::assertIsArray($classifications);
        self::assertEmpty($classifications);
    }

    /**
     * @return void
     */
    public function test_get_time_to_complete(): void {
        self::assertEquals(
            30 * MINSECS,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "time_to_complete",
                $this->learning_object,
                ["format" => timespan_field_formatter::FORMAT_SECONDS]
            )
        );

        self::assertEquals(
            get_string(
                "timespan_format_minutes",
                "contentmarketplace_linkedin",
                [
                    "minutes" => 30,
                ]
            ),
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "time_to_complete",
                $this->learning_object,
                ["format" => timespan_field_formatter::FORMAT_HUMAN]
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_asset_type(): void {
        self::assertEquals(
            $this->learning_object->asset_type,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "asset_type",
                $this->learning_object
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_image_url(): void {
        self::assertNull(
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "image_url",
                $this->learning_object
            )
        );

        // Update the image url.
        $entity = new entity($this->learning_object->get_id());
        $entity->primary_image_url = "https://example.com?filename=cool_guy_kev.png";

        $entity->save();
        $this->learning_object->refresh();

        self::assertEquals(
            "https://example.com?filename=cool_guy_kev.png",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "image_url",
                $this->learning_object
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_web_launch_url_type(): void {
        self::assertEquals(
            $this->learning_object->web_launch_url,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "web_launch_url",
                $this->learning_object
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_sso_launch_url_type(): void {
        self::assertEquals(
            $this->learning_object->sso_launch_url,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_learning_object::class),
                "sso_launch_url",
                $this->learning_object
            )
        );
    }
}
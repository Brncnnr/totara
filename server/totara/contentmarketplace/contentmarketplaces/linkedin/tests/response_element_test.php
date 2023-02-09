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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\dto\timestamp;
use contentmarketplace_linkedin\exception\json_validation_exception;
use core_phpunit\testcase;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\element;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_response_element_testcase extends testcase {
    /**
     * @return void
     */
    public function test_instantiate_element_from_invalid_json(): void {
        $json_data = (object) [
            'urn' => 'urn:li:lyndaCourse:252',
            'title' => (object) [
                'value' => 'Title one',
                'locale' => (object) [
                    'country' => 'US'
                ]
            ],
            'type' => constants::ASSET_TYPE_COURSE
        ];

        $this->expectException(json_validation_exception::class);
        $this->expectExceptionMessage(
            "Failed to validate the json data: Missing field 'language', within object at field 'locale'."
        );

        element::create($json_data);
    }

    /**
     * @return void
     */
    public function test_instantiate_element_from_valid_json(): void {
        $time_now = time();
        $json_data = (object) [
            'urn' => 'urn:li:lyndaCourse:252',
            'title' => (object) [
                'value' => 'this is title',
                'locale' => (object) [
                    'language' => 'en',
                    'country' => 'US'
                ]
            ],
            'type' => constants::ASSET_TYPE_COURSE,
            'details' => (object) [
                'level' => constants::DIFFICULTY_LEVEL_BEGINNER,
                'images' => (object) [],
                'lastUpdatedAt' => $time_now * timestamp::MILLISECONDS_IN_SECOND,
                'publishedAt' => $time_now * timestamp::MILLISECONDS_IN_SECOND,
                'urls' => (object) [],
                'descriptionIncludingHtml' => (object) [
                    'value' => /** @lang text */'Hello <i>world</i>',
                    'locale' => (object) [
                        'language' => 'en',
                        'country' => 'US'
                    ]
                ],
                'shortDescription' => (object) [
                    'value' => 'short description',
                    'locale' => (object) [
                        'language' => 'en',
                        'country' => 'US'
                    ]
                ],
            ]
        ];

        $element = element::create($json_data);

        self::assertEquals('urn:li:lyndaCourse:252', $element->get_urn());
        self::assertEquals('en_US', $element->get_title_locale()->__toString());
        self::assertEquals('this is title', $element->get_title_value());

        self::assertEquals($time_now, $element->get_last_updated_at()->get_timestamp());
        self::assertEquals($time_now, $element->get_published_at()->get_timestamp());

        self::assertEquals("Hello <i>world</i>", $element->get_description_include_html());
        self::assertEquals("en_US", $element->get_description_include_html_locale()->__toString());

        self::assertEquals("short description", $element->get_short_description_value());
        self::assertEquals("en_US", $element->get_short_description_locale()->__toString());
        self::assertEquals(constants::DIFFICULTY_LEVEL_BEGINNER, $element->get_level());

        self::assertNull($element->get_primary_image_url());
        self::assertNull($element->get_web_launch_url());
        self::assertNull($element->get_sso_launch_url());

        self::assertEquals(constants::ASSET_TYPE_COURSE, $element->get_type());
        self::assertIsArray($element->get_classifications());
        self::assertEmpty($element->get_classifications());
    }
}
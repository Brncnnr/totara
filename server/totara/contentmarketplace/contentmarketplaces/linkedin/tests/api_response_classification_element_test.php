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

use contentmarketplace_linkedin\api\v2\service\learning_classification\response\element;
use contentmarketplace_linkedin\constants;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_api_response_classification_element_testcase extends testcase {
    /**
     * @return void
     */
    public function test_instantiatet_element_from_json_data(): void {
        $generator = generator::instance();
        $json_data = $generator->get_json_content_from_fixtures('classification_1.json');

        $json = json_decode($json_data, false, 512, JSON_THROW_ON_ERROR);
        $element = element::create($json);

        self::assertObjectHasAttribute('urn', $json);
        self::assertObjectHasAttribute('name', $json);
        self::assertObjectHasAttribute('owner', $json);
        self::assertObjectHasAttribute('type', $json);

        self::assertTrue(constants::is_valid_classification_type($json->type));
        self::assertEquals($json->urn, $element->get_urn());
        self::assertEquals($json->type, $element->get_type());

        $name_json = $json->name;
        self::assertIsObject($name_json);
        self::assertObjectHasAttribute('value', $name_json);
        self::assertObjectHasAttribute('locale', $name_json);

        self::assertEquals($name_json->value, $element->get_name_value());

        $locale_json = $name_json->locale;
        self::assertIsObject($locale_json);
        self::assertObjectHasAttribute('language', $locale_json);
        self::assertObjectHasAttribute('country', $locale_json);

        $locale = $element->get_name_locale();
        self::assertEquals($locale_json->language, $locale->get_lang());
        self::assertEquals($locale_json->country, $locale->get_country());
    }
}
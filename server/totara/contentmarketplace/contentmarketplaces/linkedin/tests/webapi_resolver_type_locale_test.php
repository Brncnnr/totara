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
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;
use contentmarketplace_linkedin\dto\locale;
use contentmarketplace_linkedin\webapi\resolver\type\locale as type_locale;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_webapi_resolver_type_locale_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_language(): void {
        $locale = new locale("en", "US");
        self::assertEquals(
            "en",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_locale::class),
                "language",
                $locale
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_country(): void {
        $locale = new locale("en", "US");
        self::assertEquals(
            "US",
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_locale::class),
                "country",
                $locale
            )
        );
    }

    /**
     * @return void
     */
    public function test_get_country_as_null(): void {
        $locale = new locale("en", null);
        self::assertNull(
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_locale::class),
                "country",
                $locale
            )
        );
    }

    /**
     * Providing the list of languages for the test.
     *
     * @internal
     * @return array
     */
    public static function provided_languages(): array {
        $language_pack = "core_iso6392";
        return [
            ["en", "US", get_string("eng", $language_pack)],
            ["de", "DE", get_string("deu", $language_pack)],
            ["ja", "JP", get_string("jpn", $language_pack)],
            ["pt", "BR", get_string("por", $language_pack)],
            ["zh", "CN", get_string("zho", $language_pack)],
            ["fr", "FR", get_string("fra", $language_pack)],
            ["es", "ES", get_string("spa", $language_pack)]
        ];
    }

    /**
     * @dataProvider provided_languages
     * @param string $language
     * @param string $country
     * @param string $label
     * @return void
     */
    public function test_get_language_label(string $language, string $country, string $label): void {
        $locale = new locale($language, $country);
        self::assertEquals(
            $label,
            $this->resolve_graphql_type(
                $this->get_graphql_name(type_locale::class),
                "language_label",
                $locale
            )
        );
    }
}
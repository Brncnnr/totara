<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use totara_core\util\language;

/**
 * @coversDefaultClass totara_core\util\language
 */
class totara_core_botfw_util_language_testcase extends advanced_testcase {
    public function setUp(): void {
        $sm = get_string_manager();
        $rc = new ReflectionClass('core_string_manager_standard');
        $get_key_suffix = $rc->getMethod('get_key_suffix');
        $get_key_suffix->setAccessible(true);
        $rccache = $rc->getProperty('menucache');
        $rccache->setAccessible(true);
        $cachekey = 'list_'.$get_key_suffix->invokeArgs($sm, array());
        $cache = $rccache->getValue($sm);
        $cache->set($cachekey, [
            'en' => 'English (en)',
            'fr' => 'French (fr)',
            'es' => 'Spanish (es)',
            'ru' => 'Russian (ru)',
            'zh_cn' => 'Mainland Chinese (zh_cn)',
            'zh_tw' => 'Taiwanese (zh_tw)',
        ]);
        $this->assertCount(6, get_string_manager()->get_list_of_translations(true));
    }

    public function data_totara_language_code() {
        return [
            ['en', 'en'],
            ['en-NZ', 'en'],
            ['en_NZ', 'en'],
            ['en-nz', 'en'],
            ['En-zA', 'en'],
            ['fr-CA', 'fr'],
            ['es-Mx', 'es'],
            ['ru-ru', 'ru'],
            ['zh-cn', 'zh_cn'],
            ['zh_CN', 'zh_cn'],
            ['zh-TW', 'zh_tw'],
            ['ZH_tw', 'zh_tw'],
            ['zh-hk', null], // not installed; not even fallback to any other Chinese variant
            ['af-ZA', null], // not installed
            ['en/nz', null], // invalid
            ['English', null], // invalid
        ];
    }

    /**
     * @param string $input
     * @param string|null $expected
     * @dataProvider data_totara_language_code
     * @covers ::convert_to_totara_format
     */
    public function test_convert_to_totara_format(string $input, ?string $expected) {
        $this->assertSame($expected, language::convert_to_totara_format($input, false));
    }

    public function data_ietf_language_code() {
        return [
            ['en', 'en'],
            ['en-nz', 'en-NZ'],
            ['en_nz', 'en-NZ'],
            ['en-NZ', 'en-NZ'],
            ['EN-nZ', 'en-NZ'],
            ['en/nZ', 'en/nz'], // invalid
            ['English', 'english'], // invalid
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     * @dataProvider data_ietf_language_code
     * @covers ::convert_to_ietf_format
     */
    public function test_convert_to_ietf_format(string $input, string $expected) {
        $this->assertSame($expected, language::convert_to_ietf_format($input));
    }
}

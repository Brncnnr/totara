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

use totara_core\util\base64url;

/**
 * @coversDefaultClass totara_core\util\base64url
 */
class totara_core_util_base64url_testcase extends advanced_testcase {
    public function data_decode_failure() {
        return [
            ['123+4567'],
            ['123/4567'],
            ['123*4567'],
        ];
    }

    /**
     * @param string $input
     * @dataProvider data_decode_failure
     * @covers ::decode
     */
    public function test_decode_failure(string $input) {
        $decode = base64url::decode($input);
        $this->assertFalse($decode);
    }

    public function data_decode_success(): array {
        return [
            ['', ''],
            ['WA', 'WA=='],
            ['WA==', 'WA=='],
            ['k1A0rAkoUt0u', 'k1A0rAkoUt0u'],
            ['2a-3b_4c', '2a+3b/4c'],
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     * @dataProvider data_decode_success
     * @covers ::decode
     */
    public function test_base64url_decode_success(string $input, string $expected) {
        $decode = base64url::decode($input);
        $this->assertNotFalse($decode);
        $this->assertEquals($expected, base64_encode($decode));
    }

    public function data_encode(): array {
        return [
            ['', ''],
            ['kia ora', 'a2lhIG9yYQ'],
            ["\u{1F333}\u{1D4E3}\u{1D4F8}\u{1D4FD}\u{1D4EA}\u{1D4FB}\u{1D4EA}\u{1F332}", '8J-Ms_Cdk6PwnZO48J2TvfCdk6rwnZO78J2TqvCfjLI']
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     * @dataProvider data_encode
     * @covers ::encode
     */
    public function test_encode(string $input, string $expected) {
        $this->assertEquals($expected, base64url::encode($input));
    }
}

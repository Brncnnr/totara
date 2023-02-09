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
 * @package totara_msteams
 */

use totara_msteams\manifest_helper;

defined('MOODLE_INTERNAL') || die;

/**
 * Test manifest_helper class.
 */
class totara_msteams_manifest_helper_testcase extends advanced_testcase {
    public function test_is_valid_guid() {
        $cases = [
            [ false, false ],
            [ 1, false ],
            [ '', false ],
            [ 'kia ora', false ],
            [ '00000000-0000-0000-0000-000000000000', false ],
            [ '0000000n-0000-0000-0000-000000000000', false ],
            [ '00000000-0z00-0000-0000-000000000000', false ],
            [ '00000000-0000-00N0-0000-000000000000', false ],
            [ '00000000-0000-0000-000Z-000000000000', false ],
            [ '00000000-0000-0000-0000-00000000000!', false ],
            [ '{31415926-5358-9793-2384-626433832795}', false ],
            [ '31415926535897932384626433832795', false ],
            [ '31415926-5358-97932384-6264-33832795', false ],
            [ ' 31415926-5358-9793-2384-626433832795', false ],
            [ '31415926-5358-9793-2384-626433832795 ', false ],
            [ '31415926-5358-9793-2384-626433832795', true ],
            [ '82baDFa5-7f00-DfEe-151C-C0600dc0fFEe', true ],
        ];

        foreach ($cases as [$input, $expected]) {
            $this->assertEquals($expected, manifest_helper::is_valid_guid($input));
        }
    }

    public function test_utf16_strlen() {
        $cases = [
            'null' => [ null, 0 ],
            'false' => [ false, 0 ],
            '(empty)' => [ '', 0 ],
            // 0 is converted to '0', so 1 character.
            '0' => [ 0, 1 ],
            // "New Zealand" is 11 characters.
            'NZL' => [ 'New Zealand', 11 ],
            // "New Zealand" in Ukrainian is 13 characters, not 25 characters.
            'UKR' => [ json_decode('"\u041D\u043E\u0432\u0430 \u0417\u0435\u043B\u0430\u043D\u0434\u0456\u044F"'), 13 ],
            // "New Zealand" in Chinese simplified is 3 characters, not 9 characters.
            'CHS' => [ json_decode('"\u65B0\u897F\u5170"'), 3 ],
            // The New Zealand flag (U+1F1F3, U+1F1FF) is 4 characters, not 8 characters.
            'Flag' => [ json_decode('"\uD83C\uDDF3\uD83C\uDDFF"'), 4 ],
        ];

        foreach ($cases as $case => [$input, $expected]) {
            $this->assertEquals($expected, manifest_helper::utf16_strlen($input), $case);
        }
    }
}

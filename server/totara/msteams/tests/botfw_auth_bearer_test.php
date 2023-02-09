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

use totara_msteams\botfw\auth\bearer;

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_botfw_auth_bearer_testcase extends advanced_testcase {
    /**
     * @return array
     */
    public function data_validate_header(): array {
        $valid_jwt = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.e30.BMI_HUYbnGhqzQJclgarZIi-AvtIdZVwNWJFj6tJ9nc';
        return [
            'empty array' => [[], false],
            'header not provided' => [['Host' => 'localhost:99999'], false],
            'not in the UK' => [['Authorisation' => 'Bearer '.$valid_jwt], false],
            'no auth scheme' => [['Authorization' => $valid_jwt], false],
            'garbage at the end' => [['Authorization' => 'Bearer '.$valid_jwt.'@'], false],
            'bogus jwt' => [['Authorization' => 'Bearer eyJhbGciOiJub25lIn0.W10.bG9yZW1pcHN1bSE_'], false],
            'valid case 1' => [['Authorization' => 'Bearer '.$valid_jwt], true],
            'valid case 2' => [['authorization' => 'Bearer '.$valid_jwt], true],
            'valid case 3' => [['Authorization' => 'bearer '.$valid_jwt], true],
        ];
    }

    /**
     * @param array $input
     * @param boolean $expected
     * @dataProvider data_validate_header
     */
    public function test_validate_header(array $input, bool $expected) {
        $this->assertEquals($expected, bearer::validate_header($input) !== null);
    }
}

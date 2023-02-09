<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package core_oauth2
 */

defined('MOODLE_INTERNAL') || die();

use core\oauth2\client;
use core_phpunit\testcase;

class core_oauth2_client_testcase extends testcase {

    public function test_map_userinfo_to_fields() {
        $map_user_info_to_fields_method = new ReflectionMethod(client::class, 'map_user_info_to_fields');
        $map_user_info_to_fields_method->setAccessible(true);

        $client_mock = $this->getMockBuilder(client::class)
            ->onlyMethods(['get_userinfo_mapping'])
            ->disableOriginalConstructor()
            ->getMock();
        $client_mock->expects($this->once())->method('get_userinfo_mapping')->willReturn([
            'nickname' => 'alternatename',
            'domain-url' => 'url',
            'https://totara\-learning/info' => 'info',
            'domain-url2-url' => 'url2',
            '"badlyformatted-prop' => 'i am a bad prop',
            'iameven"worse-"prop' => 'yes i am',
            '"howdo-"-"ieven-work"-' => 'i dont know',
            'assessment-start\-date' => 'start date',
            'learning-catalog\-id-number' => 'id number',
            'prop-with-end\-hyphen\-' => 'end hyphen',
            '\-prop-with\-start-hyphen' => 'start hyphen'
        ]);
        $user_info = new stdClass();
        $user_info->nickname = 'nick';
        $user_info->email = 'test@test.test';
        $user_info->domain = (object) ['url' => 'https://totaralearning.com'];
        $user_info->domain->url2 = (object) ['url' => 'url2'];
        $user_info->{'https://totara-learning/info'} = 'this is info';
        $user_info->{'"badlyformatted'} = (object) ['prop' => 'bad prop'];
        $user_info->{'iameven"worse'} = (object) ['"prop' => 'bad prop 2'];
        $user_info->{'"howdo'} = (object) ['"' => (object) ['"ieven' => (object) ['work"' => 'bad prop 3']]];
        $user_info->assessment = (object) ['start-date' => 'start-date'];
        $user_info->learning = (object) ['catalog-id' => (object) ['number' => 'catalog id number']];
        $user_info->prop = (object) ['with' => (object) ['end-hyphen-' => 'end-hyphen']];
        $user_info->{'-prop'} = (object) ['with-start' => (object) ['hyphen' => 'start-hyphen']];
        $result = $map_user_info_to_fields_method->invoke($client_mock, $user_info);
        $this->assertEquals([
            'alternatename' => 'nick',
            'url' => 'https://totaralearning.com',
            'info' => 'this is info',
            'url2' => 'url2',
            'i am a bad prop' => 'bad prop',
            'yes i am' => 'bad prop 2',
            'i dont know' => 'bad prop 3',
            'start date' => 'start-date',
            'id number' => 'catalog id number',
            'end hyphen' => 'end-hyphen',
            'start hyphen' => 'start-hyphen',
        ], $result);
    }
}

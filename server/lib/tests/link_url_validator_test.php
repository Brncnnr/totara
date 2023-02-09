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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

use core\link\url_validator;

defined('MOODLE_INTERNAL') || die();

class core_link_url_validator_testcase extends advanced_testcase {

    public function test_is_valid_without_block_or_allow_list() {
        $url = new moodle_url('https://totaralearning.com');

        $validator = new url_validator($url);
        $this->assertNotNull($validator->get_validated_ip());

        $url = new moodle_url('http://totaralearning.com');

        $validator = new url_validator($url);
        $this->assertNull($validator->get_validated_ip());
    }

    /**
     * @dataProvider get_host_ip_dataprovider
     * @param string $url
     * @param bool $is_valid
     */
    public function test_is_valid(string $url, bool $is_valid) {
        set_config('link_parser_allowed_hosts', 'totaralearning.com,8.8.8.8');
        set_config('link_parser_blocked_hosts', 'totaralms.com,8.8.4.4');

        $url = new moodle_url($url);

        $validator = new url_validator($url);
        $ip = $validator->get_validated_ip();
        if ($is_valid) {
            $this->assertNotNull($ip);
            $this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP));
        } else {
            $this->assertNull($ip);
        }
    }

    public function get_host_ip_dataprovider(): array {
        return [
            'invalid url' => ['123.123.123.123', false],
            'wrong scheme (ftp)' => ['ftp://www.example.com/fake/download/file', false],
            'wrong scheme (http)' => ['http://www.google.com/this/should/fail?with=query', false],
            'internal ip 127 range' => ['https://127.0.0.1/this/should/also/fail?with=query', false],
            'internal ip 192 range' => ['https://192.168.1.123/this/should/also/fail?with=query', false],
            'internal ip 10 range' => ['https://10.0.1.2]', false],
            'internal ip 169 range' => ['https://169.254.1.3]', false],
            'ipv6 reserved' => ['https://[2001:0db8:85a3:0000:0000:8a2e:0370:7334]/this/should/not/be/valid', false],
            'ipv6 local' => ['https://[fc00:0db8:85a3:0000:0000:8a2e:0370:7334]/this/should/not/be/valid', false],
            'ipv6 valid' => ['https://[2606:4700:20::681a:ada]/this/should/be/valid', true],
            'allowed 1' => ['https://totaralearning.com', true],
            'allowed 2' => ['https://8.8.8.8', true],
            'blocked 1' => ['https://totaralms.com', false],
            'blocked 2' => ['https://8.8.4.4', false],
        ];
    }

}
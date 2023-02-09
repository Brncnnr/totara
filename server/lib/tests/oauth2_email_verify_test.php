<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core_oauth2
 */

defined('MOODLE_INTERNAL') || die();

use core\oauth2\issuer as issuer;
use core_phpunit\testcase;

/**
 * Ensure that OAuth 2 issuer configuration has the requireconfirmation
 * checkbox checked as a default setting.
 *
 */
class core_oauth2_email_verify_testcase extends testcase {

    public function test_email_verify() {
        $issuer = new issuer();
        $this->assertTrue($issuer->has_property('requireconfirmation'));
        $this->assertEquals($issuer->get('requireconfirmation'), true);
    }
}

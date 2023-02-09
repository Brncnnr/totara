<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_oauth2
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test client for PHPUnit tests of \auth_plugin_oauth2::complete_login()
 */
class oauth2_test_client extends \core\oauth2\client {
    private $fakeuserinfo = false;

    public function __construct(\core\oauth2\issuer $issuer) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('Invalid test fixture use!');
        }
        parent::__construct($issuer, null, '', false);
    }

    public function set_fake_userinfo($userinfo) {
        $this->fakeuserinfo = $userinfo;
    }

    public function get_userinfo() {
        return $this->fakeuserinfo;
    }

    public function log_out() {
        $this->fakeuserinfo = false;
    }
}

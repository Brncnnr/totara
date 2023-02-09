<?php
/*
 * This file is part of Totara Learn
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package auth_ldap
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/auth/ldap/auth.php');

/**
 * @group core_auth
 */
class auth_ldap_auth_testcase extends advanced_testcase {

    public function test_user_confirm_secret_is_required() {
        global $DB;

        $auth_plugin = $this->getMockBuilder('auth_plugin_ldap')
            ->onlyMethods(['user_activate'])
            ->getMock();
        $auth_plugin
            ->method('user_activate')
            ->willReturn(true);

        $user = $this->getDataGenerator()->create_user(['auth' => $auth_plugin->authtype, 'secret' => 'abc']);
        $DB->set_field('user', 'confirmed', false, ['id' => $user->id]);

        // Fail with wrong secret.
        self::assertEquals(AUTH_CONFIRM_ERROR, $auth_plugin->user_confirm($user->username, 'xyz'));

        // Fail with 'true' (previous security vulnerability - see TL-29941).
        self::assertEquals(AUTH_CONFIRM_ERROR, $auth_plugin->user_confirm($user->username, true));

        // Pass with correct secret.
        self::assertEquals(AUTH_CONFIRM_OK, $auth_plugin->user_confirm($user->username, 'abc'));

        // Pass with correct secret but already confirmed.
        self::assertEquals(AUTH_CONFIRM_ALREADY, $auth_plugin->user_confirm($user->username, 'abc'));
    }

}

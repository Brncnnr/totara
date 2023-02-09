<?php
/**
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package auth_shibboleth
 */

class auth_shibboleth_auth_testcase extends advanced_testcase {

    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/auth/shibboleth/auth.php');
        parent::setUp();
    }

    public function test_validate_server_attribute_name_with_common_server_keys() {
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('PHP_SELF'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('REQUEST_METHOD'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('HTTPS'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('DOCUMENT_URI'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('CommonProgramFiles(x86)'));
    }

    public function test_validate_server_attribute_name_but_disabled() {
        global $CFG;

        $CFG->auth_shibboleth_disable_server_attribute_validation = false;
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('PHP_SELF'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('REQUEST_METHOD'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('HTTPS'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('DOCUMENT_URI'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('CommonProgramFiles(x86)'));

        $CFG->auth_shibboleth_disable_server_attribute_validation = true;
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('PHP_SELF'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('REQUEST_METHOD'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('HTTPS'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('DOCUMENT_URI'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('CommonProgramFiles(x86)'));

        unset($CFG->auth_shibboleth_disable_server_attribute_validation);
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('PHP_SELF'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('REQUEST_METHOD'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('HTTPS'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('DOCUMENT_URI'));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('CommonProgramFiles(x86)'));
    }

    public function test_validate_server_attribute_name_with_valid_keys() {
        // This is entirely configurable, and while different
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('uid'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('Shib-uid'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('Shib-Person-surname'));
    }

    public function test_validate_server_attribute_name_case_sensitivity() {
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_name('HTTPS'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('https'));
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_name('HTTPs'));
    }

    public function test_validate_server_attribute_names_valid() {
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_names([
            'username' => 'uid',
            'lastname' => 'Shib-Person-surname',
        ]));
    }

    public function test_validate_server_attribute_names_disabled() {
        global $CFG;

        $CFG->auth_shibboleth_disable_server_attribute_validation = false;
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_names([
            'username' => 'uid',
            'lastname' => 'HTTPS',
        ]));

        $CFG->auth_shibboleth_disable_server_attribute_validation = true;
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_names([
            'username' => 'uid',
            'lastname' => 'HTTPS',
        ]));

        unset($CFG->auth_shibboleth_disable_server_attribute_validation);
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_names([
            'username' => 'uid',
            'lastname' => 'HTTPS',
        ]));
    }

    public function test_validate_server_attribute_names_reserved() {
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_names([
            'username' => 'uid',
            'firstname' => 'HTTPS',
        ]));
    }

    public function test_validate_server_attribute_names_non_existent_keys() {
        self::assertTrue(auth_plugin_shibboleth::validate_server_attribute_names([
            'monkeychin' => 'uid',
        ]));
        self::assertFalse(auth_plugin_shibboleth::validate_server_attribute_names([
            'monkeychin' => 'HTTPS',
        ]));
    }

}

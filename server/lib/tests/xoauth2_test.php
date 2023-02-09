<?php
/**
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package core_xoauth2
 */

use core\oauth2\api;
use core\oauth2\issuer;
use core\oauth2\system_account;
use core\xoauth2\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Test Cases for the OAuth2 Helper
 */
class core_xoauth2_testcase extends advanced_testcase {

    /**
     * Assert an invalid issuer code will throw the correct exception
     */
    public function test_helper_with_invalid_generator(): void {
        $this->setAdminUser();

        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('oauth2servicefailure');
        helper::get_token_generator(0, 'abc');
    }

    /**
     * Assert a disabled issuer will throw an exception
     */
    public function test_helper_with_disabled_issuer(): void {
        $this->setAdminUser();

        // Create our disabled issuer
        $disabled_issuer = (new issuer(0, (object) [
            'name' => 'Disabled',
            'image' => 'https://example.com/image.png',
            'baseurl' => 'https://example.com/api/base',
            'enabled' => false,
        ]))->create();

        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('oauth2servicefailure');
        helper::get_token_generator($disabled_issuer->get('id'), 'abc');
    }

    /**
     * Assert a enabled issuer with no configured token will throw an exception
     */
    public function test_helper_with_enabled_unconfigured_issuer(): void {
        $this->setAdminUser();

        // Confirm no system account will throw an error
        $enabled_issuer = (new issuer(0, (object) [
            'name' => 'Enabled',
            'image' => 'https://example.com/image.png',
            'baseurl' => 'https://example.com/api/base',
            'enabled' => true,
            'scopessupported' => 'login',
            'loginscopes' => 'login',
            'loginscopesoffline' => 'login',
            'clientid' => 'abcd',
            'clientsecret' => 'abcd',
        ]))->create();

        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('oauth2servicefailure');
        helper::get_token_generator($enabled_issuer->get('id'), 'abc');
    }

    /**
     * Assert a enabled issuer with a configured account can be used
     */
    public function test_helper_with_enabled_configured_issuer(): void {
        global $CFG;
        $this->setAdminUser();

        $enabled_issuer = (new issuer(0, (object) [
            'name' => 'Enabled',
            'image' => 'https://example.com/image.png',
            'baseurl' => 'https://example.com/api/base',
            'enabled' => true,
            'scopessupported' => 'login',
            'loginscopes' => 'login',
            'loginscopesoffline' => 'login',
            'clientid' => 'abcd',
            'clientsecret' => 'abcd',
        ]))->create();
        api::create_endpoint((object) [
            'issuerid' => $enabled_issuer->get('id'),
            'name' => 'token_endpoint',
            'url' => 'https://example.com/token-url'
        ]);

        // Now create an oauth service account for the issuer
        (new system_account(0, (object) [
            'issuerid' => $enabled_issuer->get('id'),
            'refreshtoken' => 'abcd1234',
            'grantedscopes' => 'login',
            'email' => 'system@example.com',
            'username' => 'system_account',
        ]))->create();

        // Fake the token, since we're not testing the full oauth workflow here
        $response = json_encode([
            'access_token' => 'abcd-is-a-token',
            'expires_in' => 10000,
            'scope' => 'login',
        ]);
        curl::mock_response($response);

        // Assert we can read the correct token from the helper
        $token_generator = helper::get_token_generator($enabled_issuer->get('id'), 'abc-username');

        self::assertInstanceOf(Horde_Imap_Client_Password_Xoauth2::class, $token_generator);
        self::assertSame('abc-username', $token_generator->username);
        self::assertSame('abcd-is-a-token', $token_generator->access_token);

        // Assert phpmailer also sees the correct token when we use XOAUTH2
        require_once $CFG->libdir . '/phpmailer/moodle_phpmailer.php';
        $CFG->smtpauthtype = 'XOAUTH2';
        $CFG->smtpoauth2issuer = $enabled_issuer->get('id');
        $CFG->smtpuser = $token_generator->username;
        $mail = new moodle_phpmailer();

        self::assertInstanceOf(moodle_oauth::class, $mail->getOAuth());

        // Assert we generate the expected tokens
        $expected = $token_generator->getPassword();
        self::assertSame($expected, $mail->getOAuth()->getOauth64());

        // Assert regular login will not have an OAuth instance
        $CFG->smtpauthtype = 'LOGIN';
        $mail = new moodle_phpmailer();
        self::assertNull($mail->getOAuth());
    }

    /**
     * Asserting the expected enabled options appear in our list.
     */
    public function test_service_providers_configselect(): void {
        $this->setAdminUser();
        $select = helper::service_providers_configselect('abcd', 'abcd');

        // Default to just our message
        self::assertEqualsCanonicalizing(
            [get_string('selectoauth2issuer', 'admin')],
            $select->choices
        );

        api::create_standard_issuer('microsoft');
        api::create_standard_issuer('google');

        $select = helper::service_providers_configselect('abcd', 'abcd');

        self::assertEqualsCanonicalizing(
            [
                get_string('selectoauth2issuer', 'admin'),
                'Microsoft',
                'Google',
            ],
            $select->choices
        );
    }
}

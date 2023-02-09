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

use core\oauth2\api;
use core\oauth2\endpoint;
use totara_msteams\auth_helper;
use totara_msteams\exception\auth_exception;

defined('MOODLE_INTERNAL') || die;

/**
 * Test auth_helper class.
 */
class totara_msteams_auth_helper_testcase extends advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();
    }

    /**
     * Enable or disable the OAuth2 authentication plugin.
     *
     * @param boolean $enable
     */
    private static function enable_oauth2_plugin(bool $enable): void {
        set_config('auth', $enable ? 'oauth2' : '');
        core_plugin_manager::reset_caches();
    }

    /**
     * Assert a failure case.
     *
     * @param string $messageexpected
     */
    private function assert_oauth2_failure(string $messageexpected): void {
        try {
            auth_helper::get_oauth2_issuer(true);
            $this->fail('auth_exception expected.');
        } catch (auth_exception $ex) {
            $messageactual = $ex->get_error_message();
            $this->assertStringContainsString($messageexpected, $messageactual);
        }
    }

    public function test_get_oauth2_issuer() {
        $issuer = api::init_standard_issuer('microsoft')
            ->set('enabled', 0)         // filled later
            ->set('clientid', '')       // filled later
            ->set('clientsecret', '')   // filled later
            ->create();
        $issuerid = $issuer->get('id');

        // OAuth2 plugin is disabled.
        self::enable_oauth2_plugin(false);
        $this->assert_oauth2_failure(get_string('error:oauth2_disabled', 'totara_msteams'));

        // Not set.
        self::enable_oauth2_plugin(true);
        set_config('oauth2_issuer', '', 'totara_msteams');
        $this->assert_oauth2_failure(get_string('error:oauth2_noissuer', 'totara_msteams'));

        // No client id nor secret.
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');
        $this->assert_oauth2_failure(get_string('error:oauth2_issuerinvalid', 'totara_msteams', $issuer->get('name')));

        // No secret.
        $issuer
            ->set('clientid', '31415926-5358-9793-2384-626433832795')
            ->save();
        $this->assert_oauth2_failure(get_string('error:oauth2_issuerinvalid', 'totara_msteams', $issuer->get('name')));

        // Not enabled.
        $issuer
            ->set('clientid', '31415926-5358-9793-2384-626433832795')
            ->set('clientsecret', 'kIa0rAKoUt0u!')
            ->save();
        $this->assert_oauth2_failure(get_string('error:oauth2_issuerdisabled', 'totara_msteams', $issuer->get('name')));

        // No authorization end point.
        $issuer->set('enabled', true)->save();
        $this->assert_oauth2_failure(get_string('error:oauth2_missingendpoint', 'totara_msteams', ['type' => 'authorization', 'issuer' => $issuer->get('name')]));

        // No token end point.
        $record = (object) [
            'issuerid' => $issuerid,
            'name' => 'authorization_endpoint',
            'url' => 'https://example.com/dontcare/authorize'
        ];
        (new endpoint(0, $record))->create();
        $this->assert_oauth2_failure(get_string('error:oauth2_missingendpoint', 'totara_msteams', ['type' => 'token', 'issuer' => $issuer->get('name')]));

        // All good.
        $record = (object) [
            'issuerid' => $issuerid,
            'name' => 'token_endpoint',
            'url' => 'https://example.com/dontcare/token'
        ];
        (new endpoint(0, $record))->create();
        $result = auth_helper::get_oauth2_issuer(true);
        $this->assertNotNull($result);
        $this->assertEquals($issuerid, $result->get('id'));

        // Disabled.
        $issuer->set('enabled', false)->save();
        $this->assert_oauth2_failure(get_string('error:oauth2_issuerdisabled', 'totara_msteams', $issuer->get('name')));

        // Deleted.
        api::delete_issuer($issuerid);
        $this->assert_oauth2_failure(get_string('error:oauth2_noissuer', 'totara_msteams'));
    }
}

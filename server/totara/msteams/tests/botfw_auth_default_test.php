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
use core\oauth2\issuer;
use core\orm\query\exceptions\record_not_found_exception;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\entity\user_state;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\botfw\exception\user_not_found_exception;

class totara_msteams_botfw_auth_default_testcase extends advanced_testcase {
    /** @var default_authoriser */
    private $authoriser;

    /** @var activity */
    private $activity;

    /** @var channel_account */
    private $account;

    /** @var stdClass */
    private $user;

    public function setUp(): void {
        $this->authoriser = new default_authoriser();
        $this->activity = new activity();
        $this->activity->channelId = '19:kIa0RAkoUt0u';
        $this->account = new channel_account();
        $this->account->id = '29:L0r3M-1pSUM_dOL0R_s1T-AM3t';
        $this->user = $this->getDataGenerator()->create_user();
    }

    public function tearDown(): void {
        $this->authoriser = null;
        $this->activity = null;
        $this->account = null;
        $this->user = null;
    }

    /**
     * Add a dummy Microsoft OAuth2 service.
     *
     * @param string $clientid
     * @return integer
     */
    private static function add_microsoft_oauth2_issuer(string $clientid): int {
        return api::init_standard_issuer('microsoft')
            ->set('clientid', $clientid)
            ->set('clientsecret', 'kIa0rAKoUt0u!')
            ->set('enabled', 1)
            ->create()
            ->get('id');
    }

    /**
     * Add Microsoft end points for the dummy OAuth2 service.
     *
     * @param integer $issuerid The issuer ID returned by add_microsoft_oauth2_issuer()
     */
    private static function add_microsoft_oauth2_endpoints(int $issuerid): void {
        api::create_endpoints_for_standard_issuer('microsoft', new issuer($issuerid));
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

    private function mock_database(bool $verified): int {
        $mschannel = channel::repository()->where('channel_id', $this->activity->channelId)->one();
        if (!$mschannel) {
            $mschannel = new channel();
            $mschannel->channel_id = $this->activity->channelId;
            $mschannel->save();
        }
        $msuser = new user();
        $msuser->userid = $this->user->id;
        $msuser->verified = $verified;
        $msuser->teams_id = $this->account->id;
        $msuser->mschannelid = $mschannel->id;
        $msuser->save();
        return $msuser->id;
    }

    public function test_get_user_success_verified() {
        $msuserid = $this->mock_database(true);
        $msuser = $this->authoriser->get_user($this->activity, $this->account, true);
        $this->assertEquals($msuserid, $msuser->id);
    }

    public function test_get_user_success_not_verified() {
        $msuserid = $this->mock_database(false);
        $msuser = $this->authoriser->get_user($this->activity, $this->account, false);
        $this->assertEquals($msuserid, $msuser->id);
    }

    public function test_get_user_failure() {
        try {
            $this->authoriser->get_user($this->activity, $this->account);
            $this->fail('auth_required_exception expected');
        } catch (auth_required_exception $ex) {
        }

        $this->mock_database(false);
        try {
            $this->authoriser->get_user($this->activity, $this->account);
            $this->fail('auth_required_exception expected');
        } catch (auth_required_exception $ex) {
        }
    }

    public function test_delete_user() {
        $user = user::repository()->find_or_fail($this->mock_database(true));
        $this->authoriser->delete_user($user);
        try {
            $this->authoriser->delete_user($user);
            $this->fail('user_not_found_exception expected');
        } catch (user_not_found_exception $ex) {
        }

        $user = user::repository()->find_or_fail($this->mock_database(false));
        $this->authoriser->delete_user($user);
        try {
            $this->authoriser->delete_user($user);
            $this->fail('user_not_found_exception expected');
        } catch (user_not_found_exception $ex) {
        }
    }

    public function test_simulate_login_sso() {
        $this->setAdminUser();
        self::enable_oauth2_plugin(true);
        $issuerid = self::add_microsoft_oauth2_issuer('31415926-5358-9793-2384-626433832795');
        self::add_microsoft_oauth2_endpoints($issuerid);
        set_config('sso_app_id', '31415926-5358-9793-2384-626433832795', 'totara_msteams');
        set_config('sso_scope', 'api://example.com/31415926-5358-9793-2384-626433832795', 'totara_msteams');
        set_config('oauth2_issuer', $issuerid, 'totara_msteams');
        $this->setUser(null); // log out

        $time = time();
        $url = $this->authoriser->initiate_login();
        $stateid = $url->get_param('state');
        $this->assertNotEmpty($stateid);
        $userstate = user_state::repository()->find_or_fail($stateid);
        /** @var user_state $userstate */
        $this->assertEquals(sesskey(), $userstate->sesskey);
        $this->assertSame('', (string)$userstate->verify_code);
        $this->assertNull($userstate->userid);
        $this->assertGreaterThan($time, $userstate->timeexpiry);

        $this->setUser($this->user);
        $state = $this->authoriser->continue_login();
        $this->assertNull($state);

        // Invalid state id.
        $_GET['state'] = $stateid + 42;
        try {
            $state = $this->authoriser->continue_login();
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
        }

        // Login takes too long.
        $_GET['state'] = $stateid;
        $userstate->timeexpiry = time() - 30;
        $userstate->save();
        try {
            $state = $this->authoriser->continue_login();
            $this->fail('auth_required_exception expected');
        } catch (auth_required_exception $ex) {
            $this->assertEquals(get_string('botfw:error_auth_timeout', 'totara_msteams'), $ex->getMessage());
        }

        // Success.
        $_GET['state'] = $stateid;
        $userstate->timeexpiry = time() + 30;
        $userstate->save();
        $state = $this->authoriser->continue_login();
        $this->assertNotNull($state);
        $this->assertNotNull($state->verify_code);
        $this->assertEquals($this->user->id, $state->userid);

        // The user_state has already been taken.
        $_GET['state'] = $stateid;
        try {
            $state = $this->authoriser->continue_login();
            $this->fail('auth_required_exception expected');
        } catch (auth_required_exception $ex) {
            $this->assertEquals(get_string('botfw:error_auth_invalid', 'totara_msteams'), $ex->getMessage());
        }
    }
}

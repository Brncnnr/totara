<?php
/*
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use totara_mobile\local\util;

/**
 * Tests the mobile util class.
 */
class totara_mobile_util_testcase extends advanced_testcase {

    /**
     * Test that the native_auth_allowed method is working as expected.
     */
    public function test_native_auth_allowed() {
        global $CFG;

        // First create some users.
        $u1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $u2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        $u3 = $this->getDataGenerator()->create_user(['username' => 'user3']);

        // Set their auth properties.
        $u1->auth = 'manual';
        $u2->auth = 'ldap';
        $u3->auth = 'xyzzy';

        // Note that manual authentication is always available unless plugin has been deleted.
        $this->assertTrue(util::native_auth_allowed());

        // Test with users and default authentication plugins.
        $this->assertTrue(util::native_auth_allowed($u1));
        $this->assertFalse(util::native_auth_allowed($u2));
        $this->assertFalse(util::native_auth_allowed($u3));

        // Enable LDAP and test again.
        $CFG->auth = 'ldap';
        $this->assertTrue(util::native_auth_allowed($u1));
        $this->assertTrue(util::native_auth_allowed($u2));
        $this->assertFalse(util::native_auth_allowed($u3));

        // Enable LDAP NTLM SSO and test again.
        set_config('ntlmsso_enabled', '1', 'auth_ldap');
        $this->assertTrue(util::native_auth_allowed($u1));
        $this->assertFalse(util::native_auth_allowed($u2));
        $this->assertFalse(util::native_auth_allowed($u3));
    }

    /**
     * Test that the get_site_info method is working as expected.
     */
    public function test_get_site_info() {
        global $CFG;

        // Expected site info.
        $expected = [
            'auth' => 'native',
            'siteMaintenance' => '0',
            'theme' => [
                'urlLogo' => null,
                'colorPrimary' => '#69BD45',
                'colorText' => '#FFFFFF'
            ],
            'version' => util::API_VERSION,
            'app_version' => util::MIN_APP_VERSION
        ];
        // Test with default settings.
        $this->assertEquals($expected, util::get_site_info(util::MIN_APP_VERSION));

        // Change some settings.
        set_config('authtype', 'webview', 'totara_mobile');
        set_config('primarycolour', '#decafe', 'totara_mobile');
        set_config('textcolour', '#000000', 'totara_mobile');
        $expected['auth'] = 'webview';
        $expected['theme']['colorPrimary'] = '#decafe';
        $expected['theme']['colorText'] = '#000000';
        // Test with new settings.
        $this->assertEquals($expected, util::get_site_info(util::MIN_APP_VERSION));

        // site maintenance mode
        set_config('maintenance_enabled', 1);
        $expected['siteMaintenance'] = '1';
        // Test with new settings.
        $this->assertEquals($expected, util::get_site_info(util::MIN_APP_VERSION));

        // Test with a higher app version
        $expected['app_version'] = '999999';
        $this->assertEquals($expected, util::get_site_info('999999'));

        // Test with a lower app version
        $expected_upgrade = ['app_version' => '0.1.2', 'upgrade' => util::MIN_APP_VERSION];
        $this->assertEquals($expected_upgrade, util::get_site_info('0.1.2'));

        // Test with a higher lower app version
        $expected_upgrade['app_version'] = '0.9.9.9';
        $this->assertEquals($expected_upgrade, util::get_site_info('0.9.9.9'));

        // Now set a custom logo and make sure it's loaded correctly
        $fs = get_file_storage();

        $path = $CFG->dirroot.'/totara/mobile/tests/fixtures/fruit.jpg';

        set_config('logo', '/fruit.jpg', 'totara_mobile');

        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'totara_mobile',
            'filearea' => 'logo',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'fruit.jpg',
        ];

        $fs->create_file_from_pathname($filerecord, $path);

        $site_info = util::get_site_info(util::MIN_APP_VERSION);

        $this->assertMatchesRegularExpression(
            '/https:\/\/[a-z0-9\/\.]+\/1\/totara_mobile\/logo\/0\/fruit.jpg/',
            $site_info['theme']['urlLogo']
        );
    }

}

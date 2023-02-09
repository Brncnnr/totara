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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_totara_airnotifier
 */

namespace message_totara_airnotifier;

use curl;

defined('MOODLE_INTERNAL') || die();

/**
 * Class airnotifier_client implements methods for communicating with an AirNotifier server.
 *
 * @package message_totara_airnotifier
 */
class appcode_util {

    public const DEFAULT_HOST = 'https://push.totaralearning.com';
    public const DEFAULT_APPNAME = 'totara';
    protected const REQUEST_URL = 'https://subscriptions.totara.community/local/airnotifier/appcode.php';

    /**
     * Determine whether or not an appcode can be requested from the subscription portal.
     * Note: does not check whether the site is registered, that should be done separately.
     *
     * @return bool
     */
    public static function request_available(): bool {
        // Check AirNotifier message output settings are defaults
        $host = get_config(null, 'totara_airnotifier_host');
        $appname = get_config(null, 'totara_airnotifier_appname');
        $appcode = get_config(null, 'totara_airnotifier_appcode');
        if (empty($appcode) && $host == self::DEFAULT_HOST && $appname == self::DEFAULT_APPNAME) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Request an appcode from the subscription portal
     *
     * @return array should either have key 'appcode' or key 'error'
     */
    public static function request_appcode(): array {
        global $CFG;

        if ((defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING)) {
            return ['appcode' => 'behat'];
        }

        $data = [
            'siteidentifier' => $CFG->siteidentifier,
            'wwwroot' => $CFG->wwwroot,
            'registrationcode' => $CFG->registrationcode,
            'appname' => get_config(null, 'totara_airnotifier_appname'),
        ];

        $ch = new curl();
        $options = [];

        $body = $ch->post(self::REQUEST_URL, $data, $options);
        $response = json_decode($body, true);

        if (!empty($response['appcode'])) {
            return $response;
        } else {
            if (!empty($response['error'])) {
                return ['error' => $response['error']];
            } else {
                return ['error' => 'Unknown error'];
            }
        }
    }
}
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

namespace totara_msteams;

defined('MOODLE_INTERNAL') || die;

use auth_oauth2\api;
use core\oauth2\issuer;
use dml_missing_record_exception;
use totara_msteams\exception\auth_exception;

/**
 * Helper functions for authentication/authorisation.
 */
final class auth_helper {
    /**
     * Get the instance of a configured OAuth2 issuer.
     *
     * @return issuer
     * @throws auth_exception
     */
    private static function get_oauth2_issuer_worker(): issuer {
        if (!is_enabled_auth('oauth2')) {
            throw new auth_exception(get_string('error:oauth2_disabled', 'totara_msteams'));
        }
        $issuerid = get_config('totara_msteams', 'oauth2_issuer');
        if (!empty($issuerid)) {
            try {
                $issuer = new issuer($issuerid);
                if (!$issuer->is_configured()) {
                    throw new auth_exception(get_string('error:oauth2_issuerinvalid', 'totara_msteams', $issuer->get('name')));
                }
                if ($issuer->get('enabled') != 1) {
                    throw new auth_exception(get_string('error:oauth2_issuerdisabled', 'totara_msteams', $issuer->get('name')));
                }
                foreach (['authorization', 'token'] as $type) {
                    if (!$issuer->get_endpoint_url($type)) {
                        throw new auth_exception(get_string('error:oauth2_missingendpoint', 'totara_msteams', [
                            'type' => $type,
                            'issuer' => $issuer->get('name')
                        ]));
                    }
                }
                return $issuer;
            } catch (dml_missing_record_exception $ex) {
                // Swallow dml_missing_record_exception as we're going to throw our precious auth_exception instead.
            }
        }
        throw new auth_exception(get_string('error:oauth2_noissuer', 'totara_msteams'));
    }

    /**
     * Get the instance of a configured OAuth2 issuer.
     *
     * @param boolean $electrify Throw auth_exception if not found or not properly configured.
     * @return issuer|null
     * @throws auth_exception
     */
    public static function get_oauth2_issuer(bool $electrify = false): ?issuer {
        if ($electrify) {
            return self::get_oauth2_issuer_worker();
        } else {
            try {
                return self::get_oauth2_issuer_worker();
            } catch (auth_exception $ex) {
                return null;
            }
        }
    }
}

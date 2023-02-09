<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetorface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use totara_notification\recipient\recipient;
use totara_notification\recipient\virtual_recipient;
use totara_core\totara_user as ext_user;
use mod_facetoface\seminar;

/**
 * Class third_party
 *
 * The recipient referred to in this class are external users defined in the seminar third party setting
 *
 * @package mod_facetoface\recipient
 */
class third_party implements recipient, virtual_recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_third_party', 'mod_facetoface');
    }

    public static function get_user_ids(array $data): array {
        return [];
    }

    /**
     * @param array $data
     * @return array
     * @throws coding_exception
     *
     * Return an array of ext_users with the email address(es) of the seminars third party setting.
     */
    public static function get_user_objects(array $data): array {
        if (empty($data['seminar_id'])) {
            throw new coding_exception('missing seminar_id for third party seminar recipients');
        }

        $seminar = new seminar($data['seminar_id']);
        $third_parties = $seminar->get_thirdparty();

        if (empty($third_parties)) {
            return [];
        }

        $external_user = ext_user::get_external_user('');

        $recipients = [];
        foreach (explode(',', $third_parties) as $third_party) {
            if (validate_email($third_party)) {
                $recipient = clone($external_user);
                $recipient->email = $third_party;
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }
}

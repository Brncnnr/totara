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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\recipient;

use coding_exception;
use mod_perform\models\activity\participant_source;
use mod_perform\totara_notification\placeholder\external_participant;
use core_user;
use totara_core\totara_user as ext_user;
use totara_notification\recipient\recipient;
use totara_notification\recipient\virtual_recipient;

class participant implements recipient, virtual_recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_participant', 'mod_perform');
    }

    public static function get_user_ids(array $data): array {
        return [];
    }

    /**
     * @throws coding_exception
     * @throws \dml_exception
     */
    public static function get_user_objects(array $data): array {

        if ($data['participant_source'] == participant_source::INTERNAL ) {
            return array(core_user::get_user($data['participant_id']));
        }

        // Load the external user
        $external_participant = external_participant::from_id($data['participant_id']);
        $external_user = ext_user::get_external_user($external_participant->do_get('email'));

        return array($external_user);
    }
}
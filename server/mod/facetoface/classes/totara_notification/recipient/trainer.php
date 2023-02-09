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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use totara_notification\recipient\recipient;

/**
 * Class trainer
 *
 * The recipient referred to in this class is the trainer in a seminar.
 *
 * @package totara_notification\recipient
 */
class trainer implements recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_trainer', 'mod_facetoface');
    }

    public static function get_user_ids(array $data): array {
        if (!isset($data['trainer_user_id'])) {
            throw new coding_exception('Missing trainer_user_id');
        }
        return [$data['trainer_user_id']];
    }
}

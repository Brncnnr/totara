<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\recipient;

use coding_exception;
use totara_notification\recipient\recipient;

/**
 * Class subject
 *
 * The recipient referred to in this class is the activity subject user of the notification.
 *
 * @package mod_perform\recipient
 */
class subject implements recipient {

    public static function get_name(): string {
        return get_string('notification_participant_relationship_recipient_subject', 'mod_perform');
    }

    public static function get_user_ids(array $data): array {
        if (!isset($data['subject_user_id'])) {
            throw new coding_exception('Missing subject_user_id');
        }

        return [$data['subject_user_id']];
    }
}

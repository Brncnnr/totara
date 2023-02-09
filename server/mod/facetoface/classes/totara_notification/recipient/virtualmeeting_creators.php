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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use core\orm\query\builder;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_session;
use totara_notification\recipient\recipient;

/**
 * Class virtualmeeting_creators
 *
 * The recipient referred to in this class are the users who created or edited room with a virtual meeting
 *
 * @package mod_facetoface\recipient
 */
class virtualmeeting_creators implements recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_virtualmeeting_creators', 'mod_facetoface');
    }

    /**
     * Return an array of virtual meeting creator user ids.
     *
     * @param array $data
     * @return array
     */
    public static function get_user_ids(array $data): array {
        if (!isset($data['seminar_event_id'])) {
            throw new coding_exception('Missing seminar_event_id');
        }

        $recipients = [];

        $records = builder::table('user', 'u')
            ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.userid', 'u.id')
            ->join([room::DBTABLE, 'fr'], 'frvm.roomid', 'fr.id')
            ->join([room_dates_virtualmeeting::DBTABLE, 'frdvm'], 'frdvm.roomid', 'fr.id')
            ->join([seminar_session::DBTABLE, 'sd'], 'frdvm.sessionsdateid', 'sd.id')
            ->where('u.deleted', 0)
            ->where('u.suspended', 0)
            ->where('sd.sessionid', $data['seminar_event_id'])
            ->select_raw('distinct u.id')
            ->fetch();

        foreach ($records as $record) {
            $recipients[$record->id] = $record->id;
        }

        return $recipients;
    }
}

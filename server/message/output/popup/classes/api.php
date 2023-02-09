<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains class used to return information to display for the message popup.
 *
 * @package    message_popup
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_popup;

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;
use core\orm\query\order;
use stdClass;
use moodle_exception;
use core_user;

/**
 * Class used to return information to display for the message popup.
 *
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /**
     * Get popup notifications for the specified users. Nothing is returned if notifications are disabled.
     *
     * @param int $useridto the user id who received the notification
     * @param string $sort the column name to order by including optionally direction
     * @param int $limit limit the number of result returned
     * @param int $offset offset the result set by this amount
     * @return stdClass[] notification records
     *
     * @since 3.2
     */
    public static function get_popup_notifications($useridto = 0, $sort = 'DESC', $limit = 0, $offset = 0): array {
        global $USER;

        $sort = strtoupper($sort);
        if ($sort != 'DESC' && $sort != 'ASC') {
            throw new moodle_exception('invalid parameter: sort: must be "DESC" or "ASC"');
        }

        $sort = 'DESC' === $sort ? order::DIRECTION_DESC : order::DIRECTION_ASC;

        if (empty($useridto)) {
            $useridto = $USER->id;
        }

        // Is notification enabled ?
        if ($useridto == $USER->id) {
            $disabled = $USER->emailstop;
        } else {
            $user = core_user::get_user($useridto, "emailstop", MUST_EXIST);
            $disabled = $user->emailstop;
        }
        if ($disabled) {
            // Notifications are disabled.
            return [];
        }

        $builder = builder::table('notifications', 'n');
        $builder->select([
            'n.id',
            'n.useridfrom',
            'n.useridto',
            'n.subject',
            'n.fullmessage',
            'n.fullmessageformat',
            'n.fullmessagehtml',
            'n.smallmessage',
            'n.contexturl',
            'n.contexturlname',
            'n.timecreated',
            'n.component',
            'n.eventtype',
            'n.timeread'
        ]);

        // Only filtering those notifications that appear in the popup only.
        // Note that this can be a bit outdated if the cron has not yet run. It should be happening
        // prior to the migration. Because sometimes, the notifications after this patch are created
        // would sit in the table notifications already, but not yet those older notifications.
        $builder->join(['message_popup_notifications', 'mpn'], 'n.id', 'mpn.notificationid');
        $builder->where('n.useridto', $useridto);

        $builder->order_by('n.timecreated', $sort);
        $builder->order_by('n.timeread', $sort);
        $builder->order_by('n.id', $sort);

        $builder->offset($offset);
        $builder->limit($limit);

        $builder->results_as_objects();

        $collection = $builder->fetch_recordset();
        return $collection->to_array();
    }

    /**
     * Count the unread notifications for a user.
     *
     * @param int $useridto the user id who received the notification
     * @return int count of the unread notifications
     * @since 3.2
     */
    public static function count_unread_popup_notifications(int $useridto = 0): int {
        global $USER, $DB;

        if (empty($useridto)) {
            $useridto = $USER->id;
        }

        // Is notification enabled?
        if ($useridto == $USER->id) {
            $disabled = $USER->emailstop;
        } else {
            $user = core_user::get_user($useridto, "emailstop", MUST_EXIST);
            $disabled = $user->emailstop;
        }
        if ($disabled) {
            return 0;
        }

        $sql = '
            SELECT COUNT(mpn.id) FROM "ttr_message_popup_notifications" mpn
            INNER JOIN "ttr_notifications" n ON mpn.notificationid = n.id
            WHERE n.timeread IS NULL AND n.useridto = ?
        ';

        return $DB->count_records_sql($sql, [$useridto]);
    }
}
<?php
/*
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
 * @package totara_notification
 */

namespace totara_notification\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting all centralised notification logs.
 * These probably won't hold personal information but still might be contain data which is related to privacy
 */
class notification_logs extends item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdata_notification_logs', 'totara_notification'];
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Execute user data purging for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $evt_sql = "
            UPDATE {notification_event_log}
               SET event_data = NULL
             WHERE subject_user_id = :uid
        ";
        $DB->execute($evt_sql, ['uid' => $user->id]);

        $dlv_sql = "
            UPDATE {notification_delivery_log}
               SET address = NULL
             WHERE notification_log_id IN (
                     SELECT ntf.id
                       FROM {notification_log} ntf
                      WHERE ntf.recipient_user_id = :uid
                   )
        ";
        $DB->execute($dlv_sql, ['uid' => $user->id]);

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $export = new export();

        $sub_sql = "SELECT notification_log_id,
                           " . $DB->sql_group_concat('sub.delivery_channel', ', ') . " AS channels,
                           " . $DB->sql_group_concat('sub.address', ', ') . " AS address,
                           " . $DB->sql_group_concat('sub.has_error', ', ') . " AS errors
                      FROM {notification_delivery_log} sub
                  GROUP BY sub.notification_log_id
        ";

        $sql = "
            SELECT nft.*, evt.subject_user_id, evt.context_id, evt.component, evt.area, evt.item_id,
                   evt.resolver_class_name, evt.event_data,evt.has_error as event_error, evt.time_created,
                   dlv.channels, dlv.address, dlv.errors
              FROM {notification_log} nft
              JOIN {notification_event_log} evt
                ON nft.notification_event_log_id = evt.id
              JOIN ({$sub_sql}) dlv
                ON dlv.notification_log_id = nft.id
             WHERE nft.recipient_user_id = :uid1
                OR evt.subject_user_id = :uid2
        ";

        $params = ['uid1' => $user->id, 'uid2' => $user->id];

        $export->data['notifications'] = $DB->get_records_sql($sql, $params);
        return $export;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $sql = "
            SELECT COUNT(nft.id) as count
              FROM {notification_log} nft
              LEFT JOIN {notification_event_log} evt
                ON nft.notification_event_log_id = evt.id
             WHERE nft.recipient_user_id = :uid1
                OR evt.subject_user_id = :uid2
        ";
        $params = ['uid1' => $user->id, 'uid2' => $user->id];

        return $DB->count_records_sql($sql, $params);
    }
}

<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_user
 */

namespace core_user\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * All additional names of a user, like middlename, alternatename, etc.
 */
class additionalnames extends \totara_userdata\userdata\item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['additionalnames', 'core'];
    }

    /**
     * Returns sort order.
     * @return int
     */
    public static function get_sortorder() {
        return 150;
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
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $update = [];
        if ($user->lastnamephonetic !== null) {
            $update['lastnamephonetic'] = null;
        }
        if ($user->firstnamephonetic !== null) {
            $update['firstnamephonetic'] = null;
        }
        if ($user->middlename !== null) {
            $update['middlename'] = null;
        }
        if ($user->alternatename !== null) {
            $update['alternatename'] = null;
        }

        if (!empty($update)) {
            if (!$user->deleted) {
                // We do not want any unnecessary changes for deleted accounts.
                $update['timemodified'] = time();
            }

            $update['id'] = $user->id;
            $DB->update_record('user', (object)$update);

            if (!$user->deleted) {
                \core\event\user_updated::create_from_userid($user->id)->trigger();
            }
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
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
        $export = new export();

        $export->data = [
            'lastnamephonetic'  => trim($user->lastnamephonetic ?? ''),
            'firstnamephonetic' => trim($user->firstnamephonetic ?? ''),
            'middlename'        => trim($user->middlename ?? ''),
            'alternatename'     => trim($user->alternatename ?? '')
        ];

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
        $counts = [
            intval(trim($user->lastnamephonetic ?? '') !== ''),
            intval(trim($user->firstnamephonetic ?? '') !== ''),
            intval(trim($user->middlename ?? '') !== ''),
            intval(trim($user->alternatename ?? '') !== ''),
        ];

        return array_sum($counts);
    }

}
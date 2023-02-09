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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;
use totara_engage\entity\engage_bookmark;
use totara_engage\repository\bookmark_repository;

/**
 * GDPR for bookmark
 */
final class bookmark extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        global $DB;
        return (int) $DB->count_records('engage_bookmark', ['userid' => $user->id]);
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        global $DB;

        $result = $DB->delete_records('engage_bookmark', ['userid' => $user->id]);
        if ($result) {
            return self::RESULT_STATUS_SUCCESS;
        }

        return self::RESULT_STATUS_ERROR;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        /** @var bookmark_repository $repository */
        $repository = engage_bookmark::repository();
        $export = new export();

        $bookmarks = $repository->get_bookmarks_for_user($user->id);

        foreach ($bookmarks as $bookmark) {
            $export->data[] = [
                'id' => $bookmark->id,
                'item_id' => $bookmark->itemid,
                'component' => $bookmark->component,
                'time_created' => userdate($bookmark->timecreated)
            ];
        }

        return $export;
    }
}
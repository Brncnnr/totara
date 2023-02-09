<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_xapi
 */

namespace totara_xapi\userdata;

use context;
use core\orm\entity\repository;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_xapi\entity\xapi_statement;
use totara_xapi\model\xapi_statement as xapi_statement_model;

class statement extends item {

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param context $context Not used, as completions are independent of courses
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context): int {
        return static::query($user->id)->count();
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param context $context Not used, as completions are independent of courses
     * @return export result object
     */
    protected static function export(target_user $user, context $context): export {
        $data = static::query($user->id)
            ->order_by('id')
            ->get()
            ->map_to(xapi_statement_model::class)
            ->map(static function (xapi_statement_model $statement) {
                return [
                    'id' => (int) $statement->id,
                    'time_created' => (int) $statement->time_created,
                    'statement' => $statement->statement,
                ];
            })
            ->all();

        $export = new export();
        $export->data = [static::get_name() => $data];
        return $export;
    }

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus): bool {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * @param target_user $user
     * @param context $context Not used, as completions are independent of courses
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context): int {
        static::query($user->id)->delete();

        return static::RESULT_STATUS_SUCCESS;
    }

    /**
     * Repository query for user's xAPI statement data.
     *
     * @param int $user_id
     * @return repository
     */
    protected static function query(int $user_id): repository {
        return xapi_statement::repository()
            ->where('user_id', $user_id);
    }

}

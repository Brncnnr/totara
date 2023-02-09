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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

final class recommended_user extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status) {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * @return bool
     */
    public static function is_countable() {
        return false;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        global $DB;
        $DB->delete_records('ml_recommender_users', ['user_id' => $user->id]);

        return self::RESULT_STATUS_SUCCESS;
    }
}

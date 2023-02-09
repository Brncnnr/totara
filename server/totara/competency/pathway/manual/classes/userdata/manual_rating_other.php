<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package pathway_manual
 */

namespace pathway_manual\userdata;

use core\orm\entity\repository;
use pathway_manual\entity\rating;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This handles manual ratings made by a user for another user.
 *
 * Because purging an entire manual rating record could have cascading effects, we simply just remove the reference to the user
 * who made the manual rating, anonymising the record rather than removing it.
 *
 * @package pathway_manual\userdata
 */
class manual_rating_other extends manual_rating {

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 5; // 6th item of 6 in the 'Competencies' list.
    }

    /**
     * Purge user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        static::rating_query($user->id)
            ->update(['assigned_by' => null]);

        return static::RESULT_STATUS_SUCCESS;
    }

    /**
     * Manual rating repository query for ratings on other users
     *
     * @param int $user_id
     * @return repository
     */
    protected static function rating_query(int $user_id): repository {
        return rating::repository()
            ->where('user_id', '<>', $user_id)
            ->where('assigned_by', $user_id);
    }

}

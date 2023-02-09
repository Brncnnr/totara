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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\userdata;

use core\orm\entity\repository;

use pathway_perform_rating\entity\perform_rating as perform_rating_entity;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Does GDPR related processing for competency ratings given by the subject in a
 * perform activity.
 */
class perform_rating_self extends perform_rating {
    /**
     * {@inheritdoc}
     */
    public static function get_sortorder() {
        return 6;
    }

    /**
     * {@inheritdoc}
     */
    protected static function purge(target_user $user, \context $context) {
        static::rating_query($user->id)->delete();
        return static::RESULT_STATUS_SUCCESS;
    }

    /**
     * {@inheritdoc}
     */
    protected static function rating_query(int $user_id): repository {
        return perform_rating_entity::repository()
            ->where('user_id', $user_id)
            ->where('rater_user_id', $user_id);
    }
}
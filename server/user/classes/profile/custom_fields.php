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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core_user
 */

namespace core_user\profile;

use context_user;
use core\entity\user;
use core\orm\query\builder;
use stdClass;

final class custom_fields {
    /**
     * Construct an array with field data for this user.
     *
     * @param stdClass $user
     * @return array
     */
    public static function create(stdClass $user): array {
        $user_custom_fields = builder::table('user_info_data')
            ->join('user_info_field', 'fieldid', '=', 'id')
            ->where('userid', $user->id)
            ->select(['*', 'user_info_field.*'])
            ->fetch();

        $result = [];
        foreach ($user_custom_fields as $user_custom_field) {
            if (in_array($user_custom_field->visible, [PROFILE_VISIBLE_NONE, PROFILE_VISIBLE_PRIVATE])){
                $context_user = context_user::instance(user::logged_in()->id);
                if (!has_capability('moodle/user:viewalldetails', $context_user)) {
                    continue;
                }
            }

            $result[] = [
                'shortname' => format_string($user_custom_field->shortname),
                'data' => $user_custom_field->data,
                'data_format' => $user_custom_field->dataformat,
                'data_type' => strtoupper($user_custom_field->datatype)
            ];
        }

        return $result;
    }
}
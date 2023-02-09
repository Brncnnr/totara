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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\entity;

use core\orm\entity\entity;
use totara_msteams\botfw\repository\user_setting_repository;

/**
 * @property integer    $id
 * @property string     $area           area of settings
 * @property integer    $timecreated    time record created
 * @property integer    $timemodified   time record modified
 * @property string     $data           user's data
 * @property integer    $userid         totara user id
 * @method static user_setting_repository repository()
 */
class user_setting extends entity {
    public const TABLE = 'totara_msteams_user_settings';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return user_setting_repository::class;
    }
}

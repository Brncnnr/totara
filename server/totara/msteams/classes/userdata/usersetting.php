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

namespace totara_msteams\userdata;

use context;
use core\orm\query\builder;
use totara_msteams\botfw\entity\user_setting as user_setting_entity;
use totara_userdata\userdata\target_user;

/**
 * user_setting data.
 */
class usersetting extends userbase {
    /**
     * @inheritDoc
     */
    protected static function get_builder_for(target_user $user, context $context): builder {
        return builder::table(user_setting_entity::TABLE)->where('userid', $user->id);
    }
}

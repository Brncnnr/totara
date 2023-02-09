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
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * userbase class.
 */
abstract class userbase extends item {
    /**
     * Get the instance of the query builder that requests per-user data.
     *
     * @param target_user $user
     * @return builder
     */
    protected abstract static function get_builder_for(target_user $user, context $context): builder;

    /**
     * @inheritDoc
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_countable() {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected static function purge(target_user $user, context $context) {
        static::get_builder_for($user, $context)->delete();
        return parent::RESULT_STATUS_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    protected static function count(target_user $user, context $context) {
        return static::get_builder_for($user, $context)->count();
    }

    /**
     * @inheritDoc
     */
    protected static function export(target_user $user, context $context) {
        return parent::RESULT_STATUS_SKIPPED;
    }
}

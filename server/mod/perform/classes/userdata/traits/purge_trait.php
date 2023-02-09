<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 *
 */

namespace mod_perform\userdata\traits;

/**
 * Trait purge_trait
 * @package mod_perform\userdata\traits
 */
trait purge_trait {

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * Can user data of this item data be purged from system?
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus): bool {
        return true;
    }

    /**
     * Can user data of this item be exported from the system?
     * @return bool
     */
    public static function is_exportable(): bool {
        return false;
    }

    /**
     * Is the given context level compatible with this item?
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE
        ];
    }

    /**
     * Purge the files for the given user.
     *
     * @param int $user_id
     */
    abstract protected static function purge_files(int $user_id): void;

}

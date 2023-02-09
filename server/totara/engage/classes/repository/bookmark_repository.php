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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_engage\entity\engage_bookmark;

class bookmark_repository extends repository {

    /**
     * @param int $userid
     * @param int $itemid
     * @param string $component
     * @return bool
     */
    public function is_bookmarked(int $userid, int $itemid, string $component): bool {
        $builder = builder::table(engage_bookmark::TABLE)
            ->where('itemid', $itemid)
            ->where('component', $component)
            ->where('userid', $userid);

        return $builder->exists();
    }

    /**
     * @param int $userid
     * @param int $itemid
     * @param string $component
     * @return engage_bookmark|null
     */
    public function get_bookmark(int $userid, int $itemid, string $component): ?engage_bookmark {
        $builder = builder::table(engage_bookmark::TABLE)
            ->map_to(engage_bookmark::class)
            ->where('itemid', $itemid)
            ->where('component', $component)
            ->where('userid', $userid);

        /** @var engage_bookmark $entity */
        $entity = $builder->one();

        return $entity;
    }

    /**
     * @param int $userid
     * @param int $itemid
     * @param string $component
     */
    public function delete_bookmark(int $userid, int $itemid, string $component): void {
        builder::table(engage_bookmark::TABLE)
            ->where('itemid', $itemid)
            ->where('component', $component)
            ->where('userid', $userid)
            ->delete();
    }

    /**
     * @param int $userid
     * @return engage_bookmark[]
     */
    public function get_bookmarks_for_user(int $userid): array {
        $builder = builder::table(engage_bookmark::TABLE)
            ->map_to(engage_bookmark::class)
            ->where('userid', $userid);

        /** @var engage_bookmark[] $entities */
        $entities = $builder->fetch();

        return $entities;
    }
}
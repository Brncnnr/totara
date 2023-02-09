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
 * @package totara_engage
 */
namespace totara_engage\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_engage\entity\engage_resource;
use totara_engage\entity\engage_resource_completion;
use totara_engage\resource\resource_item;

final class resource_completion extends repository {
    /**
     * @param int $resource_id
     * @param int $user_id
     * @return bool
     */
    public function is_exist(int $resource_id, int $user_id): bool {
        return builder::table(engage_resource_completion::TABLE, 'rc')
            ->where('userid', $user_id)
            ->where('resourceid', $resource_id)
            ->exists();
    }

    /**
     * @param int $user_id
     * @param string $component
     * @return int
     */
    public function count_for_resources(int $user_id, string $component): int {
        return builder::table(engage_resource_completion::TABLE, 'rc')
            ->join([engage_resource::TABLE, 'er'], 'er.id', 'rc.resourceid')
            ->where('rc.userid', $user_id)
            ->where('er.resourcetype', $component)
            ->count();
    }

    /**
     * @param int $user_id
     * @param string $component
     * @return void
     */
    public function delete_by_userid(int $user_id, string $component): void {
        /** @var engage_resource_completion[] $entites */
        $entites = builder::table(engage_resource_completion::TABLE, 'rc')
            ->join([engage_resource::TABLE, 'er'], 'er.id', 'rc.resourceid')
            ->map_to(engage_resource_completion::class)
            ->where('rc.userid', $user_id)
            ->where('er.resourcetype', $component)
            ->fetch();

        foreach ($entites as $entity) {
            $entity->delete();
        }
    }

    /**
     * @param int $user_id
     * @param string $component
     * @return array
     */
    public function get_all(int $user_id, string $component): array {
        /** @var engage_resource_completion $resource_items */
        $resource_completions = builder::table(engage_resource_completion::TABLE, 'rc')
            ->join([engage_resource::TABLE, 'er'], 'er.id', 'rc.resourceid')
            ->map_to(engage_resource_completion::class)
            ->where('rc.userid', $user_id)
            ->where('er.resourcetype', $component)
            ->fetch();

        return $resource_completions;
    }
}
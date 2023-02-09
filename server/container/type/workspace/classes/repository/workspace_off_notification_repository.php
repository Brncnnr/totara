<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\repository;

use container_workspace\entity\workspace_off_notification;
use core\orm\entity\repository;
use core\orm\query\builder;

final class workspace_off_notification_repository extends repository {
    /**
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return workspace_off_notification|null
     */
    public function find_for_user_in_workspace(int $user_id, int $workspace_id): ?workspace_off_notification {
        $builder = builder::table(static::get_table());
        $builder->map_to(workspace_off_notification::class);

        $builder->where('course_id', $workspace_id);
        $builder->where('user_id', $user_id);

        /** @var workspace_off_notification|null $entity */
        $entity = $builder->one();
        return $entity;
    }

    /**
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return bool
     */
    public function exists_for_user_in_workspace(int $user_id, int $workspace_id): bool {
        $builder = builder::table(static::get_table());
        $builder->where('course_id', $workspace_id);
        $builder->where('user_id', $user_id);

        return $builder->exists();
    }
}
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
namespace container_workspace\entity;

use container_workspace\repository\workspace_off_notification_repository;
use core\orm\entity\entity;

/**
 * For workspace notification to tell when user had turn off notification for the workspace.
 *
 * @property int $course_id
 * @property int $user_id
 * @property int $time_created
 *
 * @method static workspace_off_notification_repository repository()
 */
final class workspace_off_notification extends entity {
    /**
     * @var string
     */
    public const TABLE = 'workspace_off_notification';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return workspace_off_notification_repository::class;
    }
}
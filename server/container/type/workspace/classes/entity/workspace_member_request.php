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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\entity;

use container_workspace\repository\workspace_member_request_repository;
use core\orm\entity\entity;

/**
 * @property int           $user_id
 * @property int           $course_id
 * @property int           $time_created
 * @property int|null      $time_accepted
 * @property int|null      $time_declined
 * @property int|null      $time_cancelled
 * @property string|null   $request_content
 * @property string|null   $decline_content
 *
 * @method static workspace_member_request_repository repository()
 */
final class workspace_member_request extends entity {
    /**
     * @var string
     */
    public const TABLE = "workspace_member_request";

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'time_created';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return workspace_member_request_repository::class;
    }
}
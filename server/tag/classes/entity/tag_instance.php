<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package core_tag
 */
namespace core_tag\entity;

use core\orm\entity\entity;
use core_tag\repository\tag_instance_repository;

/**
 * @property int    $tagid
 * @property string $component
 * @property string $itemtype
 * @property int    $itemid
 * @property int    $contextid
 * @property int    $tiuserid
 * @property int    $ordering
 * @property int    $timecreated
 * @property int    $timemodified
 *
 * @method static tag_instance_repository repository()
 */
final class tag_instance extends entity {
    /**
     * @var string
     */
    public const TABLE = 'tag_instance';

    /**
     * @var string
     */
    public const CREATED_TIMESTAMP = 'timecreated';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'timemodified';

    /**
     * @var bool
     */
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return tag_instance_repository::class;
    }
}
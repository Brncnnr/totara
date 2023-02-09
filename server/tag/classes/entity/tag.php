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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package core_tag
 */
namespace core_tag\entity;

use core\orm\entity\entity;
use core_tag\repository\tag_repository;

/**
 * @property int    $userid
 * @property string $name
 * @property string $rawname
 * @property string $description
 * @property int    $descriptionformat
 * @property int    $flag
 * @property int    $timemodified
 * @property int    $tagcollid
 * @property int    $isstandard
 *
 * @method static tag_repository repository()
 */
final class tag extends entity {
    /**
     * @var string
     */
    public const TABLE = 'tag';

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
        return tag_repository::class;
    }
}
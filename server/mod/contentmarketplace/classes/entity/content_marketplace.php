<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\entity;

use core\entity\course;
use core\entity\course_module;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_one;
use mod_contentmarketplace\repository\content_marketplace_repository;

/**
 * Entity class represent for table "ttr_contentmarketplace"
 *
 * @property int    $id
 * @property int    $course
 * @property string $name
 * @property string $learning_object_marketplace_component
 * @property int    $learning_object_id
 * @property int    $time_modified
 * @property int    $completion_condition
 * @property string $intro
 * @property int    $introformat
 *
 * @property-read course $course_entity
 * @property-read course_module $course_module
 *
 * @method static content_marketplace_repository repository()
 */
class content_marketplace extends entity {
    /**
     * @var string
     */
    public const TABLE = 'contentmarketplace';

    /**
     * @var string
     */
    public const UPDATED_TIMESTAMP = 'time_modified';

    /**
     * @var bool
     */
    public const SET_UPDATED_WHEN_CREATED = true;

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return content_marketplace_repository::class;
    }

    /**
     * @return belongs_to
     */
    public function course_entity(): belongs_to {
        return $this->belongs_to(course::class, 'course');
    }

    /**
     * @return has_one
     */
    public function course_module(): has_one {
        return $this->has_one(course_module::class, 'instance')
            ->join('modules', 'module', 'id')
            ->where('modules.name', self::TABLE)
            ->where('course', $this->course);
    }

}
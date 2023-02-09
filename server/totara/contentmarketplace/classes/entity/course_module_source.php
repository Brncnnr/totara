<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\entity;

use core\entity\course;
use core\entity\course_module;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;
use core\orm\entity\relations\has_one_through;
use totara_contentmarketplace\repository\course_module_source_repository;

/**
 * Entity class represent for table "ttr_totara_contentmarketplace_course_module_source"
 *
 * @property int $id
 * @property int $cm_id
 * @property string $marketplace_component
 * @property int $learning_object_id
 * @property-read int $course_id
 * @property-read course $course
 * @property-read course_module $module
 *
 * @method static course_module_source_repository repository()
 */
class course_module_source extends entity {
    /**
     * @var string
     */
    public const TABLE = 'totara_contentmarketplace_course_module_source';

    /**
     * @return string
     */
    public static function repository_class_name(): string {
        return course_module_source_repository::class;
    }

    /**
     * @return has_one_through
     */
    public function course(): has_one_through {
        return $this->has_one_through(
            course_module::class,
            course::class,
            'cm_id',
            'id',
            'course',
            'id'
        );
    }

    /**
     * @return int
     */
    protected function get_course_id_attribute(): int {
        return $this->module->course;
    }

    /**
     * @return belongs_to
     */
    public function module(): belongs_to {
        return $this->belongs_to(course_module::class, 'cm_id');
    }

}
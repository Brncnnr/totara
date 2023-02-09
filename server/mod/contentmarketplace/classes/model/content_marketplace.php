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
namespace mod_contentmarketplace\model;

use cm_info;
use context_module;
use core\entity\course;
use core\entity\course_module;
use core\orm\entity\model;
use mod_contentmarketplace\entity\content_marketplace as content_marketplace_entity;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_contentmarketplace\learning_object\abstraction\metadata\model as learning_object;
use totara_contentmarketplace\learning_object\factory;

/**
 * Model for content marketplace entity.
 *
 * Entity attributes:
 * @property-read int $id
 * @property-read string $name
 * @property-read string $learning_object_marketplace_component
 * @property-read int $learning_object_id
 * @property-read int $time_modified
 * @property-read int $completion_condition
 * @property-read string $intro
 * @property-read int $introformat
 * @property-read course_module $course_module
 *
 * Model attributes:
 * @property-read learning_object $learning_object
 * @property-read string $activity_module_marketplace_component
 * @property-read course $course
 * @property-read int $course_id
 * @property-read context_module $context
 * @property-read cm_info $cm_info
 */
class content_marketplace extends model {

    /**
     * @var content_marketplace_entity
     */
    protected $entity;

    /**
     * Will be lazy loaded by the getter method.
     * @var learning_object|null
     */
    private $internal_learning_object;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'learning_object_marketplace_component',
        'learning_object_id',
        'time_modified',
        'completion_condition',
        'intro',
        'introformat',
        'course_module',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'learning_object',
        'activity_module_marketplace_component',
        'course',
        'course_id',
        'context',
        'cm_info',
    ];

    /**
     * @param int $course_id
     * @param learning_object $learning_object
     * @param int|null $completion_condition
     *
     * @return content_marketplace
     */
    public static function create(
        int $course_id,
        learning_object $learning_object,
        ?int $completion_condition = null
    ): content_marketplace {
        $entity = new content_marketplace_entity();
        $entity->course = $course_id;
        $entity->learning_object_id = $learning_object->get_id();
        $entity->learning_object_marketplace_component = $learning_object::get_marketplace_component();
        $entity->name = $learning_object->get_name();
        $entity->completion_condition = $completion_condition;

        if ($learning_object instanceof detailed_model) {
            $description = $learning_object->get_description();
            if (!is_null($description)) {
                $entity->intro = $description->get_raw_value();
                $entity->introformat = $description->get_format();
            }
        }

        $entity->save();

        $model = self::load_by_entity($entity);
        $model->internal_learning_object = $learning_object;
        return $model;
    }

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return content_marketplace_entity::class;
    }

    /**
     * @param int $cm_id
     * @return content_marketplace
     */
    public static function from_course_module_id(int $cm_id): content_marketplace {
        $entity = content_marketplace_entity::repository()
            ->join('course_modules', 'id', 'instance')
            ->join('modules', 'course_modules.module', 'id')
            ->where('course_modules.id', $cm_id)
            ->where('modules.name', content_marketplace_entity::TABLE)
            ->one(true);

        return static::load_by_entity($entity);
    }

    /**
     * @return context_module
     */
    public function get_context(): context_module {
        return context_module::instance($this->course_module->id);
    }

    /**
     * @return course
     */
    public function get_course(): course {
        return $this->entity->course_entity;
    }

    /**
     * @return int
     */
    public function get_course_id(): int {
        return $this->entity->course;
    }

    /**
     * @return bool
     */
    public function delete(): bool {
        if ($this->is_deleted()) {
            debugging(
                "The record had already been deleted",
                DEBUG_DEVELOPER
            );

            return false;
        }

        $this->entity->delete();
        return $this->is_deleted();
    }

    /**
     * @return bool
     */
    public function is_deleted(): bool {
        return $this->entity->deleted();
    }

    /**
     * @return learning_object
     */
    public function get_learning_object(): learning_object {
        if (null === $this->internal_learning_object) {
            $resolver = factory::get_resolver($this->learning_object_marketplace_component);
            $this->internal_learning_object = $resolver->find($this->learning_object_id, true);
        }

        return $this->internal_learning_object;
    }

    /**
     * @return string
     */
    public function get_activity_module_marketplace_component(): string {
        return str_replace('contentmarketplace_', 'contentmarketplaceactivity_', $this->learning_object_marketplace_component);
    }

    /**
     * Get the course module info instance for this content marketplace module and user.
     *
     * @param int|null $user_id The subject user ID. If null, the current user will be used.
     * @return cm_info
     */
    public function get_cm_info(?int $user_id = null): cm_info {
        return cm_info::create($this->course_module->to_record(), $user_id ?? 0);
    }

}

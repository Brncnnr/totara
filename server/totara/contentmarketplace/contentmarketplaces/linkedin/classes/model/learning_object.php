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
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\model;

use Closure;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\collection;
use contentmarketplace_linkedin\api\v2\service\learning_asset\response\element;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\event\learning_object_updated;
use contentmarketplace_linkedin\learning_object\resolver;
use core\entity\course;
use core\entity\user;
use core\orm\collection as orm_collection;
use core\orm\entity\model;
use core\orm\query\builder;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_contentmarketplace\learning_object\text;

/**
 * A LinkedIn learning object that has been fetched and stored locally within Totara.
 *
 * Properties:
 * @property-read string            $urn
 * @property-read string            $title
 * @property-read string|null       $description
 * @property-read string|null       $description_include_html
 * @property-read string|null       $short_description
 * @property-read int               $last_updated_at
 * @property-read int               $published_at
 * @property-read string|null       $level
 * @property-read int|null          $time_to_complete
 * @property-read string|null       $web_launch_url
 * @property-read string|null       $sso_launch_url
 * @property-read string            $asset_type
 * @property-read string|null       $availability
 *
 * Summary provider properties:
 * @property-read string      $name      Alias for 'title'
 * @property-read string      $language  Alias for 'locale_language'
 * @property-read string|null $image_url Alias for 'primary_image_url'
 *
 * @property-read classification[]|orm_collection $classifications  Get the mapped classifications
 * @property-read classification[]|orm_collection $subjects         Get the mapped classifications
 *                                                                  type {@see constants::CLASSIFICATION_TYPE_SUBJECT}
 * @property-read course[]|orm_collection         $courses
 * @property-read string                          $display_level
 *
 * @package contentmarketplace_linkedin\model
 */
class learning_object extends model implements detailed_model {
    /**
     * @var learning_object_entity
     */
    protected $entity;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return learning_object_entity::class;
    }

    protected $entity_attribute_whitelist = [
        'id',
        'urn',
        'title',
        'description',
        'description_include_html',
        'short_description',
        'last_updated_at',
        'published_at',
        'level',
        'time_to_complete',
        'asset_type',
        'web_launch_url',
        'sso_launch_url',
        'availability'
    ];

    protected $model_accessor_whitelist = [
        'name',
        'language',
        'image_url',
        'classifications',
        'subjects',
        'courses',
        'display_level',
    ];

    /**
     * Get a learning object model from a URN.
     *
     * @param string $urn
     * @return static
     */
    public static function load_by_urn(string $urn): self {
        $entity = learning_object_entity::repository()
            ->where('urn', $urn)
            ->one(true);
        return static::load_by_entity($entity);
    }

    /**
     * Create a new learning object from an API response element.
     *
     * @param element $element
     * @return static
     */
    public static function create_from_element(element $element): self {
        $data = static::get_record_data_from_element($element);

        $entity = new learning_object_entity($data);
        $entity->save();

        return static::load_by_entity($entity);
    }

    /**
     * @param element $element
     * @return static
     */
    public function update_from_element(element $element): self {
        $old_entity = clone($this->entity);

        $data_record = static::get_record_data_from_element($element);
        $this->entity->set_attributes_from_record($data_record);
        $this->entity->save();

        (learning_object_updated::from_learning_object($this, $old_entity))->trigger();

        return $this;
    }

    /**
     * @return learning_object_entity
     */
    public function get_entity(): learning_object_entity {
        return $this->entity;
    }

    /**
     * Create many learning objects from an API response.
     *
     * @param collection $api_result
     */
    public static function create_bulk_from_result(collection $api_result): void {
        $elements = $api_result->get_elements();

        $to_insert = array_map(Closure::fromCallable([static::class, 'get_record_data_from_element']), $elements);

        builder::get_db()->insert_records_via_batch(learning_object_entity::TABLE, $to_insert);
    }

    /**
     * Convert an API response element into an insert-able learning object record.
     *
     * @param element $element
     * @return object
     */
    protected static function get_record_data_from_element(element $element): object {
        return (object) [
            'urn' => $element->get_urn(),
            'title' => $element->get_title_value(),
            'description' => $element->get_description_value(),
            'description_include_html' => $element->get_description_include_html(),
            'short_description' => $element->get_short_description_value(),
            'locale_language' => $element->get_title_locale()->get_lang(),
            'locale_country' => $element->get_title_locale()->get_country(),
            'last_updated_at' => $element->get_last_updated_at()->get_timestamp(),
            'published_at' => $element->get_published_at()->get_timestamp(),
            'retired_at' => $element->get_retired_at() ? $element->get_retired_at()->get_timestamp() : null,
            'level' => $element->get_level(),
            'primary_image_url' => $element->get_primary_image_url(),
            'time_to_complete' => $element->get_time_to_complete() ? $element->get_time_to_complete()->get() : null,
            'web_launch_url' => $element->get_web_launch_url(),
            'sso_launch_url' => $element->get_sso_launch_url(),
            'asset_type' => $element->get_type(),
            'availability' => $element->get_availability(),
        ];
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public static function get_marketplace_component(): string {
        return resolver::get_component();
    }

    /**
     * @return text|null
     */
    public function get_description(): ?text {
        if (empty($this->description_include_html)) {
            return null;
        }

        return new text(
            $this->description_include_html,
            FORMAT_HTML
        );
    }

    /**
     * @return string
     */
    public function get_language(): string {
        return $this->entity->locale_language;
    }

    /**
     * @return string|null
     */
    public function get_image_url(): ?string {
        return $this->entity->primary_image_url;
    }

    /**
     * @return string|null
     */
    public function get_web_launch_url(): ?string {
        return $this->entity->web_launch_url;
    }

    /**
     * @return orm_collection|classification[]
     */
    public function get_classifications(): orm_collection {
        return $this->entity->classifications->map_to(classification::class);
    }

    /**
     * @return orm_collection|classification[]
     */
    public function get_subjects(): orm_collection {
        return $this->entity->subjects->map_to(classification::class);
    }

    /**
     * @return orm_collection
     */
    public function get_courses(): orm_collection {
        global $CFG;
        if (!function_exists('totara_course_is_viewable')) {
            require_once("{$CFG->dirroot}/totara/core/totara.php");
        }

        return $this->entity->courses->filter(function (course $course) {
            return totara_course_is_viewable($course->to_record(), user::logged_in()->id);
        });
    }

    /**
     * @return string
     */
    public function get_display_level(): string {
        switch ($this->entity->level) {
            case constants::DIFFICULTY_LEVEL_BEGINNER:
                return get_string('course_difficulty_beginner', 'contentmarketplace_linkedin');
            case constants::DIFFICULTY_LEVEL_INTERMEDIATE:
                return get_string('course_difficulty_intermediate', 'contentmarketplace_linkedin');
            case constants::DIFFICULTY_LEVEL_ADVANCED :
                return get_string('course_difficulty_advanced', 'contentmarketplace_linkedin');
            default:
                return get_string('course_difficulty_general', 'contentmarketplace_linkedin');
        }
    }

    /**
     * @return learning_object
     */
    public function refresh(): learning_object {
        $this->entity->refresh();
        return $this;
    }
}
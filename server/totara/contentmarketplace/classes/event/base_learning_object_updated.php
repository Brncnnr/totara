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

namespace totara_contentmarketplace\event;

use coding_exception;
use context_system;
use core\event\base;
use core\orm\entity\entity;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;

/**
 * Class base_learning_object_updated
 */
abstract class base_learning_object_updated extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'u';
        $this->context = context_system::instance();
    }

    /**
     * @param detailed_model $learning_object
     * @param entity $entity
     * @return static
     */
    public static function from_learning_object(detailed_model $learning_object, entity $entity): self {
        $description = $learning_object->get_description();
        $data = [
            'objectid' => $learning_object->get_id(),
            'other' => [
                'name' => $learning_object->get_name(),
                'image' => $learning_object->get_image_url(),
                'marketplace_component' => $learning_object::get_marketplace_component(),
                'description' => is_null($description) ? '' : $description->get_raw_value(),
            ]
        ];

        /** @var base_learning_object_updated $event */
        $event = static::create($data);
        $event->data['other'][$event->get_extra_name_data_key()] = static::get_extra_name_value($entity) ?? '';
        $event->data['other'][$event->get_extra_image_data_key()] = static::get_extra_image_value($entity) ?? '';
        $event->data['other'][$event->get_extra_description_data_key()] = static::get_extra_description_value($entity) ?? '';

        return $event;
    }

    /**
     * Let subclass define prefix key for extra related data.
     *
     * @return string
     */
    abstract protected function get_extra_data_prefix_key(): string;

    /**
     * @param string $key
     * @return string
     */
    final public function get_extra_key(string $key): string {
        if (empty($this->get_extra_data_prefix_key())) {
            throw new coding_exception("The prefix key can not be empty");
        }

        return $this->get_extra_data_prefix_key() . $key;
    }

    /**
     * Get old name value
     *
     * @param entity $entity
     * @return string
     */
    abstract protected static function get_extra_name_value(entity $entity): ?string;

    /**
     * Get old image value
     *
     * @param entity $entity
     * @return string
     */
    abstract protected static function get_extra_image_value(entity $entity): ?string;

    /**
     * Get old description value
     *
     * @param entity $entity
     * @return string
     */
    abstract protected static function get_extra_description_value(entity $entity): ?string;

    /**
     * @return array|string[]
     */
    final public function get_default_data_key(): array {
         return ['name', 'image', 'description'];
    }

    /**
     * @param string $key
     * @return bool
     */
    final public function validate_data_key(string $key): bool {
        if (!empty($this->get_extra_data_prefix_key())) {
            $keys = $this->get_default_data_key();
            return in_array(
                $key,
                array_merge(
                    $keys,
                    [
                        $this->get_extra_image_data_key(),
                        $this->get_extra_description_data_key(),
                        $this->get_extra_name_data_key()
                    ]
                )
            );
        }

        return true;
    }

    /**
     * @return string
     */
    public function get_old_image(): string {
        return $this->get_image(true);
    }

    /**
     * @return string
     */
    public function get_new_image(): string {
        return $this->get_image();
    }

    /**
     * @param bool $has_prefix
     * @return string
     */
    public function get_image(bool $has_prefix = false): string {
        $other = $this->other;
        $image = $other['image'];
        if ($has_prefix) {
            $image = $other[$this->get_extra_image_data_key()];
        }
        return $image;
    }

    /**
     * @param bool $has_prefix
     * @return string
     */
    public function get_learning_object_name(bool $has_prefix = false): string {
        $other = $this->other;
        $name = $other['name'];
        if ($has_prefix) {
            $name = $other[$this->get_extra_name_data_key()];
        }
        return $name;
    }

    /**
     * @return string
     */
    public function get_marketplace_component(): string {
        $other = $this->other;
        return $other['marketplace_component'];
    }

    /**
     * @return string
     */
    final protected function get_extra_image_data_key(): ?string {
        return $this->get_extra_data_prefix_key() . 'image';
    }

    /**
     * @return string
     */
    final protected function get_extra_description_data_key(): ?string {
        return $this->get_extra_data_prefix_key() . 'description';
    }

    /**
     * @return string
     */
    final protected function get_extra_name_data_key(): ?string {
        return $this->get_extra_data_prefix_key() . 'name';
    }
}
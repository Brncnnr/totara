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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\model;

use coding_exception;
use contentmarketplace_goone\api;
use contentmarketplace_goone\entity\learning_object as learning_object_entity;
use core\orm\entity\entity;
use core\orm\entity\model;
use totara_contentmarketplace\learning_object\abstraction\metadata\detailed_model;
use totara_contentmarketplace\learning_object\text;

/**
 * A hybrid object that serves as a central place to obtain data about a GO1 object, with some data being stored in the DB, and
 * some being fetched from the GO1 API cache.
 *
 * Entity properties:
 * @property-read int $id
 * @property-read int $external_id
 *
 * Model properties (fetched from the API):
 * @property-read string $name
 * @property-read text $description
 * @property-read string $image_url
 * @property-read string $language
 *
 * @package contentmarketplace_goone\model
 */
class learning_object extends model implements detailed_model {

    /**
     * @var api|null Don't access this directly, use {@see get_api()}
     */
    private static $api;

    protected $entity_attribute_whitelist = [
        'id',
        'external_id',
    ];

    protected $model_accessor_whitelist = [
        'name',
        'description',
        'image_url',
        'language',
    ];

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return learning_object_entity::class;
    }

    /**
     * learning_object constructor.
     * @param entity $entity
     * @param api|null $api
     */
    public function __construct(entity $entity, ?api $api = null) {
        static::$api = static::$api ?? $api;
        parent::__construct($entity);
    }

    /**
     * Load a model instance from a Go1 learning object ID (external, not Totara's ID)
     *
     * @param int $learning_object_id
     * @param api|null $api Use existing Go1 API instance in order to fetch data from the cache
     * @param bool $create_if_does_not_exist Create a entry in the DB for it if it doesn't already exist?
     * @return static
     */
    public static function load_by_external_id(
        int $learning_object_id,
        api $api = null,
        bool $create_if_does_not_exist = true
    ): self {
        $entity = learning_object_entity::repository()
            ->where('external_id', $learning_object_id)
            ->one();

        if ($entity) {
            return new static($entity, $api);
        }

        if (!$create_if_does_not_exist) {
            $table = learning_object_entity::TABLE;
            throw new coding_exception("The Go1 learning object with ID $learning_object_id does not exist in the $table table.");
        }

        $entity = new learning_object_entity();
        $entity->external_id = $learning_object_id;
        $entity->save();

        return new static($entity, $api);
    }

    /**
     * @inheritDoc
     */
    public static function get_marketplace_component(): string {
        return 'contentmarketplace_goone';
    }

    /**
     * @inheritDoc
     */
    public static function get_marketplace_image_url(): string {
        global $OUTPUT;
        return $OUTPUT->image_url(
            'logo_small_transparent',
            'contentmarketplace_goone',
        );
    }

    /**
     * Get the preloaded API cache, or a new one if it hasn't been loaded yet.
     *
     * @return api
     */
    private static function get_api(): api {
        if (!isset(static::$api)) {
            static::$api = new api();
        }
        return static::$api;
    }

    /**
     * Fetch the Go1 learning object data from the API cache.
     *
     * @return object
     */
    private function get_data(): object {
        return static::get_api()->get_learning_object($this->external_id);
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return $this->get_data()->title;
    }

    /**
     * @inheritDoc
     */
    public function get_description(): text {
        return new text(
            $this->get_data()->description,
            FORMAT_HTML
        );
    }

    /**
     * @inheritDoc
     */
    public function get_image_url(): ?string {
        return $this->get_data()->image ?? null;
    }

    /**
     * @inheritDoc
     */
    public function get_language(): ?string {
        return $this->get_data()->language ?? null;
    }

}

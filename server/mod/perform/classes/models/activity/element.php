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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\activity;

use coding_exception;
use context;
use context_helper;
use core\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\models\activity\helpers\displays_responses;
use mod_perform\models\activity\helpers\child_element_manager;

/**
 * Class element
 *
 * Represents a question or other type of element which can be displayed to users within a performance activity.
 *
 * @property-read int $id ID
 * @property-read int $context_id
 * @property-read string $plugin_name name of the element plugin that controls this element
 * @property-read string $title a user-defined title to identify and describe this element
 * @property-read int $identifier_id used to match elements that share the same identifier
 * @property-read string $data specific configuration data for this type of element
 * @property-read bool $is_required used to check response required or optional
 * @property-read context $context
 * @property-read element_plugin $element_plugin
 * @property-read bool $is_respondable
 * @property-read string element_$identifier
 * @property-read element_identifier $element_identifier
 * @property-read int|null $parent
 * @property-read child_element_manager $child_element_manager
 * @property-read collection|element[] $children
 *
 * @package mod_perform\models\activity
 */
class element extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'context_id',
        'plugin_name',
        'title',
        'identifier_id',
        'is_required',
        'element_section',
        'parent',
        'sort_order',
    ];

    protected $model_accessor_whitelist = [
        'context',
        'element_plugin',
        'is_respondable',
        'displays_responses',
        'identifier',
        'data',
        'element_identifier',
        'parent_element',
        'children',
    ];

    /**
     * @var element_entity
     */
    protected $entity;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return element_entity::class;
    }

    /**
     * @param context $context
     * @param string $plugin_name
     * @param string $title
     * @param string $identifier
     * @param string|null $data
     * @param bool $is_required
     * @param int|null $parent
     * @param int|null $sort_order
     * @return static
     */
    public static function create(
        context $context,
        string $plugin_name,
        string $title,
        string $identifier = '',
        string $data = null,
        bool $is_required = null,
        ?int $parent = null,
        ?int $sort_order = null
    ): self {
        $entity = new element_entity();
        $entity->context_id = $context->id;
        $entity->plugin_name = $plugin_name;
        $entity->title = $title;
        $element_identifier = element_identifier::fetch_or_create_identifier($identifier);
        $entity->identifier_id = $element_identifier ? $element_identifier->id : null;
        $entity->data = $data;
        $entity->is_required  = $is_required;
        $entity->parent  = $parent;
        $entity->sort_order = $sort_order;
        self::clean($entity);
        self::validate($entity);

        return builder::get_db()->transaction(function () use ($entity) {
            $entity->save();
            $model = self::load_by_entity($entity);
            self::post_create($model);
            $entity->save();

            return static::load_by_id($model->id);
        });
    }

    /**
     * Get the element plugin that this element is based on
     *
     * @return element_plugin
     */
    public function get_element_plugin(): element_plugin {
        return element_plugin::load_by_plugin($this->entity->plugin_name);
    }

    /**
     * Can the user respond to this element.
     *
     * @return bool
     */
    public function get_is_respondable(): bool {
        return $this->get_element_plugin() instanceof respondable_element_plugin;
    }

    /**
     * Does this element display responses (not necessarily directly accept them though).
     *
     * @return bool
     */
    public function get_displays_responses(): bool {
        return $this->get_element_plugin() instanceof displays_responses;
    }

    /**
     * Is this element aggregatable?
     *
     * @return bool
     */
    public function get_is_aggregatable(): bool {
        return $this->get_element_plugin()->get_is_aggregatable();
    }

    /**
     * Get the context that this element belongs to
     *
     * @return context
     */
    public function get_context(): context {
        return context_helper::instance_by_id($this->entity->context_id);
    }

    /**
     * Get the identifier string for this element, or null if none.
     *
     * @return string|null
     * @throws coding_exception
     */
    public function get_identifier(): ?string {
        if (is_null($this->entity->element_identifier)) {
            return null;
        }
        $element_identifier = element_identifier::load_by_entity($this->entity->element_identifier);
        return $element_identifier ? $element_identifier->identifier : null;
    }

    /**
     * Get the identifier model for this element, or null if none.
     *
     * @return element_identifier
     * @throws coding_exception
     */
    public function get_element_identifier(): element_identifier {
        return element_identifier::load_by_entity($this->entity->element_identifier);
    }

    /**
     * Get the element data
     *
     * @return string|null
     */
    public function get_data(): ?string {
        $element_plugin = element_plugin::load_by_plugin($this->plugin_name);

        return $element_plugin->process_data($this->entity);
    }

    /**
     * Get child element manager.
     *
     * @return child_element_manager
     */
    public function get_child_element_manager(): child_element_manager {
        return new child_element_manager($this->entity);
    }

    /**
     * Get parent element
     *
     * @return element|null
     */
    public function get_parent_element(): ?element {
        $parent = $this->entity->parent_element;

        if ($parent) {
            return self::load_by_entity($parent);
        }
        return null;
    }

    /**
     * Get children elements.
     *
     * @return collection|null
     */
    public function get_children(): collection {
        return $this->get_child_element_manager()->get_children_elements();
    }

    /**
     * Delete an element.
     *
     * @return void
     */
    public function delete(): void {
        if ($this->element_plugin->get_child_element_config()->supports_child_elements) {
            foreach ($this->get_children() as $child_element) {
                $child_element->delete();
            }
        }
        $this->entity->delete();
    }

    /**
     * Update the context for this element
     *
     * An element is "owned" by the context it belongs to. Setting a new context effectively "moves" the element.
     *
     * @param context $context
     */
    public function update_context(context $context): void {
        $this->entity->context_id = $context->id;
        $this->entity->save();
    }

    /**
     * Update element sort_order.
     *
     * @param int $sort_order
     */
    public function update_sort_order(int $sort_order): void {
        if ($this->parent === null) {
            throw new coding_exception("Can not update sort order of element without a parent.");
        }
        $this->entity->sort_order = $sort_order;
        $this->entity->save();
    }

    /**
     * Update the standard properties that define this element
     *
     * @param string $title
     * @param string|null $data
     * @param bool $is_required
     * @param string $identifier
     */
    public function update_details(
        string $title,
        string $data = null,
        bool $is_required = null,
        string $identifier = ''
    ): void {
        $this->entity->title = $title;
        $this->entity->data = $data;
        $this->entity->is_required = $is_required;
        $element_identifier = element_identifier::fetch_or_create_identifier($identifier);
        $this->entity->identifier_id = $element_identifier ? $element_identifier->id : null;
        self::clean($this->entity);
        self::validate($this->entity);
        $this->entity->save();
        self::post_update($this);
    }

    /*
     * Update the raw element settings data.
     */
    public function update_data(string $data): element {
        $this->entity->data = $data;
        $this->entity->save();

        return $this;
    }

    /**
     * Change the internal element JSON data.
     * Note that this does not save the data, it only changes the value in memory.
     *
     * @param string $data
     */
    public function set_data(string $data): void {
        $this->entity->data = $data;
    }

    /**
     * Get the unprocessed data from the entity (data is not processed by element_plugin:process_data()).
     *
     * @return string|null
     */
    public function get_raw_data(): ?string {
        return $this->entity->data;
    }

    /**
     * Clear and save the raw element settings data.
     *
     * @return $this
     */
    public function clear_data(): self {
        $this->entity->data = null;
        $this->entity->save();

        return $this;
    }

    /**
     * Checks that the properties of an element entity are valid
     *
     * If validation fails, an exception is thrown.
     *
     * @param element_entity $entity
     * @throws coding_exception
     */
    public static function validate(element_entity $entity): void {
        $element_plugin = element_plugin::load_by_plugin($entity->plugin_name);
        $element_plugin->get_element_usage()->validate_element_usage($entity);
        $element_plugin->validate_element($entity);
    }

    /**
     * Sometimes an element needs to do some post creation stuff like cleanup or
     * store some files, this is the place to do just that.
     *
     * @param element $element
     */
    public static function post_create(element $element): void {
        element_plugin::load_by_plugin($element->plugin_name)->post_create($element);
    }

    /**
     * Sometimes an element needs to do some post update stuff like cleanup or
     * store some files, this is the place to do just that.
     *
     * @param element $element
     */
    public static function post_update(element $element): void {
        builder::get_db()->transaction(function () use ($element) {
            element_plugin::load_by_plugin($element->plugin_name)->post_update($element);
        });
    }

    /**
     * @deprecated since 14.0 - elements are designed to be reused more than once
     * @return section_element_entity
     */
    public function get_section_element(): section_element_entity {
        /** @var section_element_entity $entity */
        $entity = $this->entity->section_element()->get()->first();
        return $entity;
    }

    /**
     * Clean the data to make sure all invalid keys are removed
     *
     * @param element_entity $entity
     */
    protected static function clean(element_entity $entity): void {
        $element_plugin = element_plugin::load_by_plugin($entity->plugin_name);
        $element_plugin->clean_element($entity);
    }

}

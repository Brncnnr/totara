<?php
/*
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
use core\orm\entity\model;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\hook\pre_section_element_deleted;

/**
 * Class section_element
 *
 * The presence of an element within a section.
 *
 * @property-read int $id ID
 * @property-read int $section_id
 * @property-read int $element_id
 * @property-read int $sort_order
 * @property-read section $section
 * @property-read element $element
 *
 * @package mod_perform\models\activity
 */
class section_element extends model {

    protected $entity_attribute_whitelist = [
        'id',
        'section_id',
        'element_id',
        'sort_order',
    ];

    protected $model_accessor_whitelist = [
        'section',
        'element',
    ];

    /**
     * @var section_element_entity
     */
    protected $entity;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return section_element_entity::class;
    }

    /**
     * Create a new section element, by joining the section and element
     *
     * @param section $section
     * @param element $element
     * @param int $sort_order must be unique for the section, index starting at 1
     *
     * @return static
     */
    public static function create(section $section, element $element, int $sort_order): self {
        $entity = new section_element_entity();
        $entity->section_id = $section->id;
        $entity->element_id = $element->id;
        $entity->sort_order = $sort_order;
        $entity->save();

        return static::load_by_entity($entity);
    }

    /**
     * Get the section
     *
     * @return section
     */
    public function get_section(): section {
        $section = section::load_by_entity($this->entity->section);
        return $section;
    }

    /**
     * Get the element
     *
     * @return element
     */
    public function get_element(): element {
        $element = element::load_by_entity($this->entity->element);
        return $element;
    }

    /**
     * Update this section element to the specified location in the section
     *
     * Note that the section should be responsible for making sure that sort orders are managed correctly.
     * There is a unique index on the sort order, so make sure that the target position is vacant.
     *
     * @param int $sort_order must be unique for the section, index starting at 1
     */
    public function update_sort_order(int $sort_order) {
        // Dev note: We do not validate that sort order > 0 here - that is managed by the section.
        $this->entity->sort_order = $sort_order;
        $this->entity->save();
    }

    /**
     * Delete the section element link
     *
     * This does not automatically delete the element.
     */
    public function delete(): void {
        // check if section element can be deleted
        $hook = new pre_section_element_deleted($this->get_id());
        $hook->execute();

        $reasons = $hook->get_reasons();
        if (!empty($reasons)) {
            throw new coding_exception(array_shift($reasons)->get_description());
        }

        $this->entity->delete();
    }

    /**
     * Update this section element to point to a new section
     *
     * @param section $new_section
     * @param int|null $new_sort_order
     * @throws coding_exception
     */
    public function move_to_section(section $new_section, int $new_sort_order = null) {
        $this->entity->section_id = $new_section->id;
        $this->entity->sort_order = $new_sort_order ?? $new_section->get_section_element_manager()->get_highest_sort_order() + 1;
        $this->entity->save();

        $this->entity->load_relation('section');
    }

    /**
     * Get element data which related to this section element
     *
     * @return mixed
     */
    public function get_element_data() {
        $element_data = $this->get_element()->get_data();
        $decoded_element_data = json_decode($element_data, true);
        return $decoded_element_data;
    }

}

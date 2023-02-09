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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_perform\models\activity\helpers;

use coding_exception;
use core\orm\collection;
use core\orm\query\builder;
use mod_perform\models\activity\element;
use mod_perform\entity\activity\section;
use mod_perform\models\activity\section as section_model;
use mod_perform\models\activity\section_element;
use stdClass;

/**
 * This class handles managing the section elements in a section.
 *
 * @package mod_perform\models\activity\helpers
 */
class section_element_manager {

    private $section_entity;

    public function __construct(section $section_entity) {
        $this->section_entity = $section_entity;
    }

    /**
     * Get a collection of all section elements in this section, indexed and sorted by sort_order
     *
     * @return collection|section_element[]
     */
    public function get_section_elements(): collection {
        $section_element_models = [];

        foreach ($this->section_entity->section_elements as $section_element_entity) {
            if (isset($section_element_models[$section_element_entity->sort_order])) {
                throw new coding_exception('Section elements have invalid sort orders');
            }
            $section_element_models[$section_element_entity->sort_order] =
                section_element::load_by_entity($section_element_entity);
        }

        ksort($section_element_models);

        return new collection($section_element_models);
    }

    /**
     * Get a collection of all section elements that can accept responses.
     *
     * @return collection|section_element[]
     */
    public function get_respondable_section_elements(): collection {
        if ($this->section_entity->relation_loaded('respondable_section_elements')) {
            return $this->section_entity->respondable_section_elements->map_to(section_element::class);
        }

        return $this->get_section_elements()->filter(function (section_element $section_element) {
            return $section_element->element->get_is_respondable();
        });
    }

    /**
     * Get section elements summary
     *
     * @return stdClass
     */
    public function get_section_elements_summary(): stdClass {
        $other_element_count = 0;
        $optional_count = 0;
        $required_count = 0;

        $count_element = function (element $element) use (&$other_element_count, &$optional_count, &$required_count) {
            $is_required = $element->is_required;
            if (!$element->is_respondable) {
                $other_element_count++;
            } else {
                if ($is_required) {
                    $required_count++;
                } else {
                    $optional_count++;
                }
            }
        };

        foreach ($this->get_section_elements() as $section_element) {
            $count_element($section_element->element);

            $child_elements = $section_element
                ->get_element()
                ->get_child_element_manager()
                ->get_children_elements();

            foreach ($child_elements as $child_element) {
                $count_element($child_element);
            }
        }

        return (object)[
            'required_question_count' => $required_count,
            'optional_question_count' => $optional_count,
            'other_element_count'     => $other_element_count,
        ];
    }

    /**
     * Check if the sort orders on the section elements are valid and throw an exception if not
     *
     * @throws coding_exception when the ordering is not valid
     */
    private function validate_sort_orders(): void {
        $section_elements = $this->get_section_elements();

        // If there are no items then sorting can't be invalid.
        if ($section_elements->count() < 1) {
            return;
        }

        $sort_orders = array_unique($section_elements->pluck('sort_order'));

        if (count($sort_orders) != count($section_elements)) {
            throw new coding_exception('Section element sort orders are not unique!');
        }

        sort($sort_orders);

        if (reset($sort_orders) != 1 || end($sort_orders) != count($sort_orders)) {
            throw new coding_exception('Section element sort orders are not consecutive starting at 1!');
        }
    }

    /**
     * Add the given element to this section at a specified position.
     *
     * @param element $element
     * @param int|null $after_section_element_id
     *
     * @return section_element
     */
    public function add_element_after(element $element, ?int $after_section_element_id = null): section_element {
        if ($this->section_entity->deleted()) {
            throw new coding_exception('Section has been deleted, can not add section element');
        }
        return builder::get_db()->transaction(function () use ($element, $after_section_element_id) {
            $item_ordering = new item_ordering($this->get_section_elements()->all());
            $new_section_element_sort_order = $item_ordering->get_new_item_sort_order_after($after_section_element_id);
            $section_elements_to_reorder = $item_ordering->get_items_to_reorder($new_section_element_sort_order);
            $this->move_section_elements($section_elements_to_reorder, false);

            $section_element = section_element::create(
                section_model::load_by_entity($this->section_entity),
                $element,
                $new_section_element_sort_order
            );

            // Refresh the relation otherwise the elements are outdated
            $this->section_entity->load_relation('section_elements');
            $this->validate_sort_orders();

            return $section_element;
        });
    }

    /**
     * Reorder section element to after the specified section element.
     *
     * @param int $section_element_id
     * @param int|null $after_section_element_id
     *
     * @return void
     */
    public function reorder_section_element_to_after(int $section_element_id, ?int $after_section_element_id = null): void {
        if ($this->section_entity->deleted()) {
            throw new coding_exception('Section has been deleted, can not add section element');
        }

        if ($after_section_element_id) {
            $after_section_element = section_element::load_by_id($after_section_element_id);
            if ($this->section_entity->id != $after_section_element->section_id) {
                throw new coding_exception('Cannot move a section element that does not belong to this section');
            }
        }

        builder::get_db()->transaction(function () use ($section_element_id, $after_section_element_id) {
            $item_ordering = new item_ordering($this->get_section_elements()->all());
            $section_elements_to_reorder = $item_ordering->move_item_after($section_element_id, $after_section_element_id);
            $this->move_section_elements($section_elements_to_reorder);

            // Refresh the relation otherwise the elements are outdated
            $this->section_entity->load_relation('section_elements');
        });
    }

    /**
     * Remove the given section elements from this section
     *
     * Will automatically re-order all remaining section elements.
     *
     * @param section_element[] $remove_section_elements
     * @throws coding_exception|\Throwable
     */
    public function remove_section_elements(array $remove_section_elements): void {
        if ($this->section_entity->deleted()) {
            throw new coding_exception('Section has been deleted, can not remove section elements');
        }

        if (empty($remove_section_elements)) {
            return;
        }

        builder::get_db()->transaction(function () use ($remove_section_elements) {
            foreach ($remove_section_elements as $section_element) {
                if ($section_element->section_id != $this->section_entity->id) {
                    throw new coding_exception('Cannot delete a section element that does not belong to this section');
                }
                $section_element->delete();
            }

            // Refresh the relation otherwise the elements are outdated
            $this->section_entity->load_relation('section_elements');
            $this->reset_section_elements_order();
        });
    }

    /**
     * Resets the sort order for all section elements.
     *
     * @return void
     */
    private function reset_section_elements_order() {
        $section_elements = $this->get_section_elements();

        $i = 0;
        foreach ($section_elements as $section_element) {
            $i++;
            if ($section_element->sort_order != $i) {
                $section_element->update_sort_order($i);
            }
        }
    }

    /**
     * Move the specified set of section elements
     *
     * Will fail if the resulting sorting is not valid (all unique and sequential from 1).
     *
     * @param section_element[] $move_section_elements where $key is the new sort order and $value is the section element
     * @param bool $validate
     *
     * @throws coding_exception|\Throwable
     */
    public function move_section_elements(array $move_section_elements, $validate = true): void {
        if ($this->section_entity->deleted()) {
            throw new coding_exception('Section has been deleted, can not move section elements');
        }

        if (empty($move_section_elements)) {
            return;
        }

        builder::get_db()->transaction(function () use ($move_section_elements, $validate) {

            $section_element_ids = [];
            foreach ($move_section_elements as $sort_order => $section_element) {
                if ($section_element->section_id != $this->section_entity->id) {
                    throw new coding_exception('Cannot move a section element that does not belong to this section');
                }
                // Sort order has a unique key. The destination sort order might still be in use by a following
                // element, so we temporarily set the sort order to the negative of its final position.
                $section_element_ids[] = $section_element->id;
            }
            [$section_element_in_sql, $section_element_ids_params] = builder::get_db()->get_in_or_equal(
                $section_element_ids,
                SQL_PARAMS_NAMED
            );
            $sql = "            
                update {perform_section_element}
                set sort_order = -sort_order
                where id {$section_element_in_sql}
            ";
            builder::get_db()->execute($sql, $section_element_ids_params);

            foreach ($move_section_elements as $sort_order => $section_element) {
                // We move the section element back into the correct position, which must currently be vacant.
                $section_element->update_sort_order($sort_order);
            }

            if ($validate) {
                // Refresh the relation otherwise the elements are outdated
                $this->section_entity->load_relation('section_elements');
                (new item_ordering($this->get_section_elements()->all()))->validate_sort_orders();
            }
        });
    }

    /**
     * Get the highest sort order of the section element
     *
     * @return int
     * @throws coding_exception
     */
    public function get_highest_sort_order() {
        $last_section_element = $this->get_section_elements()->last();
        return $last_section_element ? $last_section_element->sort_order : 0;
    }
}
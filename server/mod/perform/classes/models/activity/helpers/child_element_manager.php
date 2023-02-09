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
use core\collection;
use core\orm\query\builder;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_plugin;

/**
 * This class handles managing the child elements of a perform element.
 *
 * @package mod_perform\models\activity\helpers
 */
class child_element_manager {

    private $parent_element_entity;

    public function __construct(element_entity $parent_element_entity) {
        $this->parent_element_entity = $parent_element_entity;
    }

    /**
     * Get children elements.
     *
     * @return collection|element[]|null
     */
    public function get_children_elements(): collection {
        /** @var collection $children_elements*/
        $children_elements = $this->parent_element_entity->children;

        if ($children_elements->count() > 0) {
            $children_elements = $children_elements->map_to(element::class);
        }

        return $children_elements;
    }

    /**
     * Create a child element after a specified sibling element.
     *
     * @param array $child_element_data
     * @param string $plugin_name
     * @param int|null $after_sibling_element_id
     *
     * @return element
     */
    public function create_child_element(
        array $child_element_data,
        string $plugin_name,
        ?int $after_sibling_element_id = null
    ): element {
        $parent_element_model = element::load_by_entity($this->parent_element_entity);

        if (!$parent_element_model->get_element_plugin()->get_child_element_config()->supports_child_elements) {
            throw new coding_exception("Element doesn't support child elements");
        }

        $element_plugin = element_plugin::load_by_plugin($plugin_name);
        if ($element_plugin->get_child_element_config()->supports_child_elements) {
            throw new coding_exception('Can not create an element that supports child elements as well');
        }

        if (!$element_plugin->get_element_usage()->is_compatible_child_element($parent_element_model->plugin_name, $parent_element_model->data)) {
            throw new coding_exception("$plugin_name element is not compatible with $parent_element_model->plugin_name");
        }

        return builder::get_db()->transaction(
            function () use ($child_element_data, $plugin_name, $parent_element_model, $after_sibling_element_id) {
                $child_elements = $this->get_children_elements()->all();
                $items_ordering = new item_ordering($child_elements);
                $new_element_sort_order = $items_ordering->get_new_item_sort_order_after($after_sibling_element_id);
                $sibling_elements_to_reorder = $items_ordering->get_items_to_reorder($new_element_sort_order);
                $this->update_child_elements_sort_order($sibling_elements_to_reorder);

                $new_child_element = element::create(
                    $parent_element_model->get_context(),
                    $plugin_name,
                    $child_element_data['title'],
                    $child_element_data['identifier'] ?? '',
                    $child_element_data['data'] ?? null,
                    $child_element_data['is_required'] ?? null,
                    $this->parent_element_entity->id,
                    $new_element_sort_order
                );

                $this->parent_element_entity->load_relation('children');
                (new item_ordering($this->get_children_elements()->all()))->validate_sort_orders();

                return $new_child_element;
            }
        );
    }

    /**
     * Reorder a child element with it's parent.
     * @param int $child_element_id
     * @param int|null $after_element_id
     *
     * @return void
     * @throws coding_exception
     */
    public function reorder_child_element_to_after(int $child_element_id, ?int $after_element_id = null): void {
        $parent_element_model = element::load_by_entity($this->parent_element_entity);
        if (!$parent_element_model->get_element_plugin()->get_child_element_config()->supports_child_elements) {
            throw new coding_exception("Element doesn't support child elements");
        }

        $element_siblings = $this->get_children_elements()->all(true);
        if (empty($element_siblings)) {
            throw new coding_exception("No element siblings to reorder with.");
        }

        if (!array_key_exists($child_element_id, $element_siblings)
            || ($after_element_id && !array_key_exists($after_element_id, $element_siblings))) {
            throw new coding_exception("Child elements to be reordered are not siblings");
        }

        builder::get_db()->transaction(function () use ($element_siblings, $child_element_id, $after_element_id) {
            $item_ordering = new item_ordering($element_siblings);
            $sibling_elements_to_reorder = $item_ordering->move_item_after($child_element_id, $after_element_id);

            $this->update_child_elements_sort_order($sibling_elements_to_reorder);
            $this->parent_element_entity->load_relation('children');
            (new item_ordering($this->get_children_elements()->all()))->validate_sort_orders();
        });
    }

    /**
     * Deletes a child element and recalculates sort_order of siblings.
     *
     * @param int $child_element_id
     */
    public function remove_child_element(int $child_element_id): void {
        $parent_element_model = element::load_by_entity($this->parent_element_entity);

        if (!$parent_element_model->get_element_plugin()->get_child_element_config()->supports_child_elements) {
            throw new coding_exception("Element doesn't support child elements");
        }

        builder::get_db()->transaction(function () use ($child_element_id) {
            $child_element_list = $this->get_children_elements();
            /** @var element $child_element*/
            $child_element = $child_element_list->find('id', $child_element_id);

            if ($child_element === null) {
                throw new coding_exception("Child element does not exist in parent.");
            }
            $child_element->delete();
            $this->parent_element_entity->load_relation('children');
            $this->reset_child_element_sort_orders($this->get_children_elements());
        });
    }

    /**
     * Resets the sort order for the child elements in a parent.
     *
     * @param collection $child_elements
     */
    private function reset_child_element_sort_orders(collection $child_elements) {
        $i = 0;

        foreach ($child_elements as $child_element) {
            $i++;

            if ((int)$child_element->sort_order !== $i) {
                $child_element->update_sort_order($i);
            }
        }
    }

    /**
     * Update selected child elements to specified sort orders.
     *
     * @param array|element[] $child_elements
     */
    private function update_child_elements_sort_order(array $child_elements) {
        if (empty($child_elements)) {
            return;
        }
        builder::get_db()->transaction(function () use ($child_elements) {

            $child_element_ids = [];
            foreach ($child_elements as $sort_order => $child_element) {
                if ((int)$this->parent_element_entity->id !== (int)$child_element->parent) {
                    throw new coding_exception('Can not move child elements that does not belong to same parent');
                }
                $child_element_ids[] = $child_element->id;
            }

            [$elements_in_sql, $elements_ids_params] = builder::get_db()->get_in_or_equal($child_element_ids, SQL_PARAMS_NAMED);
            $sql = "
                update {perform_element}
                set sort_order = -sort_order
                where id {$elements_in_sql}
            ";
            builder::get_db()->execute($sql, $elements_ids_params);

            foreach ($child_elements as $sort_order => $child_element) {
                $child_element->update_sort_order($sort_order);
            }
        });
    }
}

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

namespace mod_perform\data_providers\activity;

use core\orm\collection;
use mod_perform\models\activity\element_plugin as element_plugin_model;

/**
 * Class element_plugin
 * @package mod_perform
 */
class element_plugin {

    /**
     * @var collection
     */
    protected $items;

    /**
     * Fetch elements from the classes
     *
     * @return $this
     */
    public function fetch() {
        $this->fetch_elements();

        return $this;
    }

    /**
     * Actually fetch elements
     *
     * @return $this
     */
    protected function fetch_elements(): self {
        $this->items = element_plugin_model::get_element_plugins();

        // Only load enabled plugins
        $this->items = array_filter($this->items, function (element_plugin_model $plugin) {
            return $plugin->is_enabled();
        });

        usort( $this->items, function (element_plugin_model $a, element_plugin_model $b) {
            if ($a->get_sortorder() === $b->get_sortorder()) {
                return $a->get_name() <=> $b->get_name();
            }

            return $a->get_sortorder() <=> $b->get_sortorder();
        });

        return $this;
    }

    /**
     * Get items for the model
     *
     * @return collection|element_plugin_model[]
     */
    public function get() {
        return $this->items;
    }
}
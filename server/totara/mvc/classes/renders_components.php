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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_mvc
 */

namespace totara_mvc;

use totara_tui\output\component;

/**
 * This trait can be used to render front end components.
 *
 * @package totara_mvc
 */
trait renders_components {

    /**
     * Get a component rendered as a string.
     *
     * @param string $component_name
     * @param array $props
     * @return string
     */
    protected function get_rendered_component(string $component_name, array $props = []): string {
        return $this->render_component($this->create_component($component_name, $props));
    }

    /**
     * @param string $component_name
     * @param array $props
     * @return component
     */
    protected function create_component(string $component_name, array $props = []): component {
        return new component(
            $component_name,
            $props
        );
    }

    /**
     * @param component $component
     * @return string
     */
    protected function render_component(component $component): string {
        global $PAGE;

        return $PAGE->get_renderer('core')->render($component);
    }

}
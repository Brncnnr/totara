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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_mvc
 */

namespace totara_mvc;

use totara_tui\output\component;

/**
 * View specific for rendering a tui component.
 *
 * Example usage:
 * ```php
 * protected function action() {
 *     // do something
 *
 *     return tui_view::create('totara_core\MyExampleTuiComponent', $additional_props)
 *         ->set_title(...);
 * }
 * ```
 *
 * @package totara_mvc
 */
class tui_view extends view {

    /**
     * @param string $vue_component the full path of the tui component
     * @param array $props optional props to pass to the component
     */
    public function __construct(string $vue_component, $props = []) {
        if (empty($vue_component)) {
            throw new \coding_exception('You have to provide a valid vue component name');
        }
        parent::__construct($vue_component, $props);
    }

    /**
     * @inheritDoc
     */
    protected function prepare_output($output) {
        return new component($this->template, $output);
    }

    /**
     * Create a new instance of a tui_view
     *
     * @param string $vue_component full name of the tui component
     * @param array $props
     * @return static
     */
    public static function create(?string $vue_component, $props = []) {
        if (empty($vue_component)) {
            throw new \coding_exception('You have to provide a valid vue component name');
        }
        return parent::create($vue_component, $props);
    }

}

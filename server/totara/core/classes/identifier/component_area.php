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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\identifier;

/**
 * A data holder class for merging the component and area into the same
 * place. This class is for helping shorting the function(s) that require parameters such as
 * component and area together. With this class, those function(s) can shorten into just declaring
 * the need of component_area instance.
 */
class component_area {
    /**
     * @var string
     */
    private $component;

    /**
     * @var string
     */
    private $area;

    /**
     * component_area constructor.
     * @param string $component
     * @param string $area
     */
    public function __construct(string $component, string $area) {
        $this->component = $component;
        $this->area = $area;
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return string
     */
    public function get_area(): string {
        return $this->area;
    }
}
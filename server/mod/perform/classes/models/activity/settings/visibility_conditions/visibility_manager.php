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
 * @package mod_perform
 */

namespace mod_perform\models\activity\settings\visibility_conditions;

use coding_exception;
use core\collection;
use core_component;

/**
 * Class visibility_manager
 *
 * @package mod_perform\models\activity\settings\visibility_conditions
 */
class visibility_manager {
    /**
     * Class names of visibility options.
     *
     * @var collection|string[]
     */
    private $options;

    /**
     * visibility_manager constructor.
     */
    public function __construct() {
        $options = core_component::get_namespace_classes(
            'models\activity\settings\visibility_conditions',
            visibility_option::class,
            'mod_perform'
        );
        $this->options = new collection($options);
    }

    /**
     * Gets option with value.
     *
     * @param int $value
     *
     * @return visibility_option
     * @throws coding_exception
     */
    public function get_option_with_value(int $value): visibility_option {
        $option = $this->options->find(function ($option) use ($value) {
            return $option::VALUE === $value;
        });

        if (!$option) {
            throw new coding_exception('Invalid visibility condition');
        }

        return new $option();
    }

    /**
     * Get options.
     *
     * @return collection
     */
    public function get_options(): collection {
        return $this->options->map(function ($option) {
            return new $option();
        });
    }

    /**
     * Checks if visibility option with value exists.
     * @param int $value
     *
     * @return bool
     */
    public function has_option_with_value(int $value): bool {
        return $this->options->has(function($option) use ($value) {
            return $option::VALUE === $value;
        });
    }
}
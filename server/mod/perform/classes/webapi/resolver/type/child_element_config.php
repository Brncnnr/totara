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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_perform\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\child_element_config as child_element_config_formatter;
use mod_perform\models\activity\helpers\child_element_config as child_element_config_model;

class child_element_config extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $child_element_config, array $args, execution_context $ec) {
        if (!$child_element_config instanceof child_element_config_model) {
            throw new coding_exception('Expected child_element_config model');
        }

        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $context = $ec->has_relevant_context() ? $ec->get_relevant_context() : context_system::instance();
        $formatter = new child_element_config_formatter($child_element_config, $context);

        return $formatter->format($field, $format);
    }
}
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\activity\element_identifier as element_identifier_formatter;
use mod_perform\models\activity\element_identifier as element_identifier_model;


class element_identifier extends type_resolver {

    /**
     * @param string $field
     * @param element_identifier_model $element_identifier
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $element_identifier, array $args, execution_context $ec) {
        if (!$element_identifier instanceof element_identifier_model) {
            throw new \coding_exception('Expected element_identifier model');
        }
        $format = $args['format'] ?? format::FORMAT_HTML;
        $formatter = new element_identifier_formatter($element_identifier, $ec->get_relevant_context());

        return $formatter->format($field, $format);
    }
}
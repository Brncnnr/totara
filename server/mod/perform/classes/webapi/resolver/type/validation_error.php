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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\formatter\response\validation_error as validation_error_formatter;
use mod_perform\models\response\element_validation_error;

class validation_error extends type_resolver {

    /**
     * @param string $field
     * @param validation_error $validation_error
     * @param array $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $validation_error, array $args, execution_context $ec) {
        if (!$validation_error instanceof element_validation_error) {
            throw new \coding_exception('Expected element_validation_error');
        }

        $format = $args['format'] ?? format::FORMAT_HTML;
        $formatter = new validation_error_formatter($validation_error, $ec->get_relevant_context());
        return $formatter->format($field, $format);
    }
}
<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */
namespace totara_api\webapi\resolver\type;

use coding_exception;
use core\format;
use core\webapi\type_resolver;
use totara_api\formatter\client_formatter;
use totara_api\model\client as model;
use context_system;
use core\webapi\execution_context;

class client extends type_resolver {
    /**
     * @param string $field
     * @param model $client
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $client, array $args, execution_context $ec) {
        if (!($client instanceof model)) {
            throw new coding_exception('Expected client model');
        }

        $context = context_system::instance();

        $formatter = new client_formatter($client, $context);

        return $formatter->format($field, $args['format'] ?? format::FORMAT_PLAIN);
    }
}
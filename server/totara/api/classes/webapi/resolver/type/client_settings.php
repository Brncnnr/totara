<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\type;

use coding_exception;
use context_system;
use core\webapi\type_resolver;
use totara_api\formatter\client_settings_formatter;
use totara_api\model\client_settings as model;
use core\webapi\execution_context;

class client_settings extends type_resolver {
    /**
     * @param string $field
     * @param model $client_settings
     * @param array $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $client_settings, array $args, execution_context $ec) {
        if (!($client_settings instanceof model)) {
            throw new coding_exception('Expected client settings model');
        }

        $formatter = new client_settings_formatter($client_settings, context_system::instance());
        return $formatter->format($field, $args['format'] ?? null);
    }
}
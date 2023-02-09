<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\type_resolver;

/**
 * Simple type representing the status of the webapi
 */
class status extends type_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if ($field === 'timestamp') {
            $format = $args['format'] ?? null;
            $formatter = new date_field_formatter($format, \context_system::instance());
            return $formatter->format($source[$field]);
        }

        return $source[$field];
    }
}
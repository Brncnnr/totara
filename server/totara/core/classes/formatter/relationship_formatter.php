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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\formatter;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use totara_core\relationship\relationship;

/**
 * Format the relationship properties for GraphQL.
 *
 * @package totara_core\formatter
 */
class relationship_formatter extends entity_model_formatter {

    /** @var relationship */
    protected $object;

    protected function get_map(): array {
        return [
            'id' => null,
            'type' => null,
            'idnumber' => string_field_formatter::class,
            'name' => string_field_formatter::class,
            'name_plural' => string_field_formatter::class,
            'sort_order' => null,
            'created_at' => date_field_formatter::class,
            'type' => null,
        ];
    }

}

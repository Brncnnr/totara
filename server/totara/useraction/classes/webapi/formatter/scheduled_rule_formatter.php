<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\formatter;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\textarea_field_formatter;
use totara_useraction\filter\factory;

/**
 * Formatter for the scheduled_rule model
 */
class scheduled_rule_formatter extends entity_model_formatter {
    /**
     * Mapping of model fields to their relevant formatters.
     *
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'description' => textarea_field_formatter::class,
            'tenant_id' => null,
            'status' => function ($value): bool {
                return boolval($value);
            },
            'action' => null,
            'filters' => null,
            'created' => date_field_formatter::class,
            'updated' => date_field_formatter::class,
        ];
    }
}

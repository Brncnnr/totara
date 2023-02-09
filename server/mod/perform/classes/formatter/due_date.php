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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Maps the due date class into the GraphQL mod_perform_due_date type.
 */
class due_date extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'due_date' => date_field_formatter::class,
            'is_overdue' => null,
            'units_to_due_date' => null,
            'units_to_due_date_type' => string_field_formatter::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'due_date':
                return $this->object->get_due_date();

            case 'is_overdue':
                return $this->object->is_overdue();

            case 'units_to_due_date':
                return $this->object->get_interval_to_or_past_due_date_units();

            case 'units_to_due_date_type':
                return $this->object->get_interval_to_or_past_due_date_type();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}
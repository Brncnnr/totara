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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\formatter\application;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

/**
 * New application menu item formatter
 */
class new_application_menu_item extends formatter {
    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'assignment_id' => null, // Not formatted, because this is an internal key.
            'workflow_type' => string_field_formatter::class,
            'job_assignment' => string_field_formatter::class,
            'job_assignment_id' => null,
        ];
    }
}
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\formatter\application;

use coding_exception;
use core\webapi\formatter\formatter;

/**
 * Format application_interactor.
 */
final class application_interactor_formatter extends formatter {
    protected function get_map(): array {
        return [
            'can_view' => null,
            'can_clone' => null,
            'can_edit' => null,
            'can_delete' => null,
            'can_withdraw' => null,
            'can_approve' => null,
            'can_edit_without_invalidating' => null,
        ];
    }

    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }

    protected function get_field(string $field) {
        if ($this->has_field($field)) {
            return $this->object->{$field}();
        }
        throw new coding_exception("Undefined field '{$field}'");
    }
}

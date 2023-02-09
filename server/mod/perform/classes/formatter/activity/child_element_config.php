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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_perform\formatter\activity;

use core\webapi\formatter\formatter;

class child_element_config extends formatter {

    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'supports_child_elements' => null,
            'supports_repeating_child_elements' => null,
            'repeating_item_identifier' => null,
            'child_element_responses_identifier' => null,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function has_field(string $field): bool {
        return in_array($field, array_keys($this->get_map()));
    }

}
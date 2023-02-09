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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace totara_evidence\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use totara_customfield\field\field_data;

class evidence_item_field extends formatter {

    /** @var field_data */
    protected $object;

    /**
     * @inheritDoc
     */
    protected function get_map(): array {
        return [
            'label' => string_field_formatter::class,
            'type' => null,
            'content' => null,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function has_field(string $field): bool {
        if ($field === 'content') {
            return true;
        }
        return parent::has_field($field);
    }

    /**
     * @inheritDoc
     */
    protected function get_field(string $field) {
        if ($field === 'content') {
            return $this->object->extra_to_json();
        }
        if (method_exists($this->object, "get_{$field}")) {
            return $this->object->{"get_{$field}"}();
        }
        return parent::get_field($field);
    }

}

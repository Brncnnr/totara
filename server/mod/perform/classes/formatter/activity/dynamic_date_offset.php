<?php
/**
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\webapi\formatter\formatter;
use mod_perform\dates\date_offset;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the date_offset class into the GraphQL mod_perform_dynamic_date_offset type.
 */
class dynamic_date_offset extends formatter {

    /** @var date_offset */
    protected $object;

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'count' => null,
            'unit' => null,
            'direction' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        switch ($field) {
            case 'count':
            case 'unit':
            case 'direction':
                return true;
            default:
                return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'count':
                return $this->object->get_count();
            case 'unit':
                return $this->object->get_unit();
            case 'direction':
                return $this->object->get_direction();
            default:
                return null;
        }
    }

}
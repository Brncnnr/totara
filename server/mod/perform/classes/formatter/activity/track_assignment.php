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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\webapi\formatter\formatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the track_assignment class into the GraphQL mod_perform_track_assignment
 * type.
 */
class track_assignment extends formatter {
    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'track_id' => null,
            'type' => null,
            'group' => null
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'track_id':
            case 'group':
            case 'type':
                return $this->object->$field;
        }

        return parent::get_field($field);
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        $recognized = [
            'track_id',
            'type',
            'group'
        ];

        if (in_array($field, $recognized)) {
            return true;
        }

        return parent::has_field($field);
    }
}
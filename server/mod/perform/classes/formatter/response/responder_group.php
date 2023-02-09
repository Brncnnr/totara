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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\response;

use coding_exception;
use core\webapi\formatter\formatter;

/**
 * Class section_element
 *
 * @package mod_perform\formatter\response
 * @property \mod_perform\models\response\responder_group object
 */
class responder_group extends formatter {

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            'relationship_name' => null,
            'responses' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case 'relationship_name':
                return $this->object->get_relationship_name();
            case 'responses':
                return $this->object->get_responses();
        }

        throw new coding_exception('Unexpected field passed to formatter');
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        switch ($field) {
            case 'relationship_name':
            case 'responses':
                return true;
        }

        throw new coding_exception('Unexpected field passed to formatter');
    }
}
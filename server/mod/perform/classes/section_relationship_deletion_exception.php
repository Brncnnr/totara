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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform;

class section_relationship_deletion_exception extends \moodle_exception {

    /**
     * If we need more data in relation to this exception, store it in here
     *
     * @var mixed
     */
    protected $additional_data;

    /**
     * @param string $message
     * @param mixed $additional_data
     */
    public function __construct(string $message, $additional_data = null) {
        $this->additional_data = $additional_data;

        // Provide a normal string for exception
        parent::__construct('section_relationship_deletion_error', 'mod_perform', '', $message);
    }

    public function get_additional_data() {
        return $this->additional_data;
    }

}
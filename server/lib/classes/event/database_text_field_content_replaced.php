<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2023 onwards Totara Learning Solutions LTD
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
 * @author Ben Fesili <ben.fesili@totara.com>
 * @package core_event
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The database text field content replaced event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string search: The value being searched for.
 *      - string replace: The replacement value that replaces found search value.
 * }
 */
class database_text_field_content_replaced extends base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdatabasetextfieldcontentreplaced');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' replaced the string '" . $this->other['search'] . "' " .
            "with the string '" . $this->other['replace'] . "' in the database.";
    }

    /**
     * Custom validation.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['search'])) {
            throw new \coding_exception('The \'search\' value must be set in other.');
        }
        if (!isset($this->other['replace'])) {
            throw new \coding_exception('The \'replace\' value must be set in other.');
        }
    }
}

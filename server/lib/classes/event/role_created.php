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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Role created event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string name: name of role.
 *      - string shortname: shortname of role.
 *      - string description: role description.
 *      - string archetype: role archetype.
 * }
 *
 * @since Totara 13.0
 */
class role_created extends base {
    /**
     * Initialise event parameters.
     */
    protected function init() {
        $this->data['objecttable'] = 'role';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrolecreated', 'role');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $result = "The user with id '$this->userid' created the role with id '$this->objectid'";
        $info = [];
        if (isset($this->other['shortname'])) {
            $info[] = 'shortname: ' . $this->other['shortname'];
        }
        if (isset($this->other['name'])) {
            $info[] = 'name: ' . $this->other['name'];
        }
        if (isset($this->other['archetype'])) {
            $info[] = 'archetype: ' . ($this->other['archetype'] === '' ? 'none' : $this->other['archetype']);
        }
        if ($info) {
            $result .= ' (' . implode(', ', $info) . ')';
        }
        $result .= '.';
        return $result;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/roles/define.php', ['action' => 'view', 'roleid' => $this->objectid]);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['shortname'])) {
            throw new \coding_exception('The \'shortname\' value must be set in other.');
        }
        if (!isset($this->other['name'])) {
            throw new \coding_exception('The \'name\' value must be set in other.');
        }
        if (!isset($this->other['description'])) {
            throw new \coding_exception('The \'description\' value must be set in other.');
        }
        if (!isset($this->other['archetype'])) {
            throw new \coding_exception('The \'archetype\' value must be set in other.');
        }
    }
}

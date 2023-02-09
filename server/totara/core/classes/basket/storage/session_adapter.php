<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

namespace totara_core\basket\storage;

defined('MOODLE_INTERNAL') || die();

/**
 * DEPRECATED
 *
 * This adapter stores the data in the session, the parent key for the entries
 * can be specified on instantiation.
 *
 * @deprecated since Totara 13
 */
class session_adapter implements adapter {

    private $key;

    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * @deprecated since Totara 13
     */
    public function save($id, $data) {
        global $SESSION;

        if (!isset($SESSION->{$this->key})) {
            $SESSION->{$this->key} = [];
        }
        $SESSION->{$this->key}[$id] = $data;
    }

    /**
     * @deprecated since Totara 13
     */
    public function load($id) {
        global $SESSION;

        if (isset($SESSION->{$this->key}[$id])) {
            return $SESSION->{$this->key}[$id];
        }
        return null;
    }

    /**
     * @deprecated since Totara 13
     */
    public function delete($id) {
        global $SESSION;

        unset($SESSION->{$this->key}[$id]);
        if (empty($SESSION->{$this->key})) {
            unset($SESSION->{$this->key});
        }
    }
}
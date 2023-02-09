<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\hook;

use core_container\hook\base;

/**
 * A hook to allow the container/plugins to remove/add different kind of enrol plugins to list.
 * Most likely this will be used to remove enrol plugins from the list more than adding it.
 */
final class enrol_plugins extends base {
    /**
     * The list of enrol_plugin. A hash map of \enrol_plugin instance, where the name of the enrol is the key
     * and the instance itself is a value associates with its name.
     *
     * @var \enrol_plugin[]
     */
    private $enrols;

    /**
     * Passing an array of enrol plugins to the hooks, and the watcher will start filtering it out.
     * get_enrol_plugins constructor.
     *
     * @param int|\stdClass     $courseorid
     * @param \enrol_plugin[]   $enrolplugins
     */
    public function __construct($courseorid, array $enrolplugins) {
        parent::__construct($courseorid);
        $this->enrols = $enrolplugins;
    }

    /**
     * @return \enrol_plugin[]
     */
    public function get_enrol_plugins(): array {
        return $this->enrols;
    }

    /**
     * @param string $name
     * @return void
     */
    public function remove_enrol_plugins(string $name): void {
        if (!isset($this->enrols[$name])) {
            debugging("The enrol plugin '{$name}' is not existing", DEBUG_DEVELOPER);
            return;
        }

        unset($this->enrols[$name]);
    }

    /**
     * @param \enrol_plugin $enrol
     * @return void
     */
    public function add_enrol_plugin(\enrol_plugin $enrol): void {
        $name = $enrol->get_name();

        if (isset($this->enrols[$name])) {
            debugging("The enrol plugin '{$name}' has already been added", DEBUG_DEVELOPER);
            return;
        }

        $this->enrols[$name] = $enrol;
    }
}
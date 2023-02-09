<?php
/**
 * This file is part of Totara Learn
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totara.com>
 * @package core
 */

namespace core\hook;

/**
 * Hook for allowing custom checks after the require_login() call.
 *
 * Plugins may have defined an after_require_login() function in their lib.php
 * TODO - PLATFORM-117 to create a watcher to enable those functions via this hook
 */
class after_require_login extends \totara_core\hook\base {

    /**
     * mixed $courseorid id of the course or course object
     */
    public $courseorid;

    /**
     * bool $autologinguest
     */
    public $autologinguest;

    /**
     * object $cm - the course module object.
     */
    public $cm;

    /**
     * bool $setwantsurltome
     */
    public $setwantsurltome;

    /**
     * bool $preventredirect
     */
    public $preventredirect;

    /**
     * Construct with the same args and defaults that the require_login() function uses.
     *
     * @param mixed $courseorid - The id or object of the course
     * @param bool $autologinguest
     * @param object $cm - The course module object, handing this through prevents multiple fetches.
     * @param bool $setwantsurltome
     * @param bool $preventredirect
     */
    public function __construct($courseorid = null, $autologinguest = true, $cm = null, $setwantsurltome = true, $preventredirect = false) {
        $this->courseorid = $courseorid;
        $this->autologinguest = $autologinguest;
        $this->cm = $cm;
        $this->setwantsurltome = $setwantsurltome;
        $this->preventredirect = $preventredirect;
    }
}

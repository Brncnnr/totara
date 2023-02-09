<?php
/*
 * This file is part of Totara LMS
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\restriction;

use mod_facetoface\signup;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract restriction class
 *
 * Restrictions limit the actors that can perform an action.
 */
abstract class restriction {
    /**
     * @var signup instance to be tested for condition
     */
    protected $signup = null;

    /**
     * Constructor.
     *
     * @param signup $signup
     */
    public function __construct(signup $signup) {
        $this->signup = $signup;
    }
    /**
     * Is restriction met?
     * @return bool
     */
    abstract public function pass() : bool;

    /**
     * Description of this restriction
     * @return string
     */
    abstract public static function get_description() : string;

    /**
     * Return explanation why restriction was not met
     * @return array
     */
    abstract public function get_failure() : array;
}

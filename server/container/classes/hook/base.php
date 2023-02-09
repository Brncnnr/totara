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
 * @package core_container
 */
namespace core_container\hook;

use core_container\container;
use core_container\factory;
use totara_core\hook\base as master;

/**
 * A base hook that take either course's id or course record as parameter to be constructed.
 * Mainly this is for sharing the constructor between the hooks that are related to the container APIs.
 */
abstract class base extends master {
    /**
     * The id of {course} table.
     *
     * @var int|\stdClass
     */
    protected $courseorid;

    /**
     * @var array
     */
    protected $data;

    /**
     * base constructor.
     *
     * @param int|\stdClass $courseorid
     */
    public function __construct($courseorid) {
        if (!is_object($courseorid) && !is_numeric($courseorid)) {
            throw new \coding_exception("Only accept either \stdClass or an integer parameter");
        }

        $this->courseorid = $courseorid;
    }

    /**
     * @return container
     */
    public function get_container(): container {
        if (is_object($this->courseorid)) {
            return factory::from_record($this->courseorid);
        }

        return factory::from_id($this->courseorid);
    }
}
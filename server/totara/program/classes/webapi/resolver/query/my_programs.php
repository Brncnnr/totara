<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_core\advanced_feature;

/**
 * Query to return my programs.
 */
class my_programs extends query_resolver {

    /**
     * Returns the user's programs.
     *
     * @param array $args
     * @param execution_context $ec
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if (advanced_feature::is_disabled('programs')) {
            throw new \coding_exception('Programs have been disabled.');
        }

        // This method handles visibility checks internally.
        $progs = prog_get_all_programs($USER->id, '', '', '', false, false, true);
        $programs = [];
        foreach ($progs as $p) {
            $programs[] = new \program($p);
        }

        return $programs;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}


<?php
/*
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
* @author David Curry <david.curry@totaralearning.com>
* @package totara_program
*/

namespace totara_program\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;

class program_view extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/lib.php');

        // Get program module and program (provided by middleware)
        $programid = $args['program_id'] ?? null;

        if (empty($programid)) {
            throw new \invalid_parameter_exception('programid');
        }

        // Load the program and check its validity.
        try {
            $program = new \program($programid);
        } catch (\ProgramException $e) {
            throw new \invalid_parameter_exception('programid');
        }

        // Check whether the user can view the program.
        if (!$program->is_viewable()) {
            throw new \invalid_parameter_exception('programid');
        }

        $context = \context_program::instance($program->id);
        $ec->set_relevant_context($context);

        // Trigger event.
        $data = array('id' => $program->id, 'other' => array('section' => 'general'));
        \totara_program\event\program_viewed::create_from_data($data)->trigger();

        return true;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }
}

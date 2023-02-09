<?php
/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\webapi\resolver\middleware;

use core\webapi\middleware_group;

defined('MOODLE_INTERNAL') || die();

class test_group implements middleware_group {

    /**
     * @inheritDoc
     */
    public function get_middleware(): array {
        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_request_1.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_result_1.php';

        return [
            test_request_1::class,
            new test_result_1()
        ];
    }

}
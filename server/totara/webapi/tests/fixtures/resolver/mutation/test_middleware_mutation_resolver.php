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

namespace totara_webapi\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\mutation_resolver;
use totara_webapi\webapi\resolver\middleware\test_request_1;
use totara_webapi\webapi\resolver\middleware\test_request_2;
use totara_webapi\webapi\resolver\middleware\test_result_1;
use totara_webapi\webapi\resolver\middleware\test_result_2;

defined('MOODLE_INTERNAL') || die();

class test_middleware_mutation_resolver extends mutation_resolver {

    public static function resolve(array $args, execution_context $ec) {
        return ['mutation_success' => true, 'args' => $args];
    }

    public static function get_middleware(): array {
        global $CFG;
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_request_1.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_request_2.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_result_1.php';
        require_once $CFG->dirroot.'/totara/webapi/tests/fixtures/resolver/middleware/test_result_2.php';

        return [
            test_request_1::class,
            test_request_2::class,
            new test_result_1(),
            test_result_2::class
        ];
    }

}
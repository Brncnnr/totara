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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core
 * @category test
 */

use core\webapi\middleware\require_advanced_feature;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;

/**
 * @coversDefaultClass \core\webapi\middleware\require_advanced_feature
 *
 * @group core_webapi
 */
class core_webapi_middleware_require_advanced_feature_testcase extends advanced_testcase {
    /**
     * @covers ::handle
     */
    public function test_require(): void {
        $expected = 123;
        $next = function (payload $payload) use ($expected): result {
            return new result($expected);
        };

        $feature = 'appraisals';
        $require = new require_advanced_feature($feature);
        $ec = execution_context::create("dev");
        $payload = payload::create([], $ec);

        advanced_feature::enable($feature);
        $result = $require->handle($payload, $next);
        $this->assertEquals($expected, $result->get_data(), 'wrong result');

        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage($feature);
        advanced_feature::disable($feature);
        $require->handle($payload, $next);
    }
}

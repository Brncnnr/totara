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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_webapi
 */

use totara_webapi\client_aware_exception;

/**
 * @covers \totara_webapi\client_aware_exception
*/
class totara_webapi_client_aware_exception_test extends advanced_testcase {

    /**
     * Test creating client_aware exception with data returns with correct values.
     */
    public function test_new_client_aware_exception_with_data() {
        $exception = new client_aware_exception(
            new coding_exception('with data'),
            ['category' => 'unique_category']
        );
        $this->assertTrue($exception->isClientSafe());
        $this->assertEquals('unique_category', $exception->getCategory());
    }

    /**
     * Test creating client_aware exception without data returns with default values.
     */
    public function test_new_client_aware_exception_without_data() {
        $exception = new client_aware_exception(new coding_exception('no data'));
        $this->assertFalse($exception->isClientSafe());
        $this->assertEquals('internal', $exception->getCategory());
    }
}
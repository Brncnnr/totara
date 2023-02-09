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

use totara_webapi\client_aware_exception_helper;

/**
 * @covers \totara_webapi\client_aware_exception_helper
*/
class totara_webapi_client_aware_exception_collection_test extends advanced_testcase {

    /**
     * Test exception is registered.
     *
     * @dataProvider exceptions_provider
     */
    public function test_exception_is_registered(Throwable $exception) {
        $this->assertTrue(
            client_aware_exception_helper::exception_registered($exception)
        );
    }

    /**
     * Test data available for a registered exception.
     *
     * @dataProvider exceptions_provider
     */
    public function test_get_data_for_registered_exception(Throwable $exception, $category) {
        $data = [
            'category' => $category,
        ];
        $this->assertEqualsCanonicalizing(
            $data,
            client_aware_exception_helper::get_exception_data($exception)
        );
    }

    /**
     * Data provider for the test exceptions.
     *
     * @return array
     */
    public function exceptions_provider(): array {
        return [
            [new require_login_exception('require login'), 'require_login'],
            [new require_login_session_timeout_exception(), 'require_login'],
        ];
    }

    /**
     * test no data for an unregistered exception.
     */
    public function test_cannot_get_data_for_unregistered_exception() {
        $exception = new coding_exception('coding error');
        $this->assertNull(client_aware_exception_helper::get_exception_data($exception));
    }

    /**
     * Tests exception is not registered.
    */
    public function test_exception_is_not_registered() {
        $exception = new coding_exception('coding error');
        $this->assertFalse(
            client_aware_exception_helper::exception_registered($exception)
        );
    }
    /**
     * Test creating client_aware exception with unregistered exception returns with default values.
     */
    public function test_create_unregistered_client_aware_exception() {
        $exception = client_aware_exception_helper::create(new coding_exception('sample'));
        $this->assertFalse($exception->isClientSafe());
        $this->assertEquals('internal', $exception->getCategory());
    }

    /**
     * Test creating client_aware exception with a registered exception returns with correct values.
     */
    public function test_create_registered_client_aware_exception() {
        $exception = client_aware_exception_helper::create(new require_login_exception('sample'));
        $this->assertTrue($exception->isClientSafe());
        $this->assertEquals('require_login', $exception->getCategory());
    }
}
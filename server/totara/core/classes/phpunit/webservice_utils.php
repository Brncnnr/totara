<?php
/*
 * This file is part of Totara Learn
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\phpunit;

/**
 * This trait provides convencience method to support testing of webservices
 */
trait webservice_utils {

    /**
     * Calls the webservice with the parameters passed
     *
     * @param string $name
     * @param array $parameters
     * @param bool $ignore_sesskey defaults to true
     * @return array
     */
    protected function call_webservice_api(string $name, array $parameters, bool $ignore_sesskey = true): array {
        global $CFG, $USER;
        require_once($CFG->libdir . '/externallib.php');

        if ($ignore_sesskey) {
            $old_ignoresesskey = $USER->ignoresesskey ?? null;
            $USER->ignoresesskey = true;
        }
        $result = \external_api::call_external_function($name, $parameters);
        if ($ignore_sesskey) {
            if ($old_ignoresesskey === null) {
                unset($USER->ignoresesskey);
            } else {
                $USER->ignoresesskey = $old_ignoresesskey;
            }
        }
        return $result;
    }

    /**
     * Assert that provided webservice response contains an error
     *
     * @param array $response
     */
    protected function assert_webservice_error(array $response): void {
        $error = $response['error'] ?? null;
        if (!$error) {
            $this->fail('Expected webservice returned error status but actually returned a success status.');
        }
    }

    /**
     * Assert that provided webservice response contains success
     *
     * @param array $response
     */
    protected function assert_webservice_success(array $response): void {
        $error = $response['error'] ?? null;
        if ($error) {
            $exception = $response['exception'] ?? null;
            $error = var_export($exception, true);
            $this->fail("Expected webservice returned success status but actually returned the error '{$error}'.");
        }
    }

    /**
     * Assert that provided webservice response contains an exception message
     *
     * @param string $expected_message
     * @param array $response
     */
    protected function assert_webservice_has_exception_message(string $expected_message, array $response): void {
        $exception = $response['exception'] ?? null;
        if (!$exception) {
            $this->fail('Expected webservice returned an exception but none found.');
        }
        if (strpos($exception->message, $expected_message) === false) {
            $this->fail("Expected webservice returned message '{$expected_message}' but returned '{$exception->message}'.");
        }
    }

}
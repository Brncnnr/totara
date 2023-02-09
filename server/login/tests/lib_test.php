<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author matthias.bonk@totaralearning.com
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/login/lib.php');

class core_login_lib_testcase extends advanced_testcase {

    public function test_core_login_email_exists_multiple_times(): void {
        global $CFG;

        $CFG->allowaccountssameemail = 1;

        self::getDataGenerator()->create_user([
            'email' => 'unique.email@example.com',
        ]);
        self::getDataGenerator()->create_user([
            'email' => 'duplicate.email@example.com',
        ]);
        self::getDataGenerator()->create_user([
            'email' => 'DupLiCATE.email@example.com',
        ]);

        self::assertTrue(core_login_email_exists_multiple_times('duplicate.email@example.com'));
        self::assertTrue(core_login_email_exists_multiple_times('DUPLICATE.EMAIL@EXAMPLE.COM'));
        self::assertFalse(core_login_email_exists_multiple_times('unique.email@example.com'));
        self::assertFalse(core_login_email_exists_multiple_times('nonexistent.email@example.com'));
        self::assertFalse(core_login_email_exists_multiple_times(''));
        self::assertFalse(core_login_email_exists_multiple_times('   '));
    }

    /**
     * Data provider for \core_login_lib_testcase::test_core_login_validate_forgot_password_data().
     */
    public function forgot_password_data_provider() {
        return [
            'Both username and password supplied' => [
                [
                    'username' => 's1',
                    'email' => 's1@example.com'
                ],
                [
                    'username' => get_string('usernameoremail'),
                    'email' => get_string('usernameoremail'),
                ]
            ],
            'Valid username' => [
                ['username' => 's1']
            ],
            'Valid username, different case' => [
                ['username' => 'S1']
            ],
            'Valid username, different case, username protection off' => [
                ['username' => 'S1'],
                [],
                ['protectusernames' => 0]
            ],
            'Non-existent username' => [
                ['username' => 's2'],
            ],
            'Non-existing username, username protection off' => [
                ['username' => 's2'],
                ['username' => get_string('usernamenotfound')],
                ['protectusernames' => 0]
            ],
            'Valid username, unconfirmed username' => [
                ['username' => 's1'],
                ['email' => get_string('confirmednot')],
                ['confirmed' => 0]
            ],
            'Invalid email' => [
                ['email' => 's1-example.com'],
                ['email' => get_string('invalidemail')]
            ],
            'Multiple accounts with the same email' => [
                ['email' => 's1@example.com'],
                ['email' => get_string('forgottenduplicate')],
                ['allowaccountssameemail' => 1]
            ],
            'Non-existent email, username protection on' => [
                ['email' => 's2@example.com']
            ],
            'Non-existent email, username protection off' => [
                ['email' => 's2@example.com'],
                ['email' => get_string('emailnotfound')],
                ['protectusernames' => 0]
            ],
            'Valid email' => [
                ['email' => 's1@example.com']
            ],
            'Valid email, different case' => [
                ['email' => 'S1@EXAMPLE.COM']
            ],
            'Valid email, unconfirmed user' => [
                ['email' => 's1@example.com'],
                ['email' => get_string('confirmednot')],
                ['confirmed' => 0]
            ],
        ];
    }

    /**
     * Test for core_login_validate_forgot_password_data().
     *
     * @dataProvider forgot_password_data_provider
     * @param array $data Key-value array containing username and email data.
     * @param array $errors Key-value array containing error messages for the username and email fields.
     * @param array $options Options for $CFG->protectusernames, $CFG->allowaccountssameemail and $user->confirmed.
     */
    public function test_core_login_validate_forgot_password_data($data, $errors = [], $options = []) {
        $this->resetAfterTest();

        // Set config settings we need for our environment.
        $protectusernames = $options['protectusernames'] ?? 1;
        set_config('protectusernames', $protectusernames);

        $allowaccountssameemail = $options['allowaccountssameemail'] ?? 0;
        set_config('allowaccountssameemail', $allowaccountssameemail);

        // Generate the user data.
        $generator = $this->getDataGenerator();
        $userdata = [
            'username' => 's1',
            'email' => 's1@example.com',
            'confirmed' => $options['confirmed'] ?? 1
        ];
        $generator->create_user($userdata);

        if ($allowaccountssameemail) {
            // Create another user with the same email address.
            $generator->create_user(['email' => 's1@example.com']);
        }

        // Validate the data.
        $validationerrors = core_login_validate_forgot_password_data($data);

        // Check validation errors for the username field.
        if (isset($errors['username'])) {
            // If we expect and error for the username field, confirm that it's set.
            $this->assertArrayHasKey('username', $validationerrors);
            // And the actual validation error is equal to the expected validation error.
            $this->assertEquals($errors['username'], $validationerrors['username']);
        } else {
            // If we don't expect that there's a validation for the username field, confirm that it's not set.
            $this->assertArrayNotHasKey('username', $validationerrors);
        }

        // Check validation errors for the email field.
        if (isset($errors['email'])) {
            // If we expect and error for the email field, confirm that it's set.
            $this->assertArrayHasKey('email', $validationerrors);
            // And the actual validation error is equal to the expected validation error.
            $this->assertEquals($errors['email'], $validationerrors['email']);
        } else {
            // If we don't expect that there's a validation for the email field, confirm that it's not set.
            $this->assertArrayNotHasKey('email', $validationerrors);
        }
    }
}

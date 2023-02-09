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
require_once($CFG->dirroot . '/login/forgot_password_form.php');

class core_login_forgot_password_form_testcase extends advanced_testcase {

    public function test_protect_usernames_for_unconfirmed_user(): void {
        global $CFG;

        self::getDataGenerator()->create_user([
            'auth' => 'email',
            'email' => 'selfreg@example.com',
            'username' => 'selfreg',
            'confirmed' => 0,
        ]);
        $form = new login_forgot_password_form('', []);

        // protectusernames off: Info about unconfirmed account can be displayed.
        $CFG->protectusernames = 0;
        $errors = $form->validation(['email' => 'selfreg@example.com'], []);
        self::assertCount(1, $errors);
        self::assertEquals('Your registration has not yet been confirmed!', $errors['email']);

        $errors = $form->validation(['username' => 'selfreg'], []);
        self::assertCount(1, $errors);
        self::assertEquals('Your registration has not yet been confirmed!', $errors['username']);

        $errors = $form->validation(['username' => 'selfreg', 'email' => 'selfreg@example.com'], []);
        self::assertCount(2, $errors);
        self::assertEquals('Enter either username or email address', $errors['email']);
        self::assertEquals('Enter either username or email address', $errors['username']);

        // protectusernames on: Info about unconfirmed account must be suppressed.
        $CFG->protectusernames = 1;
        $errors = $form->validation(['email' => 'selfreg@example.com'], []);
        self::assertEmpty($errors);

        $errors = $form->validation(['username' => 'selfreg'], []);
        self::assertEmpty($errors);

        $errors = $form->validation(['username' => 'selfreg', 'email' => 'selfreg@example.com'], []);
        self::assertCount(2, $errors);
        self::assertEquals('Enter either username or email address', $errors['email']);
        self::assertEquals('Enter either username or email address', $errors['username']);
    }

    public function test_protect_usernames_for_unconfirmed_user_duplicate_emails(): void {
        global $CFG;

        self::getDataGenerator()->create_user([
            'auth' => 'email',
            'email' => 'selfreg@example.com',
            'username' => 'selfreg',
            'confirmed' => 0,
        ]);

        // Add another account with the same email.
        $CFG->allowaccountssameemail = 1;
        self::getDataGenerator()->create_user([
            'email' => 'selfreg@example.com',
        ]);

        $form = new login_forgot_password_form('', []);
        $CFG->protectusernames = 0;
        $errors = $form->validation(['email' => 'selfreg@example.com'], []);
        self::assertCount(1, $errors);
        self::assertEquals('The email address is shared by several accounts, please enter username instead', $errors['email']);

        $CFG->protectusernames = 1;
        $errors = $form->validation(['email' => 'selfreg@example.com'], []);
        self::assertEmpty($errors);
    }
}

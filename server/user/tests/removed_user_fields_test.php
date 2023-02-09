<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_user
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test for fields removed from "ttr_user" table
 */
class core_user_removed_user_fields_testcase extends advanced_testcase {
    public function test_core_user_removed_fields() {
        $removedfields = core_user::REMOVED_FIELDS;
        $this->assertIsArray($removedfields);
        $this->assertArrayHasKey('icq', $removedfields);
        $this->assertArrayHasKey('yahoo', $removedfields);
        $this->assertArrayHasKey('aim', $removedfields);
        $this->assertArrayHasKey('msn', $removedfields);
        $this->assertCount(4, $removedfields);
    }

    public function test_user_columns() {
        global $DB;

        $removedfields = core_user::REMOVED_FIELDS;

        $columns = $DB->get_columns('user');
        foreach ($columns as $column => $unused) {
            $this->assertArrayNotHasKey($column, $removedfields);
        }
    }

    public function test_user_get_default_fields() {
        global $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        $removedfields = core_user::REMOVED_FIELDS;

        $fields = user_get_default_fields();
        foreach ($fields as $field) {
            $this->assertArrayNotHasKey($field, $removedfields);
        }
    }

    public function test_user_create_user() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $this->getDataGenerator()->create_user(['username' => 'test1']);

        $newuser = clone($user);
        $newuser->username = 'test2';
        unset($newuser->id);
        $newuser->id = user_create_user($newuser, false, false);
        $this->assertDebuggingNotCalled();

        foreach (core_user::REMOVED_FIELDS as $field => $unused) {
            $newuser = clone($user);
            $newuser->username = $field;
            unset($newuser->id);
            $newuser->{$field} = 'abc';
            $newuser->id = user_create_user($newuser, false, false);
            $this->assertDebuggingCalled("User field '{$field}' is not avaialble any more, use custom user profile field instead");
            $this->assertTrue($DB->record_exists('user', ['id' => $newuser->id]));
        }
    }

    public function test_user_update_user() {
        global $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        $newuser = $this->getDataGenerator()->create_user(['username' => 'test1']);

        foreach (core_user::REMOVED_FIELDS as $field => $unused) {
            $newuser->{$field} = 'abc';
            user_update_user($newuser, false, false);
            $this->assertDebuggingCalled("User field '{$field}' is not avaialble any more, use custom user profile field instead");
        }
    }

    public function test_get_user_field_name() {
        $removedfields = core_user::REMOVED_FIELDS;

        foreach ($removedfields as $field => $name) {
            $this->assertSame("Invalid user field: {$field}", get_user_field_name($field));
        }
    }
}


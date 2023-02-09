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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core_role
 */

use core_role\hook\discover_undeletable_roles;

class discover_undeletable_roles_testcase extends advanced_testcase {

    public function test_discover_undeletable_roles() {
        global $DB;

        // Set up an environemnt for testing.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);

        $course1 = $this->getDataGenerator()->create_course();
        $context1 = context_course::instance($course1->id);
        $user1 = $this->getDataGenerator()->create_user();

        $this->assertEquals(1, $DB->count_records('enrol', ['enrol' => 'self']));
        $instance1 = $DB->get_record('enrol', ['courseid' => $course1->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $this->assertEquals($studentrole->id, $instance1->roleid);

        $result = $this->getDataGenerator()->enrol_user($user1->id, $course1->id, null, 'self');
        $this->assertTrue($result);
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $instance1->id, 'userid' => $user1->id]));
        $this->assertTrue($DB->record_exists('role_assignments', ['contextid' => $context1->id, 'userid' => $user1->id, 'roleid' => $studentrole->id]));

        // In normal state, the student role should not be deletable.
        $protected_roles = core_role_user_policies::get_roles();
        $discover_undeletable_roles = new discover_undeletable_roles($protected_roles);
        $discover_undeletable_roles->execute();
        $this->assertTrue($discover_undeletable_roles->is_role_undeletable($studentrole->id));

        // When we drop student role out of the list of protected species, it should not be deletable because we have
        // a self-enrolled student.
        unset($protected_roles[$studentrole->id]);
        $discover_undeletable_roles = new discover_undeletable_roles($protected_roles);
        $discover_undeletable_roles->execute();
        $this->assertTrue($discover_undeletable_roles->is_role_undeletable($studentrole->id));

        // When we drop that enrolment, it should not be deletable because the course still has that assigned as a default
        // role when enrolment happens.
        $DB->delete_records_select('role_assignments', 'userid = ?', [$user1->id]);
        $this->assertTrue($discover_undeletable_roles->is_role_undeletable($studentrole->id));

        // When we remove enrolment types (or change the default roleid), then the student role should become deletable.
        $DB->delete_records_select('enrol', 'roleid = ?', [$studentrole->id]);
        $this->assertFalse($discover_undeletable_roles->is_role_undeletable($studentrole->id));
    }
}

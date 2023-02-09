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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_course\local\archive_progress_helper\output\navigation\current_user;
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\output\navigation\completed_users
*/
class archive_progress_helper_output_navigation_current_users_testcase extends testcase {


    /**
     * @var navigation_node
     */
    private $node;

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var stdClass
     */
    private $user;

    protected function setUp(): void {
        global $PAGE, $SITE;

        $PAGE->set_url('/');
        $PAGE->set_course($SITE);
        $course = $this->getDataGenerator()->create_course();
        $course->enablecompletion = 1;

        $this->course = $course;
        $this->node = new navigation_node('test node');
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
    }

    protected function tearDown(): void {
        $this->course = null;
        $this->node = null;
        $this->user = null;
    }

    public function test_add_node_without_capability() {
        $completed_users_navigation = new current_user($this->course);
        $completed_users_navigation->add_node($this->node);
        $completed_users_node = $this->node->children->get(0, navigation_node::TYPE_SETTING);
        $this->assertEmpty($completed_users_node);
    }

    public function test_add_node_with_capability() {
        $this->grant_capability();
        $current_user_navigation = new current_user($this->course);
        $current_user_navigation->add_node($this->node);
        $current_user_node = $this->node->children->get(0, navigation_node::TYPE_SETTING);

        $this->assertEquals('Reset this course', $current_user_node->text);
        $this->assertStringContainsString('course/archivecompletions.php', $current_user_node->action->out());
    }

    /**
     * Allow user the capability to archive all users progress.
     */
    private function grant_capability() {
        $roleid = $this->getDataGenerator()->create_role();
        $context = context_course::instance($this->course->id);
        role_change_permission($roleid, $context, 'totara/core:archivemycourseprogress', CAP_ALLOW);
        role_assign($roleid, $this->user->id, $context);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');
    }
}
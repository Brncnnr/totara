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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

use core_phpunit\testcase;

global $CFG;
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_self.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_date.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_duration.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_grade.php');
require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');
require_once($CFG->dirroot.'/course/completion_form.php');

class core_course_completion_form_testcase extends testcase {

    /**
     * Confirm that we only get categories/courses back that user can access.
     */
    public function test_hidden_categories() {
        global $DB;

        $gen = $this->getDataGenerator();
        $completion_generator = $gen->get_plugin_generator('core_completion');

        // Create user.
        $user = $gen->create_user();

        // Create 3 course categories (2 hidden).
        $course_cat1 = $gen->create_category(['name' => 'course_cat_1', 'visible' => 1]);
        $course_cat2 = $gen->create_category(['name' => 'course_cat_2', 'visible' => 0]);
        $course_cat3 = $gen->create_category(['name' => 'course_cat_3', 'visible' => 0]);

        // Get context of course category 2.
        $context = context_coursecat::instance($course_cat2->id);

        // Set user as course creator on hidden category 2.
        $role_course_creator = $DB->get_record('role', array('shortname' => 'coursecreator'));
        role_assign($role_course_creator->id, $user->id, $context);

        // Create a course in each category.
        $course_cat1c1 = $gen->create_course(['category' => $course_cat1->id]);
        $course_cat2c1 = $gen->create_course(['category' => $course_cat2->id]);
        $course_cat3c1 = $gen->create_course(['category' => $course_cat3->id]);

        // Enable completion tracking.
        $completion_generator->enable_completion_tracking($course_cat1c1);
        $completion_generator->enable_completion_tracking($course_cat2c1);
        $completion_generator->enable_completion_tracking($course_cat3c1);

        // Add a module to each course.
        $book1 = $this->getDataGenerator()->create_module('book', array('course' => $course_cat1c1->id));
        $book2 = $this->getDataGenerator()->create_module('book', array('course' => $course_cat2c1->id));
        $book3 = $this->getDataGenerator()->create_module('book', array('course' => $course_cat3c1->id));

        // Set module completion criteria.
        $completion_generator->set_activity_completion($course_cat1c1->id, [$book1]);
        $completion_generator->set_activity_completion($course_cat2c1->id, [$book2]);
        $completion_generator->set_activity_completion($course_cat3c1->id, [$book3]);

        // Set current user.
        $this->setUser($user);

        // Form unlocked override
        $course = $course_cat1c1;
        $unlockdelete = false;
        $unlockonly = false;

        // Get course completion form data.
        $form = new course_completion_form(
            'completion.php?id='.$course_cat1c1->id,
            compact(
                'course',
                'unlockdelete',
                'unlockonly'
            )
        );
        $mform = $form->_form;
        $element = $mform->getElement('criteria_course_value');

        // There should be 1 option.
        $expected = [[
            'text' => 'course_cat_2 / Test course 2',
            'attr' => [
                'value' => intval($course_cat2c1->id),
                'title' => 'course_cat_2 / Test course 2'
            ],
        ]];
        $this->assertCount(1, $element->_options);
        $this->assertEqualsCanonicalizing($expected, $element->_options);
    }

}
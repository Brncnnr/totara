<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */

use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_contentmarketplace\interactor\content_marketplace_interactor;
use mod_contentmarketplace\testing\generator;
use mod_contentmarketplace\model\content_marketplace;

/**
 * @group totara_contentmarketplace
 */
class content_marketplace_content_marketplace_interactor_testcase extends testcase {
    /**
     * @return void
     */
    public function test_check_can_view_on_student(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $mod_generator = generator::instance();
        $cm = $mod_generator->create_instance([
            'course' => $course_record->id,
            'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
        ]);

        $user = $generator->create_user();
        $model = content_marketplace::from_course_module_id($cm->cmid);

        $interactor = new content_marketplace_interactor($model, $user->id);
        self::assertFalse($interactor->can_view());

        $generator->enrol_user($user->id, $course_record->id);
        self::assertTrue($interactor->can_view());
    }

    /**
     * @return void
     */
    public function test_check_can_view_on_guest(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $mod_generator = generator::instance();
        $cm = $mod_generator->create_instance([
            'course' => $course_record->id,
            'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
        ]);

        self::setGuestUser();
        $model = content_marketplace::from_course_module_id($cm->cmid);
        $interactor = new content_marketplace_interactor($model);

        // Guest user is not able to see the content marketplace,
        // because guest user is not able to access the course.
        self::assertFalse($interactor->can_view());

        //Enable the guest enrol plugin for the course.
        $db = builder::get_db();
        $enrol_instance = $db->get_record(
            enrol::TABLE,
            ['enrol' => 'guest', 'courseid' => $course_record->id],
            '*',
            MUST_EXIST
        );

        $new_enrol_instance = new stdClass();
        $new_enrol_instance->status = ENROL_INSTANCE_ENABLED;

        $plugin = enrol_get_plugin('guest');
        $plugin->update_instance($enrol_instance, $new_enrol_instance);

        // With require_login(), guest user is auto enrolled to course via guest enroll.
        require_login($course_record->id, true, null, false, true);

        // Then guest user is able to view the activity, because guest user is able
        // to access the course.
        self::assertTrue($interactor->can_view());
    }

    /**
     * @return void
     */
    public function test_check_can_launch_on_student(): void {
        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $mod_generator = generator::instance();
        $cm = $mod_generator->create_instance([
            'course' => $course_record->id,
            'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
        ]);

        $user = $generator->create_user();
        $model = content_marketplace::from_course_module_id($cm->cmid);

        $interactor = new content_marketplace_interactor($model, $user->id);
        self::assertFalse($interactor->can_launch());

        $generator->enrol_user($user->id, $course_record->id);
        self::assertTrue($interactor->can_launch());
    }

    /**
     * @return void
     */
    public function test_check_can_launch_on_guest(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course_record = $generator->create_course();

        $mod_generator = generator::instance();
        $cm = $mod_generator->create_instance([
            'course' => $course_record->id,
            'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
        ]);

        self::setGuestUser();
        $model = content_marketplace::from_course_module_id($cm->cmid);
        $interactor = new content_marketplace_interactor($model);

        // Guest user is not able to see the content marketplace, because guest
        // user cannot access to the course.
        self::assertFalse($interactor->can_view());
        self::assertFalse($interactor->can_launch());

        //Enable the guest enrol plugin for the course.
        $db = builder::get_db();
        $enrol_instance = $db->get_record(
            enrol::TABLE,
            ['enrol' => 'guest', 'courseid' => $course_record->id],
            '*',
            MUST_EXIST
        );

        $new_enrol_instance = new stdClass();
        $new_enrol_instance->status = ENROL_INSTANCE_ENABLED;

        $plugin = enrol_get_plugin('guest');
        $plugin->update_instance($enrol_instance, $new_enrol_instance);

        // Use require login to allow guest user access the course automatically.
        require_login($course_record->id, true, null, false, true);

        // Guest user is able to view the course, but not able to launch.
        self::assertTrue($interactor->can_view());
        self::assertFalse($interactor->can_launch());
    }
}
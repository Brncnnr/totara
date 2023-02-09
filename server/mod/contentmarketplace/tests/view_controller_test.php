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

use totara_contentmarketplace\testing\helper;
use core\entity\enrol;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_contentmarketplace\controllers\view;
use totara_mvc\tui_view;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_view_controller_testcase extends testcase {
    /**
     * @return void
     */
    public function test_view_content_marketplace(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        self::setAdminUser();
        $controller = new view($cm->cmid);

        $component = $cm->learning_object_marketplace_component;
        $plugin = $this->get_subplugin_name($component);

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $tui_view = new tui_view(
            "{$plugin}/pages/ActivityView",
            ['cm-id' => (int) $cm->cmid, 'has-notification' => false]
        );

        $tui_view->set_title($cm->name);
        self::assertEquals($tui_view->render(), $content);
    }

    /**
     * @return void
     */
    public function test_view_content_as_authenticated_user(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $user = $generator->create_user();
        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        self::setUser($user);

        // Since controller does not support throw exception on no course access, hence this is the
        // best way to capture the issue.
        try {
            new view($cm->cmid);
            self::fail("Expect the initialisation should yield error");
        } catch (moodle_exception $e) {
            self::assertEquals(
                get_string('redirecterrordetected', 'error', new moodle_url("/login/index.php")),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_view_content_as_enrolled_user(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $user = $generator->create_user();
        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        $generator->enrol_user($user->id, $course->id);
        self::setUser($user);
        $controller = new view($cm->cmid);

        $component = $cm->learning_object_marketplace_component;
        $plugin = $this->get_subplugin_name($component);

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $view = new tui_view(
            "{$plugin}/pages/ActivityView",
            ['cm-id' => (int) $cm->cmid, 'has-notification' => false]
        );

        $view->set_title($cm->name);
        self::assertEquals($view->render(), $content);
    }

    /**
     * @return void
     */
    public function test_view_content_as_enrolled_user_without_permission(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $user = $generator->create_user();
        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        $student_role = helper::get_student_role();
        $generator->enrol_user($user->id, $course->id, $student_role);
        $context = context_module::instance($cm->cmid);
        self::assertTrue(has_capability('mod/contentmarketplace:view', $context, $user->id));

        self::setUser($user);
        $controller = new view($cm->cmid);

        try {
            assign_capability('mod/contentmarketplace:view', CAP_PREVENT, $student_role, $context->id);
            self::assertFalse(has_capability('mod/contentmarketplace:view', $context, $user->id));

            $controller->action();
            self::fail("Expects the initialisation of controller should yield eror");
        } catch (required_capability_exception $e) {
            $action_str = get_string('contentmarketplace:view', 'mod_contentmarketplace');

            self::assertEquals(
                get_string('nopermissions', 'error', $action_str),
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_view_content_as_guest_user_with_guest_enrol_off(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        self::setGuestUser();

        // Since controller does not support throw exception on no course access, hence this is the
        // best way to capture the issue.
        try {
            new view($cm->cmid);
            self::fail("Expect the process of authorize user to redirect");
        } catch (moodle_exception $e) {
            self::assertStringContainsString(
                get_string('redirecterrordetected', 'error', new moodle_url("/enrol/index.php")),
                $e->getMessage(),
            );
        }
    }

    /**
     * @return void
     */
    public function test_view_content_as_guest_user_with_guest_enrol_on(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $cm = $generator->create_module(
            'contentmarketplace',
            [
                'course' => $course->id,
                'learning_object_marketplace_component' => 'contentmarketplace_linkedin'
            ]
        );

        $db = builder::get_db();
        $enrol_instance = $db->get_record(
            enrol::TABLE,
            [
                'enrol' => 'guest',
                'courseid' => $course->id
            ],
            '*',
            MUST_EXIST
        );

        $plugin = enrol_get_plugin('guest');
        $new_enrol_instance = new stdClass();
        $new_enrol_instance->status = ENROL_INSTANCE_ENABLED;
        $plugin->update_instance($enrol_instance, $new_enrol_instance);

        self::setGuestUser();
        $controller = new view($cm->cmid);
        $component = $cm->learning_object_marketplace_component;
        $plugin = $this->get_subplugin_name($component);

        ob_start();
        $controller->process();
        $content = ob_get_contents();
        ob_end_clean();

        $view = new tui_view(
            "{$plugin}/pages/ActivityView",
            ['cm-id' => (int) $cm->cmid, 'has-notification' => false]
        );

        $view->set_title($cm->name);
        self::assertEquals($view->render(), $content);
    }

    /**
     * @param string $component
     * @return string
     */
    private function get_subplugin_name(string $component): string {
        [$plugin_type, $plugin_name] = core_component::normalize_component($component);
        return 'contentmarketplaceactivity_' . $plugin_name;
    }
}
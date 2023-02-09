<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */

use core\theme\settings;
use core_course\theme\file\course_image;

defined('MOODLE_INTERNAL') || die();

class totara_core_get_course_image_testcase extends advanced_testcase {

    public function test_course_image() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = context_user::instance($user_one->id);
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        // Get current default image.
        $course_image = new course_image($theme_config);
        $this->assertEquals(true, $course_image->is_available());
        $url = $course_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage",
            $url->out()
        );

        // Now update the theme course default image, this should override the one set above on the system level
        $files = [
            [
                'ui_key' => 'learncourse',
                'draft_id' => $this->create_image('new_course_image', $user_context),
            ]
        ];
        $theme_settings->update_files($files);

        // Confirm that new default image is fetched.
        $url = $course_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/course/defaultcourseimage/{$course_image->get_item_id()}/new_course_image.png",
            $url->out()
        );

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $course_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage",
            $url->out()
        );

        // Let's set a new default file on the system level
        $setting = new \admin_setting_configstoredfile(
            'course/defaultimage',
            '',
            '',
            'defaultimage',
            0,
            [
                'accepted_types' => 'image',
                'context' => context_system::instance()
            ]
        );

        $draft_id = $this->create_image('new_course_image', $user_context);

        // Write new settings.
        $setting->write_setting($draft_id);

        // Confirm that new system default image is fetched.
        $url = $course_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/course/defaultimage/".theme_get_revision()."/new_course_image.png",
            $url->out()
        );

        // Now remove the theme setting file.
        $course_image->delete();

        // Confirm that now the system default image is fetched.
        $url = $course_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/course/defaultimage/".theme_get_revision()."/new_course_image.png",
            $url->out()
        );
    }

    public function test_course_get_image() {
        global $CFG, $OUTPUT;

        $course = $this->getDataGenerator()->create_course();

        // Return false if there is not image anywhere.
        $url = course_get_image($course);
        $expected = $OUTPUT->image_url('course_defaultimage', 'moodle');
        $this->assertEquals($expected->out(), $url->out());

        $this->setAdminUser();

        $context = context_course::instance($course->id);
        $fs = get_file_storage();

        $rc = [
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'images',
            'filepath' => '/',
            'filename' => 'hello_world.png',
            'mimetype' => 'png',
            'itemid' => 0,
            'license' => 'public'
        ];

        $fs->create_file_from_string($rc, 'Hello World !!!');

        $url = course_get_image($course);
        $expected = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/course/images/{$course->cacherev}/image";
        $this->assertEquals($expected, $url->out());
    }

    /**
     * @param string $name
     * @param context $context
     *
     * @return int
     */
    private function create_image(string $name, context $context): int {
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $time = time();
        $file_record = new stdClass();
        $file_record->filename = "{$name}.png";
        $file_record->contextid = $context->id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->itemid = $draft_id;
        $file_record->timecreated = $time;
        $file_record->timemodified = $time;
        $fs->create_file_from_string($file_record, $name);

        return $draft_id;
    }

}
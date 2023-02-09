<?php
/**
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_certification
 */

use core\theme\settings;
use totara_certification\theme\file\certification_image;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class totara_certification_image_testcase extends advanced_testcase {

    public function test_certification_image() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = \context_user::instance($user_one->id);
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        // Get current default image.
        $certification_image = new certification_image($theme_config);
        $this->assertEquals(true, $certification_image->is_available());
        $url = $certification_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage",
            $url->out()
        );

        // Update default image of the theme which should now come first
        $files = [
            [
                'ui_key' => 'learncert',
                'draft_id' => $this->create_image('new_certification_image', $user_context),
            ]
        ];
        $theme_settings->update_files($files);

        // Confirm that new default image is fetched.
        $url = $certification_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/defaultcertificationimage/{$certification_image->get_item_id()}/new_certification_image.png",
            $url->out()
        );

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $certification_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage",
            $url->out()
        );

        // Now update the system default image for certifications
        $fs = get_file_storage();
        $rc = [
            'contextid' => context_system::instance()->id,
            'component' => 'totara_core',
            'filearea' => 'totara_certification_default_image',
            'filepath' => '/',
            'filename' => 'hello_world.png',
            'mimetype' => 'png',
            'itemid' => 0,
            'license' => 'public'
        ];
        $fs->create_file_from_string($rc, 'Hello World !!!');

        $url = $certification_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/totara_certification_default_image/0/hello_world.png",
            $url->out()
        );

        // Now remove the theme setting file. Currently, there's no function for this so we remove it manually
        unset_config('defaultcertificationimage', 'totara_core');
        $current_file = $certification_image->get_current_file();
        $current_file->delete();

        // Now we are back to the system default image
        $url = $certification_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/totara_certification_default_image/0/hello_world.png",
            $url->out()
        );
    }

    public function test_image_enabled() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $theme_config = theme_config::load('ventura');

        // Disable advanced feature.
        advanced_feature::disable('certifications');

        // Image should be disabled and not found in files.
        $certification_image = new certification_image($theme_config);
        $this->assertEquals(false, $certification_image->is_enabled());

        $theme_settings = new settings($theme_config, 0);
        $files = $theme_settings->get_files();
        foreach ($files as $file) {
            if ($file instanceof certification_image) {
                $this->assertFalse($file->is_enabled());
            }
        }
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

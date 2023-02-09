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
 * @package totara_program
 */

use core\theme\settings;
use totara_core\advanced_feature;
use totara_program\theme\file\program_image;

defined('MOODLE_INTERNAL') || die();

class totara_program_program_image_testcase extends advanced_testcase {

    public function test_program_image() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = \context_user::instance($user_one->id);
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        // Get current default image.
        $program_image = new program_image($theme_config);
        $this->assertEquals(true, $program_image->is_available());
        $url = $program_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage",
            $url->out()
        );

        // Update default image of the theme which should now come first
        $files = [
            [
                'ui_key' => 'learnprogram',
                'draft_id' => $this->create_image('new_program_image', $user_context),
            ]
        ];
        $theme_settings->update_files($files);

        // Confirm that new default image is fetched.
        $url = $program_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/defaultprogramimage/{$program_image->get_item_id()}/new_program_image.png",
            $url->out()
        );

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $program_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage",
            $url->out()
        );

        // Now update the system default image for programs
        $fs = get_file_storage();
        $rc = [
            'contextid' => context_system::instance()->id,
            'component' => 'totara_core',
            'filearea' => 'totara_program_default_image',
            'filepath' => '/',
            'filename' => 'hello_world.png',
            'mimetype' => 'png',
            'itemid' => 0,
            'license' => 'public'
        ];
        $fs->create_file_from_string($rc, 'Hello World !!!');

        $url = $program_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/totara_program_default_image/0/hello_world.png",
            $url->out()
        );

        // Now remove the theme setting file. Currently, there's no function for this so we remove it manually
        unset_config('defaultprogramimage', 'totara_core');
        $current_file = $program_image->get_current_file();
        $current_file->delete();

        // Now we are back to the system default image
        $url = $program_image->get_current_or_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/totara_program_default_image/0/hello_world.png",
            $url->out()
        );
    }

    public function test_image_enabled() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $theme_config = theme_config::load('ventura');

        // Disable advanced feature.
        advanced_feature::disable('programs');

        // Image should be disabled and not found in files.
        $program_image = new program_image($theme_config);
        $this->assertEquals(false, $program_image->is_enabled());

        $theme_settings = new settings($theme_config, 0);
        $files = $theme_settings->get_files();
        foreach ($files as $file) {
            if ($file instanceof program_image) {
                $this->assertFalse($file->is_enabled());
            }
        }
    }

    public function data_program_or_certification(): array {
        return ['program' => [true], 'certification' => [true]];
    }

    /**
     * Test totara_program_pluginfile() with publishgridcatalogimage disabled.
     *
     * @param boolean $program
     * @dataProvider data_program_or_certification
     */
    public function test_pluginfile_visibility_unpublish(bool $program) {
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $prog1 = $this->create_program_with_images(['visible' => true], [$user1->id], $program);
        $prog2 = $this->create_program_with_images(['visible' => false], [$user1->id], $program);
        $context1 = $prog1->get_context();
        $context2 = $prog2->get_context();
        $image1args = [$context1, $prog1->id, 'images', 'totara.png', 'ventura', 'totara_catalog_medium'];
        $summary1args = [$context1, $prog1->id, 'overviewfiles', 'summary.png', null, null];
        $image2args = [$context2, $prog2->id, 'images', 'totara.png', 'ventura', 'totara_catalog_medium'];
        $summary2args = [$context2, $prog2->id, 'overviewfiles', 'summary.png', null, null];

        // Do not expose catalogue images.
        set_config('publishgridcatalogimage', 0);

        // Admin should be able to access any image of any program.
        $this->setAdminUser();
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary2args);
        $this->assert_png($contents);

        // Assigned user should be able to access only images of a visible program.
        $this->setUser($user1->id);
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unassigned user should also be able to access only images of a visible program.
        $this->setUser($user2->id);
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Guest user should be able to access only the catalog image of a visible program.
        $this->setGuestUser();
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unauthorised user should not be able to access any image of any program.
        $this->setUser(null);
        try {
            $contents = self::call_totara_program_pluginfile(...$image1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$summary1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
    }

    /**
     * Test totara_program_pluginfile() with publishgridcatalogimage enabled.
     *
     * @param boolean $program
     * @dataProvider data_program_or_certification
     */
    public function test_pluginfile_visibility_publish(bool $program) {
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $prog1 = $this->create_program_with_images(['visible' => true], [$user1->id], $program);
        $prog2 = $this->create_program_with_images(['visible' => false], [$user1->id], $program);
        $context1 = $prog1->get_context();
        $context2 = $prog2->get_context();
        $image1args = [$context1, $prog1->id, 'images', 'totara.png', 'ventura', 'totara_catalog_medium'];
        $summary1args = [$context1, $prog1->id, 'overviewfiles', 'summary.png', null, null];
        $image2args = [$context2, $prog2->id, 'images', 'totara.png', 'ventura', 'totara_catalog_medium'];
        $summary2args = [$context2, $prog2->id, 'overviewfiles', 'summary.png', null, null];

        // Expose catalogue images.
        set_config('publishgridcatalogimage', 1);

        // Admin should be able to access any image of any program.
        $this->setAdminUser();
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary2args);
        $this->assert_png($contents);

        // Assigned user should be able to access catalogue images as well as images of a visible program.
        $this->setUser($user1->id);
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unassigned user should also be able to access catalogue images as well as images of a visible program.
        $this->setUser($user2->id);
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$summary1args);
        $this->assert_png($contents);
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Guest user should be able to access catalog images.
        $this->setGuestUser();
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unauthorised user should be able to access catalog images.
        $this->setUser(null);
        $contents = self::call_totara_program_pluginfile(...$image1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
        $contents = self::call_totara_program_pluginfile(...$image2args);
        $this->assert_png($contents);
        try {
            $contents = self::call_totara_program_pluginfile(...$summary2args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
    }

    /**
     * Asserts that the variable contains a raw PNG file data.
     *
     * @param string $actual
     * @param string $message
     */
    private static function assert_png(string $actual, $message = ''): void {
        // Just verify the PNG file signature.
        static::assertEquals("\x89PNG\r\n\x1a\n", substr($actual, 0, 8), $message);
    }

    /**
     * Create a program ora certification with a catalogue image and a summary image.
     *
     * @param array $data properties passed to \totara_program\testing\generator::create_program
     * @param array $userids array of assigned user ids
     * @param boolean $program true = program, false = certification
     * @return program
     */
    private function create_program_with_images(array $data, array $userids, bool $program): program {
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        /** @var \totara_program\testing\generator */
        $progen = $gen->get_plugin_generator('totara_program');
        if ($program) {
            $prog = $progen->create_program($data);
        } else {
            $prog = $progen->create_certification($data);
        }
        $this->upload_file($prog, 'images', 'totara.png', __DIR__ . '/fixtures/leaves-green.png');
        $this->upload_file($prog, 'overviewfiles', 'summary.png', __DIR__ . '/fixtures/leaves-blue.png');
        foreach ($userids as $userid) {
            $progen->assign_to_program($prog->id, ASSIGNTYPE_INDIVIDUAL, $userid);
        }
        $this->setUser();
        return $prog;
    }

    /**
     * Upload a file.
     *
     * @param program $prog
     * @param string $filearea
     * @param string $filename
     * @param string $path
     */
    private function upload_file(program $prog, string $filearea, string $filename, string $path) {
        global $USER;
        $fs = get_file_storage();
        $userctx = context_user::instance($USER->id);
        $progctx = $prog->get_context();
        $rec = [
            'component' => 'user',
            'filearea'  => 'draft',
            'contextid' => $userctx->id,
            'itemid' => file_get_unused_draft_itemid(),
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
        ];
        $file = $fs->create_file_from_pathname($rec, $path);
        file_merge_files_from_draft_area_into_filearea($file->get_itemid(), $progctx->id, 'totara_program', $filearea, $prog->id);
        $this->assertInstanceOf(stored_file::class, $fs->get_file($progctx->id, 'totara_program', $filearea, $prog->id, '/', $filename));
    }

    /**
     * Call totara_program_pluginfile() without any disruption.
     *
     * @param context $context
     * @param integer $itemid
     * @param string $filearea
     * @param string $filename
     * @param string|null $theme
     * @param string|null $preview
     * @return string
     */
    private static function call_totara_program_pluginfile(context $context, int $itemid, string $filearea, string $filename, string $theme = null, string $preview = null): string {
        // Install a custom error handler to shut up an error message when header() is called.
        set_error_handler(function (int $errno , string $errstr) {
            if (strpos($errstr, 'Cannot modify header information - headers already sent by') === false) {
                return false;
            }
        }, E_WARNING);
        ob_start();
        try {
            // send_file_not_found() just throws moodle_exception.
            // send_stored_file() does not die if 'dontdie' is set.
            totara_program_pluginfile(null, null, $context, $filearea, [$itemid, $filename], false, ['theme' => $theme, 'preview' => $preview, 'dontdie' => true]);
            return ob_get_contents();
        } finally {
            ob_end_clean();
            restore_error_handler();
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

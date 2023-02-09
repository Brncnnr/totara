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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

use core_phpunit\testcase;
use totara_contentmarketplace\course\course_image_downloader;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_course_image_downloader_testcase extends testcase {
    /**
     * @return void
     */
    public function test_course_image(): void {
        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $url = self::getExternalTestFileUrl('/test.jpg');

        $course_image = new course_image_downloader($course->id, $url);
        $file = $course_image->download_image_for_course();

        $context = context_course::instance($course->id);

        // Test the same course
        self::assertEquals($context->id, $file->get_contextid());
        self::assertEquals(0, $file->get_itemid());
        self::assertEquals('course', $file->get_component());
        self::assertEquals('images', $file->get_filearea());

        $course_image = new course_image_downloader($course->id, $url);
        $file = $course_image->download_image_for_course();
        self::assertTrue($file);
    }

    /**
     * @return void
     */
    public function test_actual_download_image(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $test_image = file_get_contents("{$CFG->dirroot}/lib/tests/fixtures/image_test.png");
        curl::mock_response($test_image);

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $fs = get_file_storage();
        $context_course = context_course::instance($course->id);
        self::assertFalse(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'duck.jpg'
            )
        );

        $downloader = new course_image_downloader($course->id, "https://example.com/duck.jpg");
        $result = $downloader->download_image_for_course();

        self::assertIsNotBool($result);
        self::assertInstanceOf(stored_file::class, $result);

        self::assertTrue(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'duck.jpg'
            )
        );
    }

    /**
     * @return void
     */
    public function test_compare_and_update(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $test_image = file_get_contents("{$CFG->dirroot}/lib/tests/fixtures/image_test.png");
        curl::mock_response($test_image);

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        $fs = get_file_storage();
        $context_course = context_course::instance($course->id);

        $downloader = new course_image_downloader($course->id, "https://example.com/old_image.jpg");
        $downloader->download_image_for_course();

        self::assertTrue(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'old_image.jpg'
            )
        );

        $test_image = file_get_contents("{$CFG->dirroot}/lib/tests/fixtures/image_test.png");
        curl::mock_response($test_image);

        $downloader->compare_and_update(new course_image_downloader($course->id, "https://example.com/new_image.jpg"));
        self::assertFalse(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'old_image.jpg'
            )
        );

        self::assertTrue(
            $fs->file_exists(
                $context_course->id,
                'course',
                'images',
                0,
                '/',
                'new_image.jpg'
            )
        );
    }
}
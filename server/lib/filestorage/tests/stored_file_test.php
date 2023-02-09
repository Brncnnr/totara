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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core
 */

use totara_core\path;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->libdir . '/filestorage/stored_file.php');

/**
 * @coversDefaultClass \stored_file
 */
class core_files_stored_file_testcase extends advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Do markTestSkipped if the exif extension is not loaded.
     */
    private function check_exif_extension() {
        if (!extension_loaded('exif') || !function_exists('exif_read_data')) {
            $this->markTestSkipped('The EXIF extension is not available.');
        }
    }

    /**
     * @return stored_file[]
     */
    public function upload_files(): array {
        global $USER;
        $fs = get_file_storage();
        $course = $this->getDataGenerator()->create_course();
        $files = [];
        for ($i = 0; $i <= 8; $i++) {
            $jpegpath = (new path(__DIR__, 'fixtures', "orientation-{$i}.jpg"))->out(true);
            $record = [
                'contextid' => context_course::instance($course->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $i,
                'filename' => basename($jpegpath),
                'userid' => $USER->id,
                'filepath' => '/',
            ];
            $image = file_get_contents($jpegpath);
            $files[$i] = $fs->create_file_from_string($record, $image);
        }
        return $files;
    }

    /**
     * @param array $refs
     * @param integer $index
     * @param string $name
     * @param string $comperand
     * @param array $spots
     */
    private function compare_colours(&$refs, $index, $name, $comperand, $spots) {
        $image = imagecreatefromstring($comperand);
        foreach ($spots as $j => $coords) {
            if (!isset($refs[$name])) {
                $refs[$name] = [];
            }
            $rgb = imagecolorat($image, ...$coords);
            [$r, $g, $b] = [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
            if ($index) {
                // Compare against the reference colour.
                if (abs($r - $refs[$name][$j][0]) > 3 || abs($g - $refs[$name][$j][1]) > 3 || abs($b - $refs[$name][$j][2]) > 3) {
                    $this->fail(sprintf("%s: failure at #%d, (%d, %d), #%02x%02x%02x != #%02x%02x%02x", $name, $index, $coords[0], $coords[1],
                    $r, $g, $b, $refs[$name][$j][0], $refs[$name][$j][1], $refs[$name][$j][2]));
                }
            } else {
                // Use #0 as a reference.
                $refs[$name][$j] = [$r, $g, $b];
            }
        }
        imagedestroy($image);
    }

    /**
     * @covers ::get_imageinfo
     */
    public function test_get_imageinfo() {
        $this->check_exif_extension();
        $files = $this->upload_files();
        foreach ($files as $i => $file) {
            $info1 = $file->get_imageinfo();
            $info2 = $file->get_imageinfo(true);
            if ($i >= 5) {
                $this->assertEquals(128, $info1['width']);
                $this->assertEquals(128, $info2['width']);
                $this->assertEquals(400, $info1['height']);
                $this->assertEquals(400, $info2['height']);
            } else {
                $this->assertEquals(400, $info1['width']);
                $this->assertEquals(400, $info2['width']);
                $this->assertEquals(128, $info1['height']);
                $this->assertEquals(128, $info2['height']);
            }
            $this->assertEquals('image/jpeg', $info1['mimetype']);
            $this->assertEquals('image/jpeg', $info2['mimetype']);
            $this->assertArrayNotHasKey('orientation', $info1);
            if ($i == 0) {
                $this->assertArrayNotHasKey('orientation', $info2);
            } else {
                $this->assertEquals($i, $info2['orientation']);
            }
        }
    }

    /**
     * @covers ::generate_image_thumbnail
     */
    public function test_generate_image_thumbnail() {
        $this->check_exif_extension();
        $files = $this->upload_files();
        $references = [];
        foreach ($files as $i => $file) {
            $message = "square: failure at #{$i}";
            $result = $file->generate_image_thumbnail(150, 150);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(150, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(150, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'square', $result, [[22, 60], [25, 87], [104, 85]]);

            $message = "wide: failure at #{$i}";
            $result = $file->generate_image_thumbnail(250, 64);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(250, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(64, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'wide', $result, [[56, 8], [205, 47]]);

            $message = "narrow: failure at #{$i}";
            $result = $file->generate_image_thumbnail(40, 160);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(40, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(160, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'narrow', $result, [[6, 76], [15, 78], [37, 82]]);
        }
    }

    /**
     * @covers ::resize_image
     */
    public function test_resize_image() {
        $this->check_exif_extension();
        $files = $this->upload_files();
        $references = [];
        foreach ($files as $i => $file) {
            $message = "square: failure at #{$i}";
            $result = $file->resize_image(80, 80);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(80, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(25, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'square', $result, [[12, 3], [10, 16], [69, 10]]);

            $message = "wide: failure at #{$i}";
            $result = $file->resize_image(240, 48);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(150, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(48, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'wide', $result, [[23, 6], [20, 32], [123, 18]]);

            $message = "narrow: failure at #{$i}";
            $result = $file->resize_image(48, 128);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(48, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(15, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'narrow', $result, [[7, 2], [12, 7], [44, 10]]);
        }
    }

    /**
     * @covers ::crop_image
     */
    public function test_crop_image() {
        $this->check_exif_extension();
        $files = $this->upload_files();
        $references = [];
        foreach ($files as $i => $file) {
            $message = "square: failure at #{$i}";
            $result = $file->crop_image(80, 80);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(80, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(80, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'square', $result, [[32, 32], [32, 45]]);

            $message = "wide: failure at #{$i}";
            $result = $file->crop_image(180, 64);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(180, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(64, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'wide', $result, [[21, 9], [45, 33], [178, 48]]);

            $message = "narrow: failure at #{$i}";
            $result = $file->crop_image(48, 96);
            $this->assertNotFalse($result, $message);
            $imageinfo = getimagesizefromstring($result);
            $this->assertEqualsWithDelta(48, $imageinfo[0], 1, $message . ' (width)');
            $this->assertEqualsWithDelta(96, $imageinfo[1], 1, $message . ' (height)');
            $this->assertEquals(IMAGETYPE_PNG, $imageinfo[2], $message . ' (type)');
            $this->compare_colours($references, $i, 'narrow', $result, [[2, 34], [46, 72]]);
        }
    }
}

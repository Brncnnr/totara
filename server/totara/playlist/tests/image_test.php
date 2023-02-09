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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_playlist
 */

use core\json_editor\node\image;
use engage_article\totara_engage\resource\article;
use totara_engage\timeview\time_view;
use totara_playlist\local\image_processor;
use totara_playlist\playlist;

defined('MOODLE_INTERNAL') || die();

class totara_playlist_image_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. Default uses the correct cells
     *   2. Correct image is placed in the correct corner
     *   3. If an image is missing/invalid in the middle, the cells are shuffled up
     */
    public function test_image_generation() {
        global $CFG;

        // Setup, x/y positions for each cell
        [$cell_w, $cell_h] = image_processor::CELL_SIZE;
        $cells = [
            [5, 5], // top left
            [$cell_w + 5, 5], // top right
            [5, $cell_h + 5], // bottom left
            [$cell_w + 5, $cell_h + 5], // bottom right
        ];
        $red = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/red.png');
        $blue = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/blue.png');
        $green = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/green.png');
        $yellow = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/yellow.png');
        $colour_red = [255, 0, 0];
        $colour_blue = [0, 0, 255];
        $colour_green = [0, 255, 0];
        $colour_yellow = [255, 255, 0];
        $default = image_processor::IMAGE_DEFAULT;

        // Test - No source images, use the default colours instead
        $image = $this->invoke_generate_image([]);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - One of each
        $image = $this->invoke_generate_image([$red, $blue, $yellow, $green]);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($colour_green, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - One default square
        $image = $this->invoke_generate_image([$red, $blue, $yellow]);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - Missing image in middle, everything's shuffled up
        $image = $this->invoke_generate_image([$red, $blue, null, $yellow]);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2])); // Note yellow is 3rd
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Cleanup
        unset($red, $blue, $green, $yellow);
    }

    /**
     * Test generating the square version of the images
     * Validate the following:
     *   1. Default uses the correct cells
     *   2. Correct image is placed in the correct corner
     *   3. If an image is missing/invalid in the middle, the cells are shuffled up
     */
    public function test_square_image_generation() {
        global $CFG;

        // Setup, x/y positions for each cell
        [$cell_w, $cell_h] = image_processor::CELL_SQUARE_SIZE;
        $cells = [
            [5, 5], // top left
            [$cell_w + 5, 5], // top right
            [5, $cell_h + 5], // bottom left
            [$cell_w + 5, $cell_h + 5], // bottom right
        ];
        $red = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/red.png');
        $blue = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/blue.png');
        $green = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/green.png');
        $yellow = file_get_contents($CFG->dirroot . '/totara/playlist/tests/fixtures/yellow.png');
        $colour_red = [255, 0, 0];
        $colour_blue = [0, 0, 255];
        $colour_green = [0, 255, 0];
        $colour_yellow = [255, 255, 0];
        $default = image_processor::IMAGE_DEFAULT;

        // Test - No source images, use the default colours instead
        $image = $this->invoke_generate_image([], true);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - One of each
        $image = $this->invoke_generate_image([$red, $blue, $yellow, $green], true);

        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($colour_green, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - One default square
        $image = $this->invoke_generate_image([$red, $blue, $yellow], true);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2]));
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Test - Missing image in middle, everything's shuffled up
        $image = $this->invoke_generate_image([$red, $blue, null, $yellow], true);
        $this->assertNotEmpty($image);
        $canvas = imagecreatefromstring($image);
        $this->assertSame($colour_red, $this->get_rgb_at_coords($canvas, $cells[0]));
        $this->assertSame($colour_blue, $this->get_rgb_at_coords($canvas, $cells[1]));
        $this->assertSame($colour_yellow, $this->get_rgb_at_coords($canvas, $cells[2])); // Note yellow is 3rd
        $this->assertSame($default, $this->get_rgb_at_coords($canvas, $cells[3]));
        imagedestroy($canvas);

        // Cleanup
        unset($red, $blue, $green, $yellow);
    }

    /**
     * Invokes the image_processor::generate_image for testing
     *
     * @param array $images
     * @param false $is_square
     * @return string|null
     */
    private function invoke_generate_image(array $images, $is_square = false): ?string {
        $process = new ReflectionMethod(image_processor::class, 'generate_image');
        $process->setAccessible(true);
        return $process->invoke(image_processor::make(), $images, $is_square);
    }

    /**
     * Helper to pick the rgb colour out of a spot in the image
     *
     * @param $canvas
     * @param array $coords
     * @return array|int[]
     */
    private function get_rgb_at_coords($canvas, array $coords): array {
        $rgb = imagecolorat($canvas, ...$coords);
        $val = [
            ($rgb >> 16) & 0xFF,
            ($rgb >> 8) & 0xFF,
            $rgb & 0xFF,
        ];
        $transparency = ($rgb & 0x7F000000) >> 24;
        if ($transparency !== 0) {
            $val[] = $transparency;
        }
        return $val;
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
     * Call totara_playlist_pluginfile() without any disruption.
     *
     * @param context $context
     * @param integer $itemid
     * @param string $filearea
     * @param string $filename
     * @param string|null $theme
     * @param string|null $preview
     * @return string
     */
    private static function call_playlist_pluginfile(context $context, int $itemid, string $filearea, string $filename, string $theme = null, string $preview = null): string {
        // Install a custom error handler to shut up an error message when header() is called.
        set_error_handler(function (int $errno, string $errstr) {
            if (strpos($errstr, 'Cannot modify header information - headers already sent by') === false) {
                return false;
            }
        }, E_WARNING);
        ob_start();
        try {
            // send_file_not_found() just throws moodle_exception.
            // send_stored_file() does not die if 'dontdie' is set.
            totara_playlist_pluginfile(null, null, $context, $filearea, [$itemid, $filename], false, ['theme' => $theme, 'preview' => $preview, 'dontdie' => true]);
            return ob_get_contents();
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
    }

    /**
     * Test totara_playlist_pluginfile() with publishgridcatalogimage disabled.
     *
     */
    public function test_pluginfile_visibility_unpublish() {

        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $gen->get_plugin_generator('totara_playlist');
        $admin = $gen->create_user();
        $user1 = $gen->create_user();
        $this->setUser($admin);

        $playlist1 = $playlist_generator->create_playlist_with_image('totara1', 1);
        $playlist2 = $playlist_generator->create_playlist_with_image('totara2', 0);

        $image1args = [$playlist1->get_context(), $playlist1->get_id(), playlist::IMAGE_AREA, 'card.png', 'ventura', 'totara_catalog_medium'];
        $image2args = [$playlist2->get_context(), $playlist2->get_id(), playlist::IMAGE_AREA, 'card.png', 'ventura', 'totara_catalog_medium'];

        // Do not expose catalogue images.
        set_config('publishgridcatalogimage', 0);

        // Admin should be able to access any image of article.
        $this->setAdminUser();
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);

        $contents = self::call_playlist_pluginfile(...$image2args);
        $this->assert_png($contents);

        // User should be able to access only images of a public article.
        $this->setUser($user1->id);
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_playlist_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Guest user should not be able to access any image of any workspace.
        $this->setGuestUser();
        try {
            $contents = self::call_playlist_pluginfile(...$image1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        try {
            $contents = self::call_playlist_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unauthorised user should not be able to access any image of any workspace.
        $this->setUser(null);
        try {
            $contents = self::call_playlist_pluginfile(...$image1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        try {
            $contents = self::call_playlist_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
    }

    /**
     * Test totara_playlist_pluginfile() with publishgridcatalogimage enabled.
     *
     */
    public function test_pluginfile_visibility_publish() {

        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $gen->get_plugin_generator('totara_playlist');
        $admin = $gen->create_user();
        $user1 = $gen->create_user();
        $this->setUser($admin);

        $playlist1 = $playlist_generator->create_playlist_with_image('totara1', 1);
        $playlist2 = $playlist_generator->create_playlist_with_image('totara2', 0);

        $image1args = [$playlist1->get_context(), $playlist1->get_id(), playlist::IMAGE_AREA, 'card.png', 'ventura', 'totara_catalog_medium'];
        $image2args = [$playlist2->get_context(), $playlist2->get_id(), playlist::IMAGE_AREA, 'card.png', 'ventura', 'totara_catalog_medium'];


        // Expose catalogue images.
        set_config('publishgridcatalogimage', 1);

        // Admin should be able to access any image of any workspace.
        $this->setAdminUser();
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_playlist_pluginfile(...$image2args);
        $this->assert_png($contents);

        // User should be able to access catalogue images.
        $this->setUser($user1->id);
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_playlist_pluginfile(...$image2args);
        $this->assert_png($contents);

        // Guest user should be able to access catalog images.
        $this->setGuestUser();
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_playlist_pluginfile(...$image2args);
        $this->assert_png($contents);

        // Unauthorised user should be able to access catalog images.
        $this->setUser(null);
        $contents = self::call_playlist_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_playlist_pluginfile(...$image2args);
        $this->assert_png($contents);
    }
}
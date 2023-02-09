<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 */

use \totara_tui\local\theme_config;

defined('MOODLE_INTERNAL') || die();

class totara_tui_local_theme_config_testcase extends advanced_testcase {

    public function test_get_component_sha() {
        /** @var theme_config $theme */
        $theme = theme_config::load('ventura');
        self::assertInstanceOf(theme_config::class, $theme);

        $method = new ReflectionMethod($theme, 'get_tui_scss_instance');
        $method->setAccessible(true);
        $tui_scss = $method->invoke($theme);
        $files = $tui_scss->get_loaded_files('samples/pages/samples');
        $shas = [];
        foreach ($files as $file) {
            if (file_exists($file) && is_readable($file)) {
                $shas[] = sha1_file($file);
            } else {
                $shas[] = $file;
            }
        }
        if (empty($shas)) {
            // This mimicks how get_component_sha works.
            $shas[] = '';
        }
        // This accounts for settings.
        $shas[] = sha1('');
        $expected = sha1(join("\n", $shas));
        $actual = $theme->get_component_sha('samples/pages/samples');
        self::assertSame($expected, $actual);
    }
}

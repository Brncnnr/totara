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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_tui
 * @var admin_root $ADMIN
 */

defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('totara_tui_settings', new lang_string('pluginname', 'totara_tui'));
if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configcheckbox(
            'totara_tui/cache_js',
            new lang_string('setting_cache_js', 'totara_tui'),
            new lang_string('setting_cache_js_desc', 'totara_tui'),
            '1'
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'totara_tui/cache_scss',
            new lang_string('setting_cache_scss', 'totara_tui'),
            new lang_string('setting_cache_scss_desc', 'totara_tui'),
            '1'
        )
    );
    $settings->add(
        new admin_setting_configcheckbox(
            'totara_tui/development_mode',
            new lang_string('setting_development_mode', 'totara_tui'),
            new lang_string('setting_development_mode_desc', 'totara_tui'),
            '0'
        )
    );
    if (version_compare(PHP_VERSION, '7.4.0') < 0) {
        $settings->add(
            new admin_setting_configcheckbox(
                'totara_tui/pre_php74_allow_unperformant_css',
                new lang_string('setting_pre_php74_allow_unperformant_css', 'totara_tui'),
                new lang_string('setting_pre_php74_allow_unperformant_css_desc', 'totara_tui'),
                '0'
            )
        );
    }
}
$ADMIN->add('appearance', $settings);

$ADMIN->add('development', new admin_externalpage('tuisamples', new lang_string('samples', 'totara_tui'),
    $CFG->wwwroot . '/totara/tui/index.php'));
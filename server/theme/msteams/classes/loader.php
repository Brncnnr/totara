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
 * @package theme_msteams
 */

namespace theme_msteams;

use cache;
use core_minify;
use html_writer;
use moodle_page;
use theme_msteams\output\core_renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end code loader.
 */
final class loader {
    /**
     * Load a compatibility hack (shim) at the end of the current page.
     * The shim is loaded only if the current theme is msteams.
     *
     * @param string $name
     * @param moodle_page|null $page custom page instance instead of $PAGE
     * @return boolean
     */
    public static function load_shim_js(string $name, ?moodle_page $page = null): bool {
        global $CFG, $PAGE, $SESSION;
        if (!isset($SESSION->theme) || $SESSION->theme != 'msteams') {
            return false;
        }
        if (clean_param($name, PARAM_ALPHANUMEXT) !== $name) {
            debugging("The shim with name '{$name}' does not exist.", DEBUG_DEVELOPER);
            return false;
        }
        $relpath = '/theme/msteams/script/shim/' . $name . '.js';
        if (!file_exists($CFG->dirroot . $relpath)) {
            debugging("The shim with name '{$name}' does not exist.", DEBUG_DEVELOPER);
            return false;
        }
        $key = 'js_theme_msteams_shim_' . $name;
        $js = self::load_script_internal($key, $relpath, true);
        $page = $page ?? $PAGE;
        /** @var moodle_page $page */
        $page->requires->js_init_code($js, false, null);
        return true;
    }

    /**
     * Load a script and return its minified version.
     *
     * @param string $key cache key
     * @param string $relpath relative path from $CFG->dirroot, starting with '/'
     * @param boolean $raw set true to return code only
     * @return string if $raw is true minified code, otherwise <script> containing the code
     * @internal Do not call this function outside theme_msteams or totara_msteams
     */
    public static function load_script_internal(string $key, string $relpath, bool $raw = false): string {
        global $CFG;
        $cache = cache::make('theme_msteams', 'postprocessedcode');
        $js = $cache->get($key);
        if ($js === false) {
            $js = core_minify::js_files([$CFG->dirroot.$relpath]);
            $cache->set($key, $js);
        }
        if ($raw) {
            return $js;
        }
        return html_writer::script($js);
    }

    /**
     * Load a css and return its minified version.
     *
     * @param string $key cache key
     * @param string $relpath relative path from $CFG->dirroot, starting with '/'
     * @param boolean $raw set true to return css only
     * @return string if $raw is true minified css, otherwise <style> containing the css
     * @internal Do not call this function outside theme_msteams or totara_msteams
     */
    public static function load_css_internal(string $key, string $relpath, bool $raw = false): string {
        global $CFG;
        $cache = cache::make('theme_msteams', 'postprocessedcode');
        $css = $cache->get($key);
        if ($css === false) {
            $css = core_minify::css_files([$CFG->dirroot.$relpath]);
            $cache->set($key, $css);
        }
        if ($raw) {
            return $css;
        }
        return html_writer::tag('style', $css);
    }

    /**
     * Load a custom css from parent theme and return its minified version
     *
     * @param string $key cache key
     * @param moodle_page $page
     * @return string
     */
    public static function load_parent_css(string $key, moodle_page $page): string {

        $cache = cache::make('theme_msteams', 'postprocessedcode');
        $data = $cache->get($key);
        if ($data === false || $data['rev'] == -1 || $data['rev'] != theme_get_revision()) {
            $theme = \theme_config::load(core_renderer::PARENT_THEME);
            $css = '';
            // CSS part from \theme_config::get_css_content()
            if (!during_initial_install()) {
                $theme_settings = new \core\theme\settings($theme, (int)$page->context->tenantid);
                $css = $theme_settings->get_css_variables();
            }
            $css = $theme->post_process($css);
            if ($theme->minify_css) {
                $css = core_minify::css($css);
            }
            $cache->set($key, ['rev' => theme_get_revision(), 'css' => $css]);
        } else {
            $css = $data['css']; // use cache
        }
        return html_writer::tag('style', $css);
    }
}

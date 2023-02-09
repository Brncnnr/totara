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
 */

namespace totara_tui\local;

use totara_tui\local\scss\scss;
use totara_tui\local\scss\scss_options;

/**
 * Overridden theme config for use when mediating JS and CSS for Tui.
 * This is local, and should only be used by mediation scripts belonging to totara_tui.
 */
final class theme_config extends \theme_config {

    /**
     * Totara Tui is not compatible with minify.
     * @var bool
     */
    public $minify_css = false;

    /**
     * If set to true, when resolving SCSS it will not be compiled
     * @var bool
     */
    private $skip_scss_compilation = false;

    /**
     * Get CSS content for type and subtype. Called by styles.php.
     *
     * @param string $type
     * @param int $tenant_id
     * @return string
     */
    public function get_css_content_by($type, int $tenant_id) {
        $csscontent = $this->get_tui_css_content($type, $tenant_id);
        $csscontent = $this->post_process($csscontent);
        return $csscontent;
    }

    /**
     * Return an SHA sum for the all of the files that would be built into the SCSS bundle for the given component
     *
     * The SHA sum will change if any individual file that is going to be processed changes.
     *
     * @param string $component
     * @return string
     */
    public function get_component_sha(string $component, int $tenant_id = null): string {
        $tui_scss = $this->get_tui_scss_instance($tenant_id);
        $theme_settings = $tui_scss->get_options()->get_theme_settings();
        $settings_css = '';
        if ($theme_settings) {
            $settings_css = $theme_settings->get_css_variables();
        }
        $shas = join(
            "\n",
            array_map(
                function($file) {
                    if (file_exists($file) && is_readable($file)) {
                        return sha1_file($file);
                    }
                    return $file;
                },
                $tui_scss->get_loaded_files($component)
            )
        );
        $shas .= "\n" . sha1($settings_css);
        return sha1($shas);
    }

    /**
     * Skips the compilation of SCSS
     */
    public function skip_scss_compilation() {
        $this->skip_scss_compilation = true;
    }

    /**
     * Get the compiled TUI CSS content for the provided Totara component
     *
     * @param string $component
     * @param int $tenant_id
     * @return string Compiled CSS
     */
    private function get_tui_css_content(string $component, int $tenant_id): string {
        $tui_scss = $this->get_tui_scss_instance($tenant_id);
        return $tui_scss->get_compiled_css($component);
    }

    /**
     * Return an scss instance for this theme.
     * @param int|null $tenant_id
     * @return scss
     */
    private function get_tui_scss_instance(int $tenant_id = null): scss {
        $scss_options = new scss_options();

        $scss_options->set_themes($this->get_tui_theme_chain());
        $scss_options->set_legacy($this->legacybrowser);

        $scss_options->set_minify(true);
        $scss_options->set_sourcemap_enabled(false);

        if (!during_initial_install()) {
            if (isset($tenant_id)) {
                $scss_options->set_theme_settings(new \core\theme\settings($this, $tenant_id));
            }

            if (get_config('totara_tui', 'development_mode')) {
                // Impossible to get here during PHPUnit tests.
                // @codeCoverageIgnoreStart
                $scss_options->set_minify(false);
                $scss_options->set_sourcemap_enabled(true);
                // @codeCoverageIgnoreEnd
            }
        }

        if ($this->skip_scss_compilation) {
            $scss_options->set_skip_compile(true);
        }

        return new scss($scss_options);
    }

    /**
     * Get theme chain (e.g. ['base', 'roots', 'basis']) for TUI CSS.
     *
     * Themes are only included if they have `$THEME->tui = true` in config.php.
     *
     * @return string[]
     */
    public function get_tui_theme_chain(): array {
        $themes = [];

        // Find out wanted parent sheets.
        $excludes = $this->resolve_excludes('parents_exclude_sheets');
        if ($excludes !== true) {
            // Base first, the immediate parent last.
            foreach (array_reverse($this->parent_configs) as $parent_config) {
                $parent = $parent_config->name;
                if (!empty($excludes[$parent]) and $excludes[$parent] === true) {
                    continue;
                }
                $themes[] = $parent;
            }
        }

        $themes[] = $this->name;

        return $themes;
    }
}
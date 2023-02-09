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
 * @package totara_tui
 */

namespace totara_tui\local\scss;

use core\topological_sorter;
use totara_tui\local\locator\bundle;

/**
 * Encapsulates logic for compiling TUI SCSS
 */
class scss {
    /**
     * @var scss_options
     */
    protected $options;

    /**
     * @var array Import paths for compiler.
     */
    protected $import_paths;

    /**
     * Create a new instance
     *
     * @param scss_options $options
     */
    public function __construct(scss_options $options = null) {
        $this->options = $options ?? new scss_options();
        $this->import_paths = [\Closure::fromCallable([$this, 'resolve_import'])];
    }

    /**
     * Get the options object for this instance
     *
     * @return scss_options
     */
    public function get_options(): scss_options {
        return $this->options;
    }

    /**
     * Compile SCSS for the provided Totara component.
     *
     * @param string $component Component to build CSS for.
     * @return string
     */
    public function get_compiled_css(string $component): string {
        // Include any content that might have been changed by theme settings.
        $settings_css = '';
        if (!during_initial_install() && $this->options->get_theme_settings()) {
            $settings_css = $this->options->get_theme_settings()->get_css_variables();
        }

        $import_data = $this->get_imports($component);

        $legacy_var_values = [];
        if ($import_data->cssvars_legacy_imports) {
            $css_realvars_legacy = $this->compile(
                $this->build_import_code($import_data->cssvars_legacy_imports),
                false
            );
            $legacy_var_values = $this->options->get_cssvars()->get_custom_property_values($css_realvars_legacy);

            // Include any content that might have been changed by theme settings.
            if (!during_initial_install()) {
                $legacy_var_values = array_merge(
                    $legacy_var_values,
                    $this->options->get_cssvars()->get_custom_property_values($settings_css)
                );
            }
        }

        $output = $this->build_import_code($import_data->imports);
        if (!$this->options->get_skip_compile()) {
            $output = $this->compile($output, $this->options->get_sourcemap_enabled());
        }

        if ($this->options->get_legacy()) {
            $output = $this->options->get_cssvars()->transform($output, ['override_values' => $legacy_var_values]);
        } else {
            if (strpos($component, 'theme_') === 0) {
                $output .= $settings_css;
            }
        }

        return $output;
    }

    /**
     * Get list of files we read to build the CSS.
     *
     * @param string $component
     * @return string[]
     */
    public function get_loaded_files(string $component): array {
        $import_data = $this->get_imports($component);
        $imports = array_merge($import_data->imports, $import_data->cssvars_legacy_imports);
        $imports = array_reduce($imports, function ($acc, $url) {
            $url_parts = explode('!', $url);
            $url = end($url_parts);
            $file = $this->resolve_import($url);
            if ($file) {
                $acc[] = $file;
            }
            return $acc;
        }, []);
        $imports = array_values(array_unique($imports));

        $loaded_files = [];
        foreach ($imports as $import) {
            $loaded_files[] = $import;
            $this->populate_loaded_files_from_file($import, $loaded_files);
        }

        return $loaded_files;
    }

    /**
     * Populate list of files that the provided file will load (recursive)
     *
     * @param string $path Path to file on filesystem
     * @param array $loaded_files Reference to loaded files array. Found files will be added here.
     */
    private function populate_loaded_files_from_file(string $path, array &$loaded_files): void {
        // scan for @imports using regex and add them to $loaded_files
        $contents = file_get_contents($path);
        if (!preg_match_all('/@import (.*?)(?:;|$)/', $contents, $import_statements)) {
            return;
        }
        foreach ($import_statements[1] as $import_statement) {
            foreach (explode(',', $import_statement) as $import) {
                if (preg_match('/^([\'"])(.*)\1$/', trim($import), $matches)) {
                    $url_parts = explode('!', $matches[2]);
                    $url = end($url_parts);
                    $loaded_file = $this->resolve_import($url);
                    if ($loaded_file && !in_array($loaded_file, $loaded_files)) {
                        $loaded_files[] = $loaded_file;
                        $this->populate_loaded_files_from_file($loaded_file, $loaded_files);
                    }
                }
            }
        }
    }

    /**
     * Build a string containing `@import`s
     *
     * @param string[] $imports List of URLs to import.
     * @return string
     */
    private function build_import_code(array $imports): string {
        return array_reduce($imports, function ($acc, $item) {
            return "{$acc}@import '{$item}';\n";
        }, '');
    }

    /**
     * Get files (URLs) to import (in the correct order) to build the CSS.
     *
     * Standard imports on $result->imports.
     * If legacy mode enabled and not compiling theme,
     * $result->cssvars_legacy_imports will contain imports needed to get theme CSS vars.
     *
     * @param string $component
     * @return object with 'imports' and 'cssvars_legacy_imports' properties
     */
    private function get_imports(string $component): object {
        /*
         * In order for the theme inheritance to work correctly with both variable types, we
         * need to separate them so they can be compiled in the correct order.
         *
         * The order is (in pseudocode):
         *
         *   if not compiling theme:
         *     add component scss vars
         *   add core scss vars
         *   for each theme:
         *     add theme scss vars
         *   if compiling theme:
         *     for each theme
         *       add theme css vars and css content
         *   else:
         *     add component css vars and css content
         *
         * This is because SCSS variables are evaluated as they are seen, and CSS variables
         * are evaluated lazily, and we want CSS variables defined in components to slot in
         * in the right place to be overridable by themes.
         *
         * For example, the following SCSS will make `button` green:
         * $bg: green; button { background: $bg; } $bg: blue;
         * Using CSS variables it would be blue:
         * :root { --bg: green: } button { background: var(--bg); } :root { --bg: blue; }
         *
         * For this reason we have to evaluate SCSS variables first, so that variables have
         * their final values by the time we evaluate component styles.
         *
         * We can't simply put CSS vars in the header too, because then CSS vars defined in
         * components will not be overridable by later themes.
         *
         * In legacy mode, when compiling a non-theme component we must also load CSS var
         * values from the themes to provide to the CSS var polyfill.
         *
         * When serving the resulting CSS, the order is components, then theme,
         * to allow theme vars to override component vars.
         */

        $component_files = $this->get_component_tui_scss_files($component);
        $tui_core_files = $this->get_component_tui_scss_files('tui');
        $theme_file_sets = $this->get_themes_tui_scss();

        $sorter = new topological_sorter();
        $this->add_with_deps($sorter, $component);
        $deps = $sorter->sort();
        $dep_file_sets = [];
        foreach ($deps as $dep) {
            if ($dep === 'tui' || $dep === $component) {
                continue;
            }
            $dep_file_sets[] = $this->get_component_tui_scss_files($dep);
        }

        $imports = [];

        $is_theme = strpos($component, 'theme_') === 0;

        // SCSS vars
        $def_file_sets = array_merge([$tui_core_files], $dep_file_sets, [$component_files], $theme_file_sets);

        foreach ($def_file_sets as $def_files) {
            foreach ($def_files['variables'] as $def_file) {
                $imports[] = "definitions_only!internal_absolute:" . $def_file;
            }
        }

        // CSS vars from theme for CSS vars polyfill
        $cssvars_legacy_imports = [];
        if ($this->options->get_legacy()) {
            $cssvars_legacy_imports = $imports;

            // tui core vars
            foreach ($tui_core_files['variables'] as $theme_file) {
                $cssvars_legacy_imports[] = "output_only!internal_absolute:" . $theme_file;
            }

            // vars from tui components we depend on
            foreach ($dep_file_sets as $dep_files) {
                foreach ($dep_files['variables'] as $theme_file) {
                    $cssvars_legacy_imports[] = "output_only!internal_absolute:" . $theme_file;
                }
            }

            // vars from theme
            foreach ($theme_file_sets as $theme_files) {
                foreach ($theme_files['variables'] as $theme_file) {
                    $cssvars_legacy_imports[] = "output_only!internal_absolute:" . $theme_file;
                }
            }
        }

        // CSS vars and component content
        $source_file_sets = $is_theme ? $theme_file_sets : [$component_files];
        foreach ($source_file_sets as $source_files) {
            foreach ($source_files['variables'] as $source_file) {
                $imports[] = "output_only!internal_absolute:" . $source_file;
            }
            foreach ($source_files['scss'] as $source_file) {
                $imports[] = "internal_absolute:" . $source_file;
            }
        }

        $return = new \stdClass;
        $return->imports = $imports;
        $return->cssvars_legacy_imports = $cssvars_legacy_imports;

        return $return;
    }

    /**
     * Add Tui component with its dependencies to the topological sorter.
     *
     * @param topological_sorter $sorter
     * @param string $component
     */
    private function add_with_deps(topological_sorter $sorter, string $component) {
        $deps = bundle::get_bundle_dependencies($component);
        $sorter->add($component, $deps);
        foreach ($deps as $dep) {
            if ($sorter->has($dep)) continue;
            $this->add_with_deps($sorter, $dep);
        }
    }

    /**
     * Compile the provided SCSS
     *
     * @param string $scss
     * @param bool $sourcemap
     * @return string
     */
    private function compile(string $scss, bool $sourcemap): string {
        $compiler = $this->options->get_compiler();
        $compiler->set_import_paths($this->import_paths);
        $compiler->set_sourcemap_enabled($sourcemap);
        $compiler->set_minify($this->options->get_minify());
        return $compiler->compile($scss);
    }

    /**
     * Resolve an import URL to a file on the filesystem
     *
     * @param string $url
     * @return string|null
     */
    private function resolve_import(string $url): ?string {

        // handle "@import 'internal_absolute:/path/to/file.scss';"
        if (substr($url, 0, 18) == 'internal_absolute:') {
            return substr($url, 18);
        }

        // handle "@import 'theme_foo/name';"
        // and map to theme/foo/build/styles/_name.scss

        // ignore external requests
        if (preg_match('/^https?:\/\//', $url)) {
            return null;
        }

        $wants_bundle = false;
        $parts = explode('/', $url, 2);
        if (count($parts) === 1) {
            $wants_bundle = true;
            $parts[] = '';
        } if (count($parts) != 2) {
            return null;
        }

        list($bundle, $path) = $parts;
        unset($parts); // Don't be tempted to abuse me.

        if (substr($path, 0, 1) === '@') {
            debugging('What is this?', DEBUG_DEVELOPER);
            $path = substr($path, 1);
        }

        if ($bundle !== clean_param($bundle, PARAM_SAFEPATH)) {
            throw new \coding_exception('Import does not reference a valid bundle', $url);
        }
        if ($path !== clean_param($path, PARAM_SAFEPATH)) {
            throw new \coding_exception('Import does not reference a valid path', $url);
        }

        if ($wants_bundle) {
            return bundle::get_bundle_css_file($bundle);
        }

        $file = bundle::get_style_import($bundle, $path . '.scss');
        if ($file) {
            return $file;
        }
        $altpath = preg_replace('/[^\/]+$/', '_\0', $path);
        $file = bundle::get_style_import($bundle, $altpath . '.scss');

        if (!$file) {
            debugging('Unable to resolve import ' . $path, DEBUG_DEVELOPER);
        }

        return $file;
    }

    /**
     * Get an array of the TUI SCSS files provided by each theme.
     *
     * @return array[]
     */
    private function get_themes_tui_scss(): array {
        return array_map(function ($theme) {
            return $this->get_component_tui_scss_files("theme_{$theme}");
        }, $this->options->get_themes());
    }

    /**
     * Get the TUI SCSS files provided by a component.
     *
     * @param string $component
     * @return array With keys 'scss' and 'variables' containing paths to files for each category if they exist.
     */
    protected function get_component_tui_scss_files(string $component): array {
        $cssfiles = [
            'scss' => [],
            'variables' => [],
        ];
        $bundle = bundle::get_bundle_css_file($component);
        if ($bundle) {
            $cssfiles['scss']['bundle'] = $bundle;
        }
        $bundle = bundle::get_bundle_css_variables_file($component);
        if ($bundle) {
            $cssfiles['variables']['_variables'] = $bundle;
        }
        return $cssfiles;
    }
}

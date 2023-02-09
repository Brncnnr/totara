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

namespace totara_tui\local\locator;

use RegexIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use coding_exception;
use totara_core\path;
use totara_tui\output\framework;

/**
 * TUI bundle helper class.
 *
 * This class knows where to find Tui bundles and how to read the dependencies file produced by the Tui build process.
 *
 * At its core a map is produced of all known bundle files, with requests to bundles being served quickly and efficiently
 * by lookups against the map.
 */
final class bundle {

    /**
     * These suffixes are used at the end of Tui bundle files.
     */
    private const SUFFIX_PRODUCTION = '';
    private const SUFFIX_PRODUCTION_LEGACY = '.legacy';
    private const SUFFIX_DEVELOPMENT = '.development';
    private const SUFFIX_DEVELOPMENT_LEGACY = '.legacy.development';

    /**
     * The directory containing loadable bundles, relative to $CFG->srcroot
     */
    private const DIR_BUNDLE = '/client/component';

    /**
     * A singleton.
     * All interaction with bundles is via static methods.
     * @var bundle
     */
    private static $instance;

    /** @var bool True once the map has been initialised. */
    private $map_initialised = false;

    /** @var array[] A multidimensional array $map_bundle_js['bundle_name']['suffix'] = $absolute_path_to_js_file  */
    private $map_bundle_js = [];
    /** @var array[] A multidimensional array $map_bundle_scss['bundle_name']['suffix'] = $absolute_path_to_scss_file  */
    private $map_bundle_scss = [];
    /**
     * Maps possible imports paths to the files that should be included to load the bundle.
     * This is essentially a map or relative file paths (to the client/build dir) to the absolute file path.
     * A multidimensional array $map_scss_imports['bundle_name']['suffix']['import_path'] = $absolute_path_to_scss_file
     * @var array[]
     */
    private $map_scss_imports = [];
    /** @var path[] An array of vendor files, the key is the suffix, and the value the absolute path for that file. */
    private $map_vendors_js = [];
    /**
     * An array of bundle dependencies. The key is the bundle name, the value is an array of dependencies.
     * @var array[]
     */
    private $map_bundle_dependencies = [];

    /**
     * An array of bundle CSS variables json files. The key is the number name, the value is the absolute path to the file
     * @var path[]
     */
    private $map_bundle_css_variables_json = [];

    /**
     * A map of file suffixes to URL parameters used for the suffix, for caching.
     * @var string[]
     */
    private static $map_file_to_param = [
        self::SUFFIX_PRODUCTION => 'p',
        self::SUFFIX_PRODUCTION_LEGACY => 'pl',
        self::SUFFIX_DEVELOPMENT => 'd',
        self::SUFFIX_DEVELOPMENT_LEGACY => 'dl',
    ];

    /**
     * Given a bundle name return the JS file that should be included.
     * If the bundle does not exist, or the bundle has no JS then null is returned.
     * @param string $bundle
     * @return path|null
     */
    public static function get_bundle_js_file(string $bundle): ?path {
        return self::instance()->resolve_bundle_js($bundle);
    }

    /**
     * Given a bundle name return the SCSS file that should be included.
     * If the bundle does not exist, or the bundle has no SCSS then null is returned.
     * @param string $bundle
     * @return path|null
     */
    public static function get_bundle_css_file(string $bundle): ?path {
        return self::instance()->resolve_bundle_scss($bundle);
    }

    /**
     * Given a bundle name return the SCSS file that can be expected to contain variables.
     * If the bundle does not exist, or the bundle has no SCSS variables then null is returned.
     * @param string $bundle
     * @return path|null
     */
    public static function get_bundle_css_variables_file(string $bundle): ?path {
        return self::instance()->resolve_style_import($bundle, '_variables.scss');
    }

    /**
     * Given a bundle name return the json file that describes the CSS variables used by the bundle.
     * If the bundle does not exist, or the bundle has no CSS variables then null is returned.
     * @param string $bundle
     * @return path|null
     */
    public static function get_bundle_css_json_variables_file(string $bundle): ?path {
        return self::instance()->resolve_bundle_css_variables_json($bundle);
    }

    /**
     * Returns the path to the vendors file within the Tui build directory.
     * @return path|null
     */
    public static function get_vendors_file(): ?path {
        return self::instance()->resolve_vendor_js();
    }

    /**
     * Returns the path to the SCSS file to import. Null if it cannot be resolved.
     * @param string $bundle
     * @param string $importpath
     * @return path|null
     */
    public static function get_style_import(string $bundle, string $importpath): ?path {
        return self::instance()->resolve_style_import($bundle, $importpath);
    }

    /**
     * Returns any dependencies the given bundle has.
     * @param string $bundle
     * @return string[] An array of bundle names that the given bundle is dependent upon.
     */
    public static function get_bundle_dependencies(string $bundle): array {
        return self::instance()->resolve_bundle_dependencies($bundle);
    }

    /**
     * Resets the singleton instance.
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Returns a singleton of this class.
     * @return bundle
     */
    private static function instance(): bundle {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Private constructor. Use self::instance();
     */
    private function __construct() {
        // Static methods only.
    }

    /**
     * Returns the directory that a bundle should exist in.
     * @param string $bundle
     * @return path
     * @throws coding_exception If the bundle name given is not valid.
     */
    private static function anticipated_bundle_location(string $bundle): path {
        global $CFG;
        if ($bundle !== framework::clean_bundle_name($bundle)) {
            throw new coding_exception('Invalid bundle name provided.');
        }
        return new path($CFG->srcroot, self::DIR_BUNDLE, $bundle, 'build');
    }

    /**
     * Given a bundle name resolve the JS file location.
     * @param string $bundle
     * @return path|null
     */
    private function resolve_bundle_js(string $bundle): ?path {
        $this->ensure_map_generated();
        foreach (self::get_js_suffix_for_file() as $suffix) {
            if (isset($this->map_bundle_js[$bundle][$suffix])) {
                return $this->map_bundle_js[$bundle][$suffix];
            }
        }
        return null;
    }

    /**
     * Given a bundle name resolve the CSS file location.
     * @param string $bundle
     * @return path|null
     */
    private function resolve_bundle_scss(string $bundle): ?path {
        $this->ensure_map_generated();
        foreach (self::get_css_suffix_for_file() as $suffix) {
            if (isset($this->map_bundle_scss[$bundle][$suffix])) {
                return $this->map_bundle_scss[$bundle][$suffix];
            }
        }
        return null;
    }

    /**
     * Resolve the vendor JS file location.
     * @return path|null
     * @throws coding_exception
     */
    private function resolve_vendor_js(): ?path {
        $this->ensure_map_generated();
        foreach (self::get_js_suffix_for_file() as $suffix) {
            if (isset($this->map_vendors_js[$suffix])) {
                return $this->map_vendors_js[$suffix];
            }
        }
        // Presently it is not possible to get here, as the vendor file is always present.
        return null;
    }

    /**
     * Resolves the SCSS file that should be imported, given the bundle name, and the import path.
     * @param string $bundle
     * @param string $importpath
     * @return path|null Absolute path to the file to import, or null if cannot be resolved.
     */
    private function resolve_style_import(string $bundle, string $importpath): ?path {
        $this->ensure_map_generated();
        foreach (self::get_css_suffix_for_file() as $suffix) {
            if (isset($this->map_scss_imports[$bundle][$suffix][$importpath])) {
                return $this->map_scss_imports[$bundle][$suffix][$importpath];
            }
        }
        return null;
    }

    /**
     * Resolves the css_variables json file.
     * @param string $bundle
     * @return path|null The path to the variables JSON file, or null if the component does not have one.
     */
    private function resolve_bundle_css_variables_json(string $bundle): ?path {
        $this->ensure_map_generated();
        if (isset($this->map_bundle_css_variables_json[$bundle])) {
            return $this->map_bundle_css_variables_json[$bundle];
        }
        return null;
    }

    /**
     * Return the given bundles dependencies.
     * @param string $bundle
     * @return string[] 0..n bundle names that the given bundle is dependent upon.
     */
    private function resolve_bundle_dependencies(string $bundle): array {
        $this->ensure_map_generated();
        if (isset($this->map_bundle_dependencies[$bundle])) {
            return $this->map_bundle_dependencies[$bundle];
        }
        return [];
    }

    /**
     * Returns the suffix that should be used in the URL when requesting JS for a bundle.
     * @return string
     */
    public static function get_js_suffix_for_url(): string {
        $suffixes = self::get_js_suffix_for_file();
        $suffix = reset($suffixes);
        return self::$map_file_to_param[$suffix];
    }

    /**
     * Return the suffixes that should be checked when resolving the JS file to serve for a bundle.
     * Multiple suffixes are supported to ensure that if the preferred suffix is not available then a suitable alternative
     * is provided.
     * @return string[]
     */
    private static function get_js_suffix_for_file(): array {
        $suffixes = [];
        $development = self::is_javascript_development();
        $legacy = \core_useragent::is_totara_legacy_browser();

        if ($legacy) {
            if ($development) {
                $suffixes[] = self::SUFFIX_DEVELOPMENT_LEGACY;
            }
            $suffixes[] = self::SUFFIX_PRODUCTION_LEGACY;
            return $suffixes;
        }

        if ($development) {
            $suffixes[] = self::SUFFIX_DEVELOPMENT;
        }
        $suffixes[] = self::SUFFIX_PRODUCTION;

        return $suffixes;
    }

    /**
     * Returns the suffix that should be used in the URL when requesting SCSS for a bundle.
     * @return string
     */
    public static function get_css_suffix_for_url(): string {
        if (\core_useragent::is_totara_legacy_browser()) {
            if (self::is_css_development()) {
                return self::$map_file_to_param[self::SUFFIX_DEVELOPMENT_LEGACY];
            }
            return self::$map_file_to_param[self::SUFFIX_PRODUCTION_LEGACY];
        }
        if (self::is_css_development()) {
            return self::$map_file_to_param[self::SUFFIX_DEVELOPMENT];
        }
        return self::$map_file_to_param[self::SUFFIX_PRODUCTION];
    }

    /**
     * Return the suffixes that should be checked when resolving the SCSS file to serve for a bundle.
     * Multiple suffixes are supported to ensure that if the preferred suffix is not available then a suitable alternative
     * is provided.
     * @param bool $preferred_only
     * @return string[]
     */
    private static function get_css_suffix_for_file(bool $preferred_only = false): array {
        $suffixes = [];
        if (self::is_css_development()) {
            $suffixes[] = self::SUFFIX_DEVELOPMENT;
        }
        $suffixes[] = self::SUFFIX_PRODUCTION;

        if ($preferred_only) {
            return reset($suffixes);
        }

        return $suffixes;
    }

    /**
     * Determine the correct JS Revision to use for this load.
     *
     * @return int the revision number to use.
     */
    public static function get_js_rev() {
        global $CFG;
        if (during_initial_install()) {
            return -1;
        }
        if (empty(get_config('totara_tui', 'cache_js') )) {
            return -1;
        }
        if (!empty($CFG->jsrev)) {
            return $CFG->jsrev;
        }
        return 1;
    }

    /**
     * Determine the correct CSS revision to use for this load.
     * @return int
     */
    public static function get_css_rev() {
        if (during_initial_install()) {
            return -1;
        }
        if (empty(get_config('totara_tui', 'cache_scss'))) {
            return -1;
        }
        return theme_get_revision();
    }

     /**
      * Returns true if Tui is in JS development mode.
      * @return bool
      */
     private static function is_javascript_development(): bool {
        if (during_initial_install()) {
            // Impossible to get here during PHPUnit tests.
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        return (bool)get_config('totara_tui', 'development_mode');
    }

     /**
      * Returns true if Tui is in CSS development mode.
      * @return bool
      */
     private static function is_css_development(): bool {
        if (during_initial_install()) {
            // Impossible to get here during PHPUnit tests.
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        return (bool)get_config('totara_tui', 'development_mode');
    }

    /**
     * Checks to make sure the map has been generated, and if not triggers its generation.
     */
    private function ensure_map_generated() {
        global $CFG;

        if ($this->map_initialised) {
            return;
        }
        $this->map_initialised = true;

        $bundles = [];
        $suffixes = [];
        $suffixes_imports = [

        ];
        foreach (array_keys(self::$map_file_to_param) as $suffix) {
            $suffixes[$suffix] = null;
            $suffixes_imports[$suffix] = [];
        }

        $directory = new path($CFG->srcroot, self::DIR_BUNDLE);
        if (!$directory->is_readable() || !$directory->is_directory()) {
            throw new coding_exception('Unable to read bundle directory');
        }
        $directory_iterator = $directory->create_directory_iterator();
        foreach ($directory_iterator as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDot() || !$file->isDir()) {
                continue;
            }
            $bundles[] = $file->getFilename();
        }

        foreach ($bundles as $bundle) {
            $this->map_bundle_js[$bundle] = $suffixes;
            $this->map_bundle_scss[$bundle] = $suffixes;
            $this->map_scss_imports[$bundle] = $suffixes_imports;
            $this->map_bundle_css_variables_json[$bundle] = $directory->join($bundle, 'build', "css_variables.json");
            $this->map_bundle_dependencies[$bundle] = [];
            $this->map_bundle($bundle);
        }
    }

    /**
     * Populates the map properties for the given bundle.
     * @param string $bundle
     */
    private function map_bundle(string $bundle) {
        $imports = [];
        foreach (array_keys(self::$map_file_to_param) as $suffix) {
            $imports[$suffix] = [];
        }

        $directory_build = self::anticipated_bundle_location($bundle);
        if (!$directory_build->exists() || !$directory_build->is_readable()) {
            return;
        }
        $iterator = $this->get_tui_build_directory_iterator($directory_build);
        $length_directory_styles = strlen($directory_build->out(true) . DIRECTORY_SEPARATOR);

        foreach ($iterator as $fileinfo) {
            $file_absolute = $fileinfo[0];
            $file_extension = $fileinfo['extension'];
            $file_suffix = $fileinfo['suffix'];
            $file_relative = substr($file_absolute, $length_directory_styles, -(strlen($file_suffix . $file_extension))) . $file_extension;

            switch ($file_extension) {
                case '.scss':
                    $this->add_bundle_scss_file_to_map($bundle, $file_suffix, $file_relative, $file_absolute);
                    break;
                case '.js':
                    $this->add_bundle_js_file_to_map($bundle, $file_suffix, $file_relative, $file_absolute);
                    break;
                case '.json':
                    $this->add_bundle_json_file_to_map($bundle, $file_suffix, $file_relative, $file_absolute);
                    break;
            }
        }
    }

    /**
     * Returns an Iterator that is designed to iterate all Tui related files within a given directory.
     * @param path $directory
     * @return RegexIterator
     */
    private function get_tui_build_directory_iterator(path $directory): RegexIterator {
        $iterator = new RegexIterator(
            new RecursiveIteratorIterator(
                $directory->create_recursive_directory_iterator(),
                RecursiveIteratorIterator::SELF_FIRST
            ),
            '/^.+?(?<suffix>(\.legacy)?(\.development)?)?(?<extension>\.(scss|js|json))$/',
            RecursiveRegexIterator::GET_MATCH
        );
        return $iterator;
    }

    /**
     * Adds a given JS file into the map in the right place.
     * @param string $bundle
     * @param string $suffix
     * @param string $path_relative
     * @param string $path_absolute
     */
    private function add_bundle_js_file_to_map(string $bundle, string $suffix, string $path_relative, string $path_absolute): void {
        $path_absolute_path = new path($path_absolute);
        if ($bundle === framework::COMPONENT && $path_relative === 'vendors.js') {
            $this->map_vendors_js[$suffix] = $path_absolute_path;
        } else {
            $this->map_bundle_js[$bundle][$suffix] = $path_absolute_path;
        }
    }

    /**
     * Adds a given SCSS file into the map in the right place.
     * @param string $bundle
     * @param string $suffix
     * @param string $path_relative
     * @param string $path_absolute
     */
    private function add_bundle_scss_file_to_map(string $bundle, string $suffix, string $path_relative, string $path_absolute) {
        $relative = (new path($path_relative))->get_relative('global_styles', true);
        $path_absolute_path = new path($path_absolute);
        if ($relative !== null) {
            $this->map_scss_imports[$bundle][$suffix][$relative->out()] = $path_absolute_path;
        } else if ($path_relative === 'tui_bundle.scss') {
            $this->map_bundle_scss[$bundle][$suffix] = $path_absolute_path;
        } else {
            debugging('Unable to map SCSS file ' . $path_absolute, DEBUG_DEVELOPER);
        }
    }

    /**
     * Adds a given JSON file into the map in the right place.
     * @param string $bundle
     * @param string $suffix
     * @param string $path_relative
     * @param string $path_absolute
     */
    private function add_bundle_json_file_to_map(string $bundle, string $suffix, string $path_relative, string $path_absolute) {
        if ($path_relative === 'dependencies.json') {
            $this->map_bundle_dependencies[$bundle] = [];
            $json = file_get_contents($path_absolute);
            $data = @json_decode($json);
            if (empty($data) || empty($data->dependencies)) {
                return;
            }
            foreach ($data->dependencies as $dependency) {
                $this->map_bundle_dependencies[$bundle][] = $dependency->name;
            }
        }
    }

    /**
     * Returns true if any of the given bundles have any resources.
     * @param string[] $bundles
     * @return bool
     */
    public static function any_have_resources(array $bundles): bool {
        $instance = bundle::instance();
        $instance->ensure_map_generated();
        foreach ($bundles as $bundle) {
            if (!is_string($bundle)) {
                continue;
            }
            if (!empty($instance->map_bundle_scss[$bundle])) {
                return true;
            }
            if (!empty($instance->map_bundle_js[$bundle])) {
                return true;
            }
        }
        return false;
    }
}
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

namespace totara_tui\local\mediation;

/**
 * Mediator helper class.
 */
final class helper {

    /**
     * Validate the given theme name appears to match a valid theme.
     * @param string $themename
     * @return bool
     */
    public static function validate_theme_name(string $themename) {
        global $CFG;

        if (!self::validate_theme_component_name($themename)) {
            // Definitely not valid.
            return false;
        }
        if (file_exists("{$CFG->dirroot}/theme/{$themename}/config.php")) {
            // The theme exists in standard location - ok.
            return true;
        } else if (!empty($CFG->themedir) and file_exists("{$CFG->themedir}/{$themename}/config.php")) {
            // Alternative theme location contains this theme - ok.
            return true;
        }
        return false;
    }

    /**
     * Gets script arguments from either slashargs or from params.
     * @param array $opts
     * @return array
     */
    public static function get_args(array $opts): array {
        $args = [];

        $slasharguments = \min_get_slash_argument();
        if ($slasharguments) {
            $slasharguments = ltrim($slasharguments, '/');
            $slashargument_parts = explode('/', $slasharguments);
            if (count($slashargument_parts) !== count($opts)) {
                mediator::send_not_found();
            }
            foreach ($opts as $optname => $type) {
                $arg = array_shift($slashargument_parts);
                $args[] = \min_clean_param($arg, $type);
            }
        } else {
            foreach ($opts as $optname => $type) {
                $args[] = \min_optional_param($optname, null, $type);
            }
        }
        return $args;
    }

    /**
     * Confirms the theme name matches the standard component naming structure.
     * Does not do any further checks (such as to see if the theme exists or not).
     *
     * @param string $theme_name
     * @return bool
     */
    private static function validate_theme_component_name(string $theme_name): bool {
        $component_name = 'theme_' . $theme_name;

        // Validate against the clean_param(PARAM_COMPONENT) filter without explicitly loading it
        if (!preg_match('/^[a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+$/', $component_name)) {
            return false;
        }
        if (strpos($component_name, '__') !== false) {
            return false;
        }

        return true;
    }
}
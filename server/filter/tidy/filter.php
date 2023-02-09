<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * HTML  tidy text filter.
 *
 * @deprecated Since Totara 13.0
 *
 * @package    filter
 * @subpackage tiny
 * @copyright  2004 Hannes Gassert <hannes at mediagonal dot ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This class looks for text including markup and
// applies tidy's repair function to it.
// Tidy is a HTML clean and
// repair utility, which is currently available for PHP 4.3.x and PHP 5 as a
// PECL extension from http://pecl.php.net/package/tidy, in PHP 5 you need only
// to compile using the --with-tidy option.
// If you don't have the tidy extension installed or don't know, you can enable
// or disable this filter, it just won't have any effect.
// If you want to know what you can set in $tidyoptions and what their default
// values are, see http://php.net/manual/en/function.tidy-get-config.php.

class filter_tidy extends moodle_text_filter {
    function filter($text, array $options = array()) {

    /// Configuration for tidy. Feel free to tune for your needs, e.g. to allow
    /// proprietary markup.
        $tidyoptions = array(
                 'output-xhtml' => true,
                 'show-body-only' => true,
                 'tidy-mark' => false,
                 'drop-proprietary-attributes' => true,
                 'drop-font-tags' => true,
                 'drop-empty-paras' => true,
                 'indent' => true,
                 'quiet' => true,
        );

    /// Do a quick check using strpos to avoid unnecessary work
        if (strpos($text, '<') === false) {
            return $text;
        }

        if (PHPUNIT_TEST) {
            // Turn off settings that will cause tidy to pretty print and wrap the HTML.
            // We don't want to have to compensate for pretty print in all unit tests.
            $tidyoptions['wrap'] = 0; // Don't wrap lines
            $tidyoptions['wrap-attributes'] = 0; // Don't wrap attributes across lines
            $tidyoptions['vertical-space'] = 0; // Don't add extraneous whitespace for readability.
            $tidyoptions['indent'] = 0; // Don't intend any thing.
            $tidyoptions['newline'] = 'LF'; // Normalise newlines as well.
        }

    /// If enabled: run tidy over the entire string
        if (function_exists('tidy_repair_string')){
            // TOTARA: we suppress warnings from this function. Not all arguments exist in all versions of Tidy, and
            // consequently trying to set a non-existent argument leads to a warning being generated.
            $text = @tidy_repair_string($text, $tidyoptions, 'utf8');
        }

        return $text;
    }

    /**
     * Returns true is text can be cleaned using clean text AFTER having been filtered.
     *
     * If false is returned then this filter must be run after clean text has been run.
     * If null is returned then the filter has not yet been updated by a developer to answer the question.
     * This should be done as a priority.
     *
     * @since Totara 13.0
     * @return bool
     */
    protected static function is_compatible_with_clean_text() {
        return true; // No additions, just tidied.
    }
}


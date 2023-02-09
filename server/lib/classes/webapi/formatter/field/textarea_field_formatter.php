<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\formatter\field;

use context;
use core\format;

/**
 * Formats a given string by running it through format_string(), preserves whitespace and newlines.
 */
class textarea_field_formatter extends base {

    /**
     * Existing options and their defaults
     *
     * @var array
     */
    protected $options = [
        'context' => null,
        'strip_tags' => false,
        'filter' => true,
        'additional_options' => []
    ];

    /**
     * @inheritDoc
     */
    protected function validate_format(): bool {
        // As the valid formats are global formats shared
        // we explicitly check for them
        $valid_formats = [
            format::FORMAT_RAW,
            format::FORMAT_HTML,
            format::FORMAT_PLAIN
        ];

        // Do a custom check for valid but (currently) unsupported formats for this formatter,
        // anything else should go through to base for the generic exception.
        $unsupported_formats = array_diff(format::get_available(), $valid_formats);
        if (in_array($this->format, $unsupported_formats)) {
             throw new \coding_exception($this->format . ' format is currently not supported by the textarea formatter.');
        }

        return in_array($this->format, $valid_formats);
    }

    /**
     * Set strip_tags parameter for format_string, defaults to false
     *
     * @param bool $strip_tags
     * @return $this
     */
    public function set_strip_tags(bool $strip_tags) {
        $this->options['strip_tags'] = $strip_tags;
        return $this;
    }

    /**
     * Set optional context to pass on to format_string.
     * By default the context passed through the constructor is used
     *
     * @param context|null $context
     * @return $this
     */
    public function set_context(?context $context) {
        $this->options['context'] = $context;
        return $this;
    }

    /**
     * Set filter parameter for format_string, defaults to true
     *
     * @param bool $filter
     * @return $this
     */
    public function set_filter(bool $filter) {
        $this->options['filter'] = $filter;
        return $this;
    }

    /**
     * Set additional options to be passed to format_string(),
     * see options parameter of format_string for available options
     *
     * @param array $options
     * @return $this
     */
    public function set_additional_options(array $options) {
        $this->options['additional_options'] = $options;
        return $this;
    }

    /**
     * Leave it as it is
     *
     * @param string $value
     * @return string
     */
    protected function format_raw(string $value): string {
        return $value;
    }

    /**
     * Passes value through format_string() with configured options.
     *
     * @param string $value
     * @return string
     */
    private function format_string(string $value): string {
        $context = $this->options['context'] ?? $this->context;
        $strip_tags = $this->options['strip_tags'] ?? false;
        $filter = $this->options['filter'] ?? true;

        // Merge any additional options
        $format_options = array_merge($this->options['additional_options'], ['context' => $context, 'filter' => $filter]);

        return format_string($value, $strip_tags, $format_options);
    }

    /**
     * Formats the string using format_string(), then preserves whitespace using HTML <br> tags and non-breaking spaces.
     *
     * @param $value
     * @return string
     */
    protected function format_html(string $value): string {
        $value = $this->format_string($value);
        $value = nl2br($value);
        return str_replace('  ', '&nbsp; ', $value);
    }

    /**
     * Formats the string using format_string() and html_to_text() on top of it
     *
     * @param $value
     * @return string
     */
    protected function format_plain(string $value): string {
        global $CFG;

        require_once($CFG->libdir .'/html2text/lib.php');
        $options = [
            'width'     => 0,
            'do_links'  => 'none',
        ];

        // Use html2text to deal with filter outputs. Filters can and will inject HTML.
        $h2t = new \core_html2text($this->format_html($value), $options);
        $value = $h2t->getText();

        // Replace any unicode non-breaking spaces (that were added by format_html) with spaces.
        $value = str_replace("\u{00A0} ", '  ', $value);
        return $value;
    }
}

<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\formatter\field;

use coding_exception;
use context;
use core\format;
use core\json_editor\helper\document_helper;
use core\json_editor\json_editor;

/**
 * Formats a given text by running it through format_text()
 *
 * For the HTML and PLAIN format you need to set the pluginfile_url_options using set_pluginfile_url_options()
 * otherwise an exception is thrown.
 *
 * The following shows an example on how to use this field formatter within a formatter:
 *
 * protected function get_map(): array {
 *     return [
 *         'textfield' => function ($value, text_field_formatter $formatter) {
 *             // The pluginfile url rewrite might need a different context
 *             // than the formatter, make sure you use the right one.
 *             $context = ...;
 *             $formatter->set_pluginfile_url_options($context, 'component', 'filearea', $this->object->id);
 *             return $formatter->format($value);
 *         }
 *     ];
 *  }
 */
class text_field_formatter extends base {

    /**
     * By default pluginfile urls are rewritten automatically.
     * You need to set the options for it using set_pluginfile_url_options()
     *
     * @var bool
     */
    protected $pluginfile_url_rewrite_enabled = true;

    /**
     * Existing options and their defaults
     * @var array
     */
    protected $options = [
        // This is the format the field was saved in
        // not to be confused with the output
        // format passed to the constructor
        'text_format' => null,
        'additional_options' => [],
        // These are the options needed to rewrite the pluginfile urls
        'pluginfile_url_options' => [
            'pluginfile' => null,
            'context' => null,
            'component' => null,
            'filearea' => null,
            'itemid' => null,
            'options' => null
        ],
    ];

    protected function validate_format(): bool {
        // As the valid formats are global formats shared
        // we explicitly check for them
        $valid_formats = [
            format::FORMAT_RAW,
            format::FORMAT_HTML,
            format::FORMAT_PLAIN,
            format::FORMAT_MOBILE
        ];

        // Do a custom check for valid but (currently) unsupported formats for this formatter,
        // anything else should go through to base for the generic exception.
        $unsupported_formats = array_diff(format::get_available(), $valid_formats);
        if (in_array($this->format, $unsupported_formats)) {
            throw new \coding_exception($this->format . ' format is currently not supported by the text formatter.');
        }

        return in_array($this->format, $valid_formats);
    }

    /**
     * This is the format the field was saved in
     * not to be confused with the output format passed to the constructor
     * FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, etc. see format_text for available options
     *
     * @param int|null $format
     * @return $this
     */
    public function set_text_format(?int $format) {
        $this->options['text_format'] = $format;
        return $this;
    }

    /**
     * Additional options for format_text(), see format_text() for more details.
     *
     * @param array $options
     * @return $this
     */
    public function set_additional_options(array $options) {
        $this->options['additional_options'] = $options;
        return $this;
    }

    /**
     * Set options which are required for file_rewrite_pluginfile_urls() to work
     *
     * @param context $context
     * @param string $component
     * @param string $filearea
     * @param int|null $itemid
     * @param array|null $options see file_rewrite_pluginfile_urls() for available options
     * @param string|null $pluginfile defaults to 'pluginfile.php'
     * @return $this
     */
    public function set_pluginfile_url_options(context $context, string $component, string $filearea, ?int $itemid = null, ?string $pluginfile = null, ?array $options = null) {
        $this->options['pluginfile_url_options'] = [
            'pluginfile' => $pluginfile ?? 'pluginfile.php',
            'context' => $context,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $itemid,
            'options' => $options
        ];

        return $this;
    }

    /**
     * Disable the pluginfile_url rewrite, making it possible to use the formatter without specifying the pluginfile_url_options
     * This is useful if you do not have a specific filearea for the field you are formatting
     *
     * @return $this
     */
    public function disabled_pluginfile_url_rewrite() {
        $this->pluginfile_url_rewrite_enabled = false;

        return $this;
    }

    /**
     * Enable the pluginfile_url rewrite. This is the default behaviour resulting in having to specify the pluginfile_url_options.
     *
     * @return $this
     */
    public function enable_pluginfile_url_rewrite() {
        $this->pluginfile_url_rewrite_enabled = true;

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
     * Format the text with format_text()
     *
     * @param string $value
     * @return string
     */
    protected function format_html(string $value): string {
        if ($this->pluginfile_url_rewrite_enabled === true) {
            $value = $this->rewrite_urls($value);
        }

        // Set the default format of the text according to given format
        $text_format = $this->options['text_format'] ?? FORMAT_HTML;

        $format_options = array_merge($this->options['additional_options'], ['context' => $this->context]);

        return format_text($value, $text_format, $format_options);
    }

    /**
     * Rewrite pluginfile urls
     *
     * @param string $value
     * @return string
     */
    private function rewrite_urls(string $value): string {
        $pluginfile_url_options = $this->options['pluginfile_url_options'];

        // We need all those options to rewrite the file urls
        $expected_options = ['context', 'component', 'filearea'];
        foreach ($expected_options as $option) {
            if (empty($pluginfile_url_options[$option])) {
                throw new coding_exception('You must provide the pluginfile url options via set_pluginfile_url_options()');
            }
        }

        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $value = file_rewrite_pluginfile_urls(
            $value,
            $pluginfile_url_options['pluginfile'],
            $pluginfile_url_options['context']->id,
            $pluginfile_url_options['component'],
            $pluginfile_url_options['filearea'],
            $pluginfile_url_options['itemid'],
            $pluginfile_url_options['options']
        );

        return $value;
    }

    /**
     * Format the text with format_text() and html_to_text() on top of it
     *
     * @param string $value
     * @return string
     */
    protected function format_plain(string $value): string {
        global $CFG;

        require_once($CFG->libdir .'/html2text/lib.php');
        $options = [
            'width' => 0,
            'do_links' => 'inline',
        ];

        $h2t = new \core_html2text($this->format_html($value), $options);
        return $h2t->getText();
    }

    /**
     * Format the text with format_text() and html_to_text() on top of it
     *
     * @param string $value
     * @return string
     */
    protected function format_mobile(string $value): string {
        global $CFG;

        if (document_helper::is_valid_json_document($value)) {
            if ($this->pluginfile_url_rewrite_enabled === true) {
                $value = $this->rewrite_urls($value);
                $editor = json_editor::create(null);
                $value = $editor->filter_json_content($value, $this->context);
            }
            return $value;
        } else {
            require_once($CFG->libdir . '/html2text/lib.php');
            $options = [
                'width' => 0,
                'do_links' => 'inline',
            ];

            $h2t = new \core_html2text($this->format_html($value), $options);
            return $h2t->getText();
        }
    }

}

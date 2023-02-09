<?php
/**
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package core
 */
namespace core\json_editor;

use core\json_editor\formatter\default_formatter;
use core\json_editor\formatter\formatter;
use core\json_editor\formatter\formatter_factory;
use core\json_editor\helper\document_helper;

/**
 * An editor to parse the json content into a human readable content.
 */
final class json_editor {
    /**
     * @var formatter Cached formatter.
     */
    private $formatter;

    /**
     * json_editor constructor.
     * @param formatter     $formatter
     */
    public function __construct(formatter $formatter) {
        $this->formatter = $formatter;
    }

    /**
     * @return json_editor
     */
    public static function default(): json_editor {
        return static::create(null);
    }

    /**
     * Create an instance of json_editor with the given $formatter_component.
     * Will use default formatter if formatter component is not found.
     *
     * @param string|null $formatter_component
     * @return json_editor
     */
    public static function create(?string $formatter_component): json_editor {
        $formatter = formatter_factory::create_formatter($formatter_component);
        return new static($formatter);
    }

    /**
     * Convert a document to HTML.
     *
     * @param \stdClass|array|string $json JSON document.
     * @return string HTML string.
     */
    public function to_html($json): string {
        $document = document_helper::parse_document($json);
        if (empty($document)) {
            // Cannot parse the json content into a proper document format.
            return '';
        }

        return $this->formatter->to_html($document);
    }

    /**
     * Filtering json content, where we are applying all the filter plugin(s) from
     * filter manager.
     *
     * @param string|\stdClass|array    $json_content
     * @param \context                  $context
     * @return string
     */
    public function filter_json_content($json_content, \context $context): string {
        $document = document_helper::parse_document($json_content);
        if (empty($document)) {
            // Empty or invalid document. We will skip it.
            return '';
        }

        $json_text = document_helper::json_encode_document($document);

        $manager = \filter_manager::instance();
        return $manager->filter_json($json_text, $context);
    }

    /**
     * Convert a document to text.
     *
     * @param object|string JSON document.
     * @return string Formatted text.
     */
    public function to_text($json): string {
        $document = document_helper::parse_document($json);

        if (empty($document)) {
            // Cannot parse the json content into a proper document format.
            return '';
        }

        return $this->formatter->to_text($document);
    }

    /**
     * To tell whether we will filter the HTML text using the old way.
     * Only run thru the filter if the formatter is a default formatter.
     *
     * @return bool
     */
    public function use_legacy_filter_text(): bool {
        return ($this->formatter instanceof default_formatter);
    }
}

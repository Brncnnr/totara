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

namespace core;

use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * This class defines all basic formats related to strings/text
 */
class format implements format_interface {

    // Ths following constants also map to the graphql core_format enums
    public const FORMAT_RAW = 'RAW';
    public const FORMAT_HTML = 'HTML';
    public const FORMAT_PLAIN = 'PLAIN';
    public const FORMAT_MARKDOWN = 'MARKDOWN';
    public const FORMAT_JSON_EDITOR = 'JSON_EDITOR';
    public const FORMAT_MOBILE = 'MOBILE';

    public static function is_defined(string $format): bool {
        return defined('self::FORMAT_'.strtoupper($format));
    }

    public static function get_available(): array {
        return [
            self::FORMAT_RAW,
            self::FORMAT_HTML,
            self::FORMAT_PLAIN,
            self::FORMAT_MARKDOWN,
            self::FORMAT_JSON_EDITOR,
            self::FORMAT_MOBILE
        ];
    }

    /**
     * Transform the format field from a core_format string to the Moodle constant.
     *
     * @param string $format
     * @return int
     */
    public static function get_moodle_format(string $format): int {
        switch ($format) {
            case self::FORMAT_HTML:
                return FORMAT_HTML;
            case self::FORMAT_PLAIN:
            case self::FORMAT_RAW:
                return FORMAT_PLAIN;
            case self::FORMAT_MARKDOWN:
                return FORMAT_MARKDOWN;
            case self::FORMAT_JSON_EDITOR:
            case self::FORMAT_MOBILE:
                return FORMAT_JSON_EDITOR;
            default:
                throw new coding_exception("Unrecognised format: {$format}");
        }
    }

    /**
     * Transform the format field from the constants to a core_format string.
     *
     * @param int $moodle_format
     * @return string
     */
    public static function from_moodle(int $moodle_format): string {
        switch ($moodle_format) {
            case FORMAT_MOODLE:
            case FORMAT_HTML:
                return self::FORMAT_HTML;
            case FORMAT_PLAIN:
                return self::FORMAT_PLAIN;
            case FORMAT_MARKDOWN:
                return self::FORMAT_MARKDOWN;
            case FORMAT_JSON_EDITOR:
                return self::FORMAT_JSON_EDITOR;
            default:
                // Note: There is also FORMAT_WIKI but it has been deprecated since 2005.
                throw new coding_exception("Unrecognised format: {$moodle_format}");
        }
    }
}

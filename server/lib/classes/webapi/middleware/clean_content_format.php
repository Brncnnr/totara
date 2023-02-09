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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\webapi\middleware;

use Closure;
use coding_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * This middleware is to clean the content format value field - which is checking whether the value is
 * matching with any of these options.
 *
 * + @see FORMAT_PLAIN
 * + @see FORMAT_JSON_EDITOR
 * + @see FORMAT_HTML
 * + @see FORMAT_MOODLE
 * + @see FORMAT_MARKDOWN
 */
final class clean_content_format implements middleware {
    /**
     * The default system provided formats.
     *
     * @var int[]
     */
    public const SYSTEM_DEFAULT_FORMATS = [
        FORMAT_MOODLE,
        FORMAT_HTML,
        FORMAT_PLAIN,
        FORMAT_MARKDOWN,
        FORMAT_JSON_EDITOR,
    ];

    /**
     * Format key that associated with the format value within the payload.
     *
     * @var string
     */
    private $format_field_key;

    /**
     * The default format value that we want to set, when the payload does not have the field {$format_field_key}.
     * If the value is null then we will not set it.
     *
     * @var int|null
     */
    private $default_value;

    /**
     * The array of restricted formats that we want the value to be locked down to.
     * By default it will be set to {@see clean_content_format::SYSTEM_DEFAULT_FORMATS}
     *
     * @var array
     */
    private $restricted_formats;

    /**
     * Whether the field is required or not.
     *
     * @var bool
     */
    private $field_required;

    /**
     * clean_content_format constructor.
     *
     * @param string   $format_field_key
     * @param int|null $default_value
     * @param array    $restricted_formats
     * @param bool     $field_required
     */
    public function __construct(string $format_field_key, ?int $default_value = null,
                                array $restricted_formats = self::SYSTEM_DEFAULT_FORMATS,
                                bool $field_required = false) {
        $this->format_field_key = $format_field_key;
        $this->field_required = $field_required;

        foreach ($restricted_formats as $restricted_format) {
            if (!in_array($restricted_format, self::SYSTEM_DEFAULT_FORMATS)) {
                throw new coding_exception(
                    "One of the restricted format does not exist in system provided formats",
                    $restricted_format
                );
            }
        }

        $this->restricted_formats = $restricted_formats;

        if (null !== $default_value && !in_array($default_value, self::SYSTEM_DEFAULT_FORMATS)) {
            throw new coding_exception("Default value '{$default_value}' is invalid");
        }

        $this->default_value = $default_value;
    }

    /**
     * The tasks that this middleware are doing:
     * + Set the default value for format value if it is not appearing in the payload.
     * + Validate the format value from the payload against the system provided format.
     * + Validate the format value from the payload against the restricted formats.
     *
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        if (!$payload->has_variable($this->format_field_key)) {
            if ($this->field_required) {
                throw new coding_exception("Missing field {$this->format_field_key} in the payload");
            }

            if (null !== $this->default_value) {
                $payload->set_variable($this->format_field_key, $this->default_value);
            }

            return $next($payload);
        }

        $format_value = $payload->get_variable($this->format_field_key);
        if (null === $format_value) {
            if (null !== $this->default_value) {
                $payload->set_variable($this->format_field_key, $this->default_value);
                return $next($payload);
            }

            // $format_value is null.
            return $next($payload);
        }

        // Checking if its exist in the restricted formats or not.
        if (!in_array($format_value, $this->restricted_formats)) {
            throw new coding_exception("The format value is invalid");
        }

        return $next($payload);
    }
}
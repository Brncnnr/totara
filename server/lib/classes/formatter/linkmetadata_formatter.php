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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package core
 */
namespace core\formatter;

use context;
use core\link\metadata_info;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

/**
 * Class linkmetadata_formatter
 * @package core\formatter
 */
final class linkmetadata_formatter extends formatter {
    /**
     * linkmetadata_formatter constructor.
     * @param metadata_info     $info
     * @param context           $context
     */
    public function __construct(metadata_info $info, context $context) {
        $record = new \stdClass();
        $record->title = $info->get_title();
        $record->description = $info->get_description();
        $record->url = $info->get_url();
        $record->image = $info->get_image();
        $record->videoheight = $info->get_video_height();
        $record->videowidth = $info->get_video_width();

        parent::__construct($record, $context);
    }

    /**
     * @param $value
     * @return string|null
     */
    public static function format_url($value): ?string {
        if ($value instanceof \moodle_url) {
            $value = $value->out();
        } else if (null !== $value && !is_string($value)) {
            debugging('Invalid value', DEBUG_DEVELOPER);
            return null;
        }

        $value = clean_param($value, PARAM_URL);
        if (empty($value)) {
            return null;
        }

        return (new \moodle_url($value))->out();
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'title' => function (?string $value, string_field_formatter $formatter): ?string {
                if (null === $value) {
                    return null;
                }

                $formatter->set_strip_tags(true);
                return $formatter->format($value);
            },
            'description' => function (?string $value, string_field_formatter $formatter): ?string {
                if (null === $value) {
                    return null;
                }

                $formatter->set_strip_tags(true);
                return $formatter->format($value);
            },
            'url' => 'format_url',
            'image' => 'format_url',
            'videoheight' => null,
            'videowidth' => null,
        ];
    }
}
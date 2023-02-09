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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\formatter\file;

use container_workspace\file\file;
use container_workspace\file\file_type;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

/**
 * Class file_formatter
 * @package container_workspace\formatter\file
 */
final class file_formatter extends formatter {
    /**
     * file_formatter constructor.
     * @param file $file
     * @param \context $context
     */
    public function __construct(file $file, \context $context) {
        $record = new \stdClass();

        $record->id = $file->get_id();
        $record->file_name = $file->get_filename();
        $record->file_size = $file->get_filesize();
        $record->extension = $file->get_extension();
        $record->time_created = $file->get_time_created();
        $record->time_modified = $file->get_time_modified();
        $record->alt_text = $file->get_alt_text();

        $record->download_url = $file->get_file_url(true);
        $record->context_url = $file->get_context_url();
        $record->mimetype = $file->get_mimetype();
        $record->file_type = file_type::get_code(strtolower($file->get_extension()));
        $record->file_url = $file->get_file_url_without_download();

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('date' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('date' === $field) {
            return parent::get_field('time_created');
        }

        return parent::get_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;

        return [
            'id' => null,
            'alt_text' => null,
            'file_name' => function (string $filename, string_field_formatter $formatter): string {
                $filename = clean_filename($filename);
                return $formatter->format($filename);
            },
            'file_size' => function (int $file_size, string_field_formatter $formatter): string {
                $str = display_size($file_size);
                return $formatter->format($str);
            },
            'extension' => string_field_formatter::class,
            'date' => function (int $value, date_field_formatter $format) use ($that): string {
                $time_modified = $that->object->time_modified;
                if ($value <= $time_modified) {
                    return $format->format($time_modified);
                }

                return $format->format($value);
            },
            'file_url' => function (\moodle_url $url): string {
                return $url->out();
            },
            'download_url' => function (\moodle_url $url): string {
                return $url->out(false);
            },
            'context_url' => function (\moodle_url $url): string {
                return $url->out();
            },
            'mimetype' => null,
            'file_type' => null,
        ];
    }
}
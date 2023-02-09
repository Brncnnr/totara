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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use totara_engage\formatter\field\date_field_formatter;
use totara_engage\access\access;
use totara_playlist\playlist;

/**
 * Formatter for playlist
 */
final class playlist_formatter extends formatter {
    /**
     * playlist_formatter constructor.
     * @param playlist  $playlist
     */
    public function __construct(playlist $playlist) {
        $record = new \stdClass();
        $context = $playlist->get_context();

        $record->id = $playlist->get_id();
        $record->name = $playlist->get_name(false);
        $record->summary = $playlist->get_summary();
        $record->access = $playlist->get_access();
        $record->contextid = $context->id;
        $record->timecreated = $playlist->get_timecreated();
        $record->timemodified = $playlist->get_timemodified();
        $record->summaryformat = $playlist->get_summaryformat();

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('timedescription' === $field) {
            return parent::get_field('timecreated');
        }

        return parent::get_field($field);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('timedescription' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        $that = $this;

        return [
            'id' => null,
            'name' => string_field_formatter::class,
            'summaryformat' => null,
            'summary' => function (?string $value, text_field_formatter $formatter) use ($that): string {
                if (null === $value || '' === $value) {
                    return '';
                }

                $formatter->set_text_format($that->object->summaryformat);
                $formatter->disabled_pluginfile_url_rewrite();
                $formatter->set_additional_options(['formatter' => 'totara_tui']);

                return $formatter->format($value);
            },
            'access' => function (int $value): string {
                return access::get_code($value);
            },
            'contextid' => null,

            'timedescription' => function (int $value, date_field_formatter $formatter) use ($that): string {
                if (null !== $that->object->timemodified && 0 !== $that->object->timemodified) {
                    $formatter->set_timemodified($that->object->timemodified);
                }

                return $formatter->format($value);
            }
        ];
    }
}
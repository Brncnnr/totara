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
 * @package container_workspace
 */
namespace container_workspace\formatter\workspace;

use container_workspace\workspace;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter as base_formatter;
use totara_engage\formatter\field\date_field_formatter;

/**
 * Class workspace_formatter
 * @package container_workspace\formatter
 */
final class formatter extends base_formatter {
    /**
     * workspace_formatter constructor.
     * @param workspace $workspace
     */
    public function __construct(workspace $workspace) {
        $record = new \stdClass();
        $context = $workspace->get_context();

        $record->id = $workspace->get_id();
        $record->name = $workspace->fullname;
        $record->description = $workspace->summary;
        $record->description_format = $workspace->summaryformat;
        $record->url = $workspace->get_view_url();
        $record->time_created = $workspace->timecreated;
        $record->time_modified = $workspace->timemodified;
        $record->context_id = $context->id;

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if ('time_description' === $field) {
            return true;
        }

        return parent::has_field($field);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    protected function get_field(string $field) {
        if ('time_description' === $field) {
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
            'url' => null,
            'description_format' => null,
            'name' => string_field_formatter::class,
            'context_id' => null,
            'description' => function (?string $value, text_field_formatter $formatter) use ($that): ?string {
                if (null === $value || '' === $value) {
                    return null;
                }

                $formatter->set_text_format($that->object->description_format);
                $formatter->disabled_pluginfile_url_rewrite();
                $formatter->set_additional_options(['formatter' => 'totara_tui']);

                return $formatter->format($value);
            },

            'time_description' => function (int $value, date_field_formatter $formatter) use ($that): string {
                if (null !== $that->object->time_modified) {
                    $formatter->set_timemodified($that->object->time_modified);
                }

                return $formatter->format($value);
            }
        ];
    }
}

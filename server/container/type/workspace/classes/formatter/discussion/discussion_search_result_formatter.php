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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\formatter\discussion;

use cache;
use container_workspace\workspace;
use totara_comment\comment;
use totara_engage\formatter\field\date_field_formatter;
use container_workspace\discussion\discussion;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use stdClass;

/**
 * Class discussion_search_result_formatter
 * @package container_workspace\formatter\discussion
 */
final class discussion_search_result_formatter extends formatter {

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if (in_array($field, ['time_description'])) {
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
            // Using time_created as base.
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
            'workspace_id' => null,
            'discussion_id' => null,
            'instance_type' => null,
            'instance_id' => null,
            'content' => function (?string $content, text_field_formatter $formatter) use ($that): string {
                if (empty($content)) {
                    return '';
                }

                $formatter->set_text_format($that->object->content_format);
                $formatter->set_additional_options(['formatter' => 'totara_tui']);

                $component = $that->object->instance_type == discussion::AREA ? workspace::get_type() : 'totara_comment';
                $formatter->set_pluginfile_url_options(
                    $that->context,
                    $component,
                    $that->object->instance_type,
                    $that->object->instance_id
                );
                return $formatter->format($content);
            },
            'content_format' => null,
            'time_description' => function (int $time_created, date_field_formatter $formatter) use ($that): string {
                return $formatter->format($time_created);
            },
            'workspace_context_id' => null,
        ];
    }
}
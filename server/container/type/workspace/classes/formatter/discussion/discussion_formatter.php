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
 * @package container_workspace
 */
namespace container_workspace\formatter\discussion;

use cache;
use container_workspace\discussion\discussion;
use container_workspace\workspace;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;
use stdClass;
use totara_engage\formatter\field\date_field_formatter;

/**
 * Class discussion_formatter
 * @package container_workspace\formatter\discussion
 */
final class discussion_formatter extends formatter {

    /**
     * discussion_formatter constructor.
     * @param discussion $discussion
     */
    public function __construct(discussion $discussion) {
        $workspace_id = $discussion->get_workspace_id();

        $record = new stdClass();
        $record->id = $discussion->get_id();
        $record->workspace_id = $workspace_id;
        $record->content = $discussion->get_content();
        $record->content_format = $discussion->get_content_format();
        $record->time_created = $discussion->get_time_created();
        $record->time_modified = $discussion->get_time_modified();
        $record->total_reactions = $discussion->get_total_reactions();
        $record->total_comments = $discussion->get_total_comments();
        $record->deleted = $discussion->is_soft_deleted();
        $record->reason_deleted = $discussion->get_reason_deleted();

        $context = $discussion->get_context();
        $record->workspace_context_id = $context->id;

        parent::__construct($record, $context);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function has_field(string $field): bool {
        if (in_array($field, ['time_description', 'draft_content', 'draft_id'])) {
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
        } else if ('draft_content' === $field) {
            // Using the discussion's content as the base value for
            // field 'draft_content'.
            return parent::get_field('content');
        } else if ('draft_id' === $field) {
            // Using the discussion's content as base value for
            // field 'draft_id'.
            return parent::get_field('content');
        }

        return parent::get_field($field);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        global $CFG;
        $that = $this;

        return [
            'id' => null,
            'workspace_id' => null,
            'total_reactions' => null,
            'total_comments' => null,
            'content_format' => null,
            'workspace_context_id' => null,
            'time_description' => function (int $time_created, date_field_formatter $formatter) use ($that): string {
                if (null !== $that->object->time_modified && 0 !== $that->object->time_modified) {
                    $formatter->set_timemodified($that->object->time_modified);
                }

                return $formatter->format($time_created);
            },
            'content' => function (?string $content, text_field_formatter $formatter) use ($that): string {
                if ($that->object->deleted) {
                    $reason = $that->object->reason_deleted;

                    // Different phrasing for removed versus user deleted comments
                    if (null !== $reason && discussion::REASON_DELETED_REPORTED == $reason) {
                        return get_string('removed_discussion', 'container_workspace');
                    }
                    return '';
                } else if (empty($content)) {
                    return '';
                }

                $formatter->set_text_format($that->object->content_format);
                $formatter->set_additional_options(['formatter' => 'totara_tui']);

                $formatter->set_pluginfile_url_options(
                    $that->context,
                    workspace::get_type(),
                    discussion::AREA,
                    $that->object->id
                );

                return $formatter->format($content);
            },
            'draft_content' => function (?string $content, text_field_formatter $formatter) use ($that, $CFG): string {
                if (null === $content || '' === $content) {
                    return '';
                }

                $draft_id_cache = cache::make('container_workspace', 'draft_id');
                $draft_id = $draft_id_cache->has($that->object->id) ? $draft_id_cache->get($that->object->id) : null;

                require_once("{$CFG->dirroot}/lib/filelib.php");

                $content_format = $that->object->content_format;
                $component = workspace::get_type();

                $content = file_prepare_draft_area(
                    $draft_id,
                    $that->context->id,
                    $component,
                    discussion::AREA,
                    $that->object->id,
                    null,
                    $content
                );

                $draft_id_cache->set($that->object->id, $draft_id);

                $formatter->set_pluginfile_url_options(
                    $that->context,
                    workspace::get_type(),
                    discussion::AREA,
                    $draft_id
                );

                $formatter->set_text_format($content_format);
                return $formatter->format($content);
            },
            'draft_id' => function (?string $content, text_field_formatter $formatter) use ($that, $CFG): string {
                $draft_id_cache = cache::make('container_workspace', 'draft_id');
                $draft_id = $draft_id_cache->has($that->object->id) ? $draft_id_cache->get($that->object->id) : null;

                if (!empty($draft_id)) {
                    return $draft_id;
                }

                require_once("{$CFG->dirroot}/lib/filelib.php");

                $component = workspace::get_type();

                // Move all the files into the draft area. If we subsequently get the draft_content and specify this
                // draft_id then it assumes the files have already been copied into it.
                file_prepare_draft_area(
                    $draft_id,
                    $that->context->id,
                    $component,
                    discussion::AREA,
                    $that->object->id,
                    null,
                    $content
                );

                $draft_id_cache->set($that->object->id, $draft_id);

                return $draft_id;
            },
        ];
    }
}
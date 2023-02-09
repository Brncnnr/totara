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
 * @package totara_comment
 */
namespace totara_comment\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_comment\comment;
use totara_comment\resolver_factory;

/**
 * Type resolver for draft item.
 */
class draft_item extends type_resolver {
    /**
     * A map of comment's id against the draft's id.
     * @var array
     */
    private static $maps;

    /**
     * @param string            $field
     * @param comment           $source
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        global $CFG;

        if (!($source instanceof comment)) {
            throw new \coding_exception("Invalid parameter \$source");
        } else if ($source->is_soft_deleted()) {
            throw new \coding_exception("Cannot resolve the draft comment that has been deleted");
        }

        if (!isset(static::$maps)) {
            static::$maps = [];
        }

        switch ($field) {
            case 'id':
                return $source->get_id();

            case 'component':
                return $source->get_component();

            case 'area':
                return $source->get_area();

            case 'format':
                return $source->get_format();

            case 'content':
                // We are preparing the draft items for the totara_comment with comment area. It has nothing
                // to do with component and area from the table itself.
                require_once("{$CFG->dirroot}/lib/filelib.php");

                $content = $source->get_content();

                $comment_id = $source->get_id();
                $comment_area = comment::COMMENT_AREA;

                if ($source->is_reply()) {
                    $comment_area = comment::REPLY_AREA;
                }

                // Fetching the context from the place that is using this totara_comment, as totara_comment
                // does not use any context at all.
                $resolver = resolver_factory::create_resolver($source->get_component());
                $context_id = $resolver->get_context_id($source->get_instanceid(), $source->get_area());

                $draftid = null;
                $content = file_prepare_draft_area(
                    $draftid,
                    $context_id,
                    'totara_comment',
                    $comment_area,
                    $source->get_id(),
                    null,
                    $content
                );

                // Add the draft's id to the map - so that we can fetch it later on.
                static::$maps[$comment_id] = $draftid;

                return $content;

            case 'comment_area':
                if ($source->is_reply()) {
                    return strtoupper(comment::REPLY_AREA);
                }

                return strtoupper(comment::COMMENT_AREA);

            case 'file_draft_id':
                $comment_id = $source->get_id();
                if (isset(static::$maps[$comment_id])) {
                    return static::$maps[$comment_id];
                }

                require_once("{$CFG->dirroot}/lib/filelib.php");
                $comment_area = comment::COMMENT_AREA;

                if ($source->is_reply()) {
                    $comment_area = comment::REPLY_AREA;
                }

                // Fetching the context from the place that is using this totara_comment, as totara_comment
                // does not use any context at all.
                $resolver = resolver_factory::create_resolver($source->get_component());
                $context_id = $resolver->get_context_id($source->get_instanceid(), $source->get_area());

                $draft_id = null;
                file_prepare_draft_area(
                    $draftid,
                    $context_id,
                    'totara_comment',
                    $comment_area,
                    $source->get_id()
                );

                // Add the draft's id to the map - so that we can fetch it later on.
                static::$maps[$comment_id] = $draft_id;
                return (int) $draft_id;

            default:
                debugging("The field '{$field}' was not found for source", DEBUG_DEVELOPER);
                return null;
        }
    }
}
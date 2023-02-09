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
namespace container_workspace\webapi\resolver\mutation;

use container_workspace\local\workspace_helper;
use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use container_workspace\workspace;

/**
 * Resolver for creating a workspace via graphql.
 */
class create extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return workspace
     */
    public static function resolve(array $args, execution_context $ec): workspace {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_coursecat::instance(workspace::get_default_category_id()));
        }

        $workspace_name = $args['name'];
        if (empty($workspace_name)) {
            throw new \coding_exception("Cannot create a workspace with an empty name");
        }

        // Default to FORMAT_JSON_EDITOR
        $summary_format = FORMAT_JSON_EDITOR;
        if (isset($args['description_format'])) {
            $summary_format = $args['description_format'];
        }

        $summary = null;
        if (isset($args['description'])) {
            $summary = $args['description'];

            if (FORMAT_JSON_EDITOR == $summary_format && document_helper::is_document_empty($summary)) {
                // Description document is empty, hence we would not want to add it.
                $summary = null;
            }
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = $args['draft_id'];
        }

        $is_private = $args['private'];
        $is_hidden = $args['hidden'];

        return workspace_helper::create_workspace(
            $workspace_name,
            $USER->id,
            null,
            $summary,
            $summary_format,
            $draft_id,
            $is_private,
            $is_hidden
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new clean_editor_content('description', 'description_format', false),

            // For now we only support FORMAT_JSON_EDIT via graphql mutation.
            new clean_content_format('description_format', FORMAT_JSON_EDITOR, [FORMAT_JSON_EDITOR])
        ];
    }

}
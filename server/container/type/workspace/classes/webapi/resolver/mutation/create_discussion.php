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

use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use container_workspace\exception\discussion_exception;
use container_workspace\webapi\middleware\workspace_availability_check;
use container_workspace\workspace;
use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;
use core\webapi\middleware\clean_editor_content;

/**
 * Class create_discussion
 * @package container_workspace\webapi\resolver\mutation
 */
class create_discussion extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return discussion
     */
    public static function resolve(array $args, execution_context $ec): discussion {
        global $USER;

        /** @var workspace $workspace */
        $workspace = factory::from_id($args['workspace_id']);

        if (!$ec->has_relevant_context()) {
            $context = $workspace->get_context();
            $ec->set_relevant_context($context);
        }

        // By default it will be FORMAT_JSON_EDITOR as it comes from the front-end to here.
        $content_format = FORMAT_JSON_EDITOR;
        if (isset($args['content_format'])) {
            $content_format = (int) $args['content_format'];
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = (int) $args['draft_id'];
        }

        $content = $args['content'];

        if (
            (FORMAT_JSON_EDITOR == $content_format && document_helper::is_document_empty($content)) ||
            empty($content)
        ) {
            throw discussion_exception::on_create("Discussion content is empty");
        }

        return discussion_helper::create_discussion(
            $workspace,
            $args['content'],
            $draft_id,
            $content_format,
            $USER->id
        );
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new clean_editor_content('content', 'content_format'),
            new workspace_availability_check('workspace_id'),

            // For now, we are only supporting FORMAT_JSON_EDITOR via graphql mutation.
            new clean_content_format('content_format', FORMAT_JSON_EDITOR, [FORMAT_JSON_EDITOR])
        ];
    }
}
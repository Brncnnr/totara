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

use container_workspace\exception\workspace_exception;
use container_workspace\interactor\workspace\interactor;
use container_workspace\webapi\middleware\workspace_availability_check;
use container_workspace\workspace;
use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;
use container_workspace\local\workspace_helper;

/**
 * Resolver for updating
 */
class update extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return workspace
     */
    public static function resolve(array $args, execution_context $ec): workspace {
        global $USER;

        /** @var workspace $workspace */
        $workspace = factory::from_id($args['id']);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($workspace->get_context());
        }

        $owner_id = $workspace->get_user_id();
        $interactor = new interactor($workspace, $USER->id);

        if ($USER->id != $owner_id && !$interactor->can_update()) {
            // Not a same owner and also not a site admin, therefore we skip it.
            throw workspace_exception::on_update();
        }

        $is_private = $args['private'] ?? $workspace->is_private();
        $is_hidden = $args['hidden'] ?? $workspace->is_hidden();

        // Check for the access settings config, to find out if update is possible or not.
        if (!$is_private && $is_hidden) {
            throw new \coding_exception("Cannot have a hidden public workspace");
        }

        if ($is_hidden && !$workspace->can_move_to_hidden()) {
            throw new \coding_exception("Cannot go down to hidden workspace");
        }

        if ($is_private && !$workspace->can_move_to_private()) {
            throw new \coding_exception("Cannot update to private workspace");
        }

        if (!$is_private && !$workspace->can_move_to_public()) {
            throw new \coding_exception("Cannot update to public workspace");
        }

        $record = new \stdClass();
        if (!isset($args['name'])) {
            // Set it back to the original if it is not being set.
            $record->fullname = $workspace->fullname;
        } else {
            $record->fullname = $args['name'];
        }

        if (!isset($args['description_format'])) {
            $record->summaryformat = $workspace->summaryformat;
        } else {
            $record->summaryformat = $args['description_format'];
        }

        if (array_key_exists('description', $args)) {
            $summary = $args['description'];
            if (null === $summary) {
                // Reset null value to empty string, as we are moving away from null-able string.
                $summary = '';
            } else if (
                FORMAT_JSON_EDITOR == $record->summaryformat &&
                document_helper::is_document_empty($summary)
            ) {
                // Document is there but it is an empty document, hence reset summary to an empty string.
                $summary = '';
            }

            $record->summary = $summary;
        }


        $record->visibleold = $workspace->visibleold;
        $record->visible = $is_hidden ? 0 : 1;
        $record->workspace_private = $is_private;

        // Update the record, then update the topics if there are any.
        $workspace->update($record);

        if (!empty($args['draft_id'])) {
            $workspace->save_image((int) $args['draft_id']);
        }

        // Process hashtags.
        workspace_helper::workspace_summary_hashtags($workspace);

        return $workspace;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new workspace_availability_check('id'),
            new clean_editor_content('description', 'description_format', false),

            // We are only allowing the FORMAT_JSON_EDITOR via graphql mutation for now
            new clean_content_format('description_format', null, [FORMAT_JSON_EDITOR])
        ];
    }
}
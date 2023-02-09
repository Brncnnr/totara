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
namespace container_workspace\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use core_container\factory;
use container_workspace\workspace;

/**
 * Mutation for preparing a draft area for uploading.
 */
class file_area extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER, $CFG;

        require_once("{$CFG->dirroot}/lib/filelib.php");
        require_once("{$CFG->dirroot}/repository/lib.php");

        if (isset($args['workspace_id'])) {
            $workspace_id = $args['workspace_id'];
            $workspace = factory::from_id($workspace_id);

            if (!$ec->has_relevant_context()) {
                $ec->set_relevant_context($workspace->get_context());
            }

            if (!$workspace->is_typeof(workspace::get_type())) {
                throw new \coding_exception("Invalid workspace");
            }

            $context = $workspace->get_context();
        } else {
            $category_id = workspace::get_default_category_id();
            $context = \context_coursecat::instance($category_id);

            if (!$ec->has_relevant_context()) {
                $ec->set_relevant_context($context);
            }
        }

        $repositories = \repository::get_instances([
            'currentcontext' => $context,
            'type' => 'upload',
            'userid' => $USER->id
        ]);

        if (empty($repositories)) {
            throw new \coding_exception("Cannot find repository for upload");
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = $args['draft_id'];
        }

        file_prepare_draft_area(
            $draft_id,
            $context->id,
            workspace::get_type(),
            workspace::IMAGE_AREA,
            0
        );

        $repository = reset($repositories);
        $url = new \moodle_url("/repository/repository_ajax.php", ['action' => 'upload']);

        return [
            'item_id' => $draft_id,
            'repository_id' => (int) $repository->id,
            'url' => $url->out(),
            'accept_types' => file_get_typegroup('extension', ['web_image']),
            'context_id' => $context->id,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
        ];
    }

}
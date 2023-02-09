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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_engage\resource\resource_factory;
use totara_playlist\playlist;

/**
 * Mutation resolver for totara_playlist_remove_resource.
 */
class remove_resource extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER, $DB;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $transaction = $DB->start_delegated_transaction();

        $playlist_id = $args['id'];
        $instance_id = $args['instanceid'];

        // No exposing internal error out.
        try {
            $playlist = playlist::from_id($playlist_id);
            $resource = resource_factory::create_instance_from_id($instance_id);
        } catch (\Exception $ex) {
            throw new \moodle_exception('error:permissiondenied', 'totara_playlist', '', null, $ex->getMessage());
        }

        $playlist->remove_resource($resource);
        $transaction->allow_commit();

        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('engage_resources'),
        ];
    }

}
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
use totara_engage\access\access_manager;
use totara_playlist\local\helper;
use totara_playlist\playlist;

/**
 * Mutation resolver for updating card order.
 */
class update_order extends mutation_resolver {
    /**
     * Mutation resolver.
     *
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $DB, $USER;

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $playlist = playlist::from_id($args['id'], true);
        $actor = (int)$USER->id;

        // If current user is not owner of playlist and not admin, exception has to be fired.
        if ($actor !== $playlist->get_userid() && !access_manager::can_manage_engage($playlist->get_context())) {
            throw new \coding_exception('Current user can not order cards in the playlist');
        }

        $transaction = $DB->start_delegated_transaction();
        helper::swap_card_sort_order($playlist, $args['instanceid'], $args['order']);
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
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
use totara_engage\event\rating_created;
use totara_engage\rating\rating_manager;
use totara_playlist\playlist;
use core\task\manager;
use totara_playlist\task\rate_notify_task;

/**
 * Mutation resolver for totara_playlist_add_rating.
 */
class add_rating extends mutation_resolver {

    /**
     * Mutation resolver.
     *
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER, $DB;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        // Only playlist is allowed to rate at this stage
        if ($args['ratingarea'] != playlist::RATING_AREA) {
            throw new \coding_exception('Wrong area used for rating');
        }

        if ($args['rating'] < 0 || $args['rating'] > playlist::RATING_MAX) {
            throw new \coding_exception('Rating is out of boundaries');
        }

        $transaction = $DB->start_delegated_transaction();

        $playlist = playlist::from_id($args['playlistid']);

        if (!access_manager::can_access($playlist, $USER->id)) {
            throw new \coding_exception('Access denied');
        }

        $rating = rating_manager::instance($playlist->get_id(), 'totara_playlist', $args['ratingarea']);
        if (!$rating->can_rate($USER->id, $playlist->get_userid())) {
            throw new \coding_exception("User can not rate this playlist");
        }

        $rating_record = $rating->add($args['rating'], $USER->id);

        $task = new rate_notify_task();
        $task->set_custom_data([
            'url' => $playlist->get_url(),
            'owner' => $playlist->get_userid(),
            'name' => $playlist->get_name(),
            'rater' => $USER->id
        ]);
        manager::queue_adhoc_task($task);

        rating_created::from_rating($rating_record)->trigger();

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
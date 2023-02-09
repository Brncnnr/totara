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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\webapi\resolver\mutation;

use context_system;
use core\task\manager;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_system_capability;
use core\webapi\mutation_resolver;
use totara_reportedcontent\hook\remove_review_content;
use totara_reportedcontent\review;
use totara_reportedcontent\task\remove_notify_task;

/**
 * Class remove_review
 *
 * @package totara_reportedcontent\webapi\resolver\mutation
 */
class remove_review extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return review
     */
    public static function resolve(array $args, execution_context $ec): review {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $review_id = $args['review_id'];
        $review = review::from_id($review_id);

        if (!$review || !$review->exists()) {
            throw new \coding_exception("Review '{$review_id}' does not exist.");
        }

        // Going to call a hook to tell the correct component to clear itself out
        $hook = new remove_review_content($review);
        $hook->execute();

        // If the hook removed the content, finish processing & fire the notification off
        if ($hook->success) {
            $review->do_review(review::DECISION_REMOVE, $USER->id);

            // Build & fire a notification here
            $task = new remove_notify_task();
            $task->set_component('totara_reportedcontent');

            $task->set_custom_data([
                'target_user_id' => $review->get_target_user()->id,
                'time_created' => $review->get_time_created(),
                'url' => $review->get_url(),
            ]);

            manager::queue_adhoc_task($task);
        }

        return $review;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_system_capability('totara/reportedcontent:manage'),
        ];
    }

}
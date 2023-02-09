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
 * @package engage_survey
 */
namespace engage_survey\observer;

use core\task\manager;
use engage_survey\totara_engage\resource\survey;
use totara_engage\task\like_notify_task;
use totara_reaction\event\reaction_created;

/**
 * Observer for reaction component
 */
final class reaction_observer {
    /**
     * reaction_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param reaction_created $event
     * @return void
     */
    public static function on_reaction_created(reaction_created $event): void {
        $others = $event->other;

        if ($others['component'] === survey::get_resource_type()) {
            $liker_id = $event->userid;
            $article = survey::from_resource_id($others['instanceid']);

            if ($liker_id !== $article->get_userid()) {
                $task = new like_notify_task();
                $task->set_custom_data([
                    'url' => $article->get_url(),
                    'liker' => $liker_id,
                    'owner' => $article->get_userid(),
                    'name' => $article->get_name(),
                    'resourcetype' => get_string('message_survey', 'engage_survey'),
                    'resource_info' => [
                        'stringkey' => 'message_survey',
                        'component' => 'engage_survey'
                    ],
                ]);

                manager::queue_adhoc_task($task);
            }
        }
    }
}

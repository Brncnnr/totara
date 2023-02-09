<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package engage_survey
 */
namespace engage_survey\webapi\resolver\mutation;

use core\task\manager;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use engage_survey\event\vote_created;
use engage_survey\task\vote_notify_task;
use engage_survey\totara_engage\resource\survey;
use totara_engage\access\access_manager;
use totara_engage\exception\resource_exception;

/**
 * Mutation resolver for creating answers.
 */
class create_answer extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec): bool {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        /** @var survey $survey */
        $survey = survey::from_resource_id($args['resourceid']);
        if (!access_manager::can_access($survey, $USER->id)) {
            throw resource_exception::create('answer', $survey::get_resource_type());
        }

        $result = $survey->add_answer($args['questionid'], $args['options']);

        if ($result) {
            vote_created::from_survey($survey)->trigger();

            if ($USER->id !== $survey->get_userid()) {
                $task =  new vote_notify_task();
                $task->set_custom_data([
                    'url' => $survey->get_url(),
                    'owner' => $survey->get_userid(),
                    'voter' => $USER->id,
                    'name' => $survey->get_name(),
                ]);

                manager::queue_adhoc_task($task);
            }
        }
        return $result;
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
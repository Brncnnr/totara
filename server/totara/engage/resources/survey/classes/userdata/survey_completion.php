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

namespace engage_survey\userdata;

use totara_engage\answer\answer_type;
use totara_engage\answer\multi_choice;
use totara_engage\answer\single_choice;
use totara_engage\entity\answer_choice;
use totara_engage\exception\no_answer_found;
use totara_engage\repository\answer_choice_repository;
use totara_userdata\userdata\item;
use totara_engage\entity\engage_resource_completion;
use totara_engage\repository\resource_completion;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;
use engage_survey\totara_engage\resource\survey;

final class survey_completion extends item {

    /**
     * @param int $userstatus
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int|void
     */
    protected static function purge(target_user $user, \context $context): int {
        /** @var resource_completion $repository */
        $repository = engage_resource_completion::repository();
        $repository->delete_by_userid((int)$user->id, survey::get_resource_type());

        /** @var answer_choice_repository $repository */
        $repository = answer_choice::repository();
        $repository->delete_by_userid((int)$user->id);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        /** @var resource_completion $repository */
        $repository = engage_resource_completion::repository();

        $entities = $repository->get_all((int)$user->id, survey::get_resource_type());

        $export = new export();
        $export->data = [];

        /** @var engage_resource_completion $entity */
        foreach ($entities as $entity) {
            $survey = survey::from_resource_id($entity->resourceid);
            $questions = $survey->get_questions();
            $question = reset($questions);

            /** @var answer_choice_repository $repo */
            $repo = answer_choice::repository();
            $options = $repo->get_answers_for_user($question->get_id(), (int)$user->id);

            $export->data[] = [
                'name' => $survey->get_name(),
                'options' => array_keys($options)
            ];
        }

        return $export;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        /** @var resource_completion $repository */
        $repository = engage_resource_completion::repository();
        return $repository->count_for_resources((int) $user->id, survey::get_resource_type());
    }

    /**
     * @return array|string[]
     */
    public static function get_fullname_string() {
        return ['user_data_item_survey_completion', 'engage_survey'];
    }
}


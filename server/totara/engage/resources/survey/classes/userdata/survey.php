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

use engage_survey\local\helper;
use engage_survey\local\loader;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use engage_survey\totara_engage\resource\survey as survey_resource;

final class survey extends item {

    public static function is_purgeable(int $userstatus) {
        return true;
    }

    public static function is_exportable() {
        return true;
    }

    public static function is_countable() {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return int|void
     */
    protected static function purge(target_user $user, \context $context): int {
        $paginator = loader::load_all_survey_of_user($user->id, 0);
        $surveys = $paginator->get_items()->all();

        foreach ($surveys as $survey) {
            helper::purge_survey($survey);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context $context
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        $paginator = loader::load_all_survey_of_user((int)$user->id, 0);
        $resources = $paginator->get_items()->all();

        $export = new export();
        $export->data = [];
        $answer_options = [];
        /** @var survey_resource $resource */
        foreach ($resources as $resource) {
            $questions = $resource->get_questions();

            // Survey always has one question.
            $question = reset($questions);

            foreach ($question->get_answer_options() as $option) {
                $answer_options[] = $option->value;
            }
            $export->data[] = [
                'name' => $resource->get_name(),
                'options' =>  $answer_options,
                'timecreated' => $resource->get_timecreated(),
                'timemodified' => $resource->get_timemodified()
            ];
        }

        return $export;
    }
    
    protected static function count(target_user $user, \context $context): int {
        $paginator = loader::load_all_survey_of_user((int)$user->id, 0);
        return $paginator->get_total();
    }

    /**
     * @return array|string[]
     */
    public static function get_fullname_string() {
        return ['user_data_item_survey', 'engage_survey'];
    }
}
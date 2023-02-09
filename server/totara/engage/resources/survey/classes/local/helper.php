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
namespace engage_survey\local;

use engage_survey\totara_engage\resource\survey;
use totara_engage\entity\answer_choice;
use totara_engage\entity\answer_option;
use totara_engage\question\question;
use totara_engage\share\manager;
use totara_engage\entity\question as question_entity;

final class helper {
    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent the construction directly.
    }

    /**
     * As the method is for purging, we do not need capability check.
     *
     * @param survey $survey
     */
    public static function purge_survey(survey $survey): void {
        global $DB;

        // Delete resource.
        $DB->delete_records('engage_resource', ['id' => $survey->get_id()]);

        // Delete shares.
        manager::delete($survey->get_id(), survey::get_resource_type());

        // Delete questions.
        $survey_questions = $survey->get_survey_questions();
        foreach ($survey_questions as $survey_question) {
            $question = question::from_id($survey_question->questionid);
            // Deleting answer choice.
            $DB->delete_records(answer_choice::TABLE, ['questionid' => $question->get_id()]);

            // Deleting answer option.
            $DB->delete_records(answer_option::TABLE, ['questionid' => $question->get_id()]);

            $DB->delete_records(question_entity::TABLE, ['id' => $question->get_id()]);
            $survey_question->delete();
        }

        // Delete itself.
        $DB->delete_records('engage_survey', ['id' => $survey->get_instanceid()]);
    }
}
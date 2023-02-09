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
 * @package engage_survey
 */
namespace engage_survey\result;

use engage_survey\entity\survey;
use engage_survey\entity\survey_question;
use engage_survey\repository\survey_question_repository;
use totara_engage\question\question;
use engage_survey\result\question as question_stat;
use engage_survey\result\option as option_stat;

/**
 * Helper class to get the vote result from a survey.
 */
final class vote_result {
    /**
     * @var survey
     */
    private $survey;

    /**
     * vote_result constructor.
     * @param survey $survey
     */
    public function __construct(survey $survey) {
        if (!$survey->exists()) {
            throw new \coding_exception("Invalid record that is not existing in the system");
        }
        $this->survey = $survey;
    }

    /**
     * Returning an array of the number of user has been voted for the survey, and the number of user has vote
     * for specific option of a question within a survey.
     *
     * @return question_stat[]
     */
    public function get_statistic(): array {
        $rtn = [];

        /** @var survey_question_repository $repo */
        $repo = survey_question::repository();
        $survey_questions = $repo->get_all_for_survey($this->survey->id);

        $question_votes = $this->get_question_votes();
        $option_votes = $this->get_option_votes();

        foreach ($survey_questions as $survey_question) {
            $question = question::from_id($survey_question->questionid);

            $question_id = $question->get_id();
            $answer_type = $question->get_answer_type();

            $question_stat = new question_stat(
                $question_id,
                $question->get_value(),
                $question->can_be_updated(),
                $answer_type
            );

            if (isset($question_votes[$question_id])) {
                $record = $question_votes[$question_id];
                $question_stat->set_votes((int) $record->votes);
                $question_stat->set_participants((int) $record->participants);
            }

            $options = $question->get_answer_options();
            foreach ($options as $option) {
                $option_stat = new option_stat(
                    $option->id,
                    $option->questionid,
                    $option->value
                );

                if (isset($option_votes[$option->id])) {
                    $record = $option_votes[$option->id];
                    $option_stat->set_votes((int) $record->votes);
                }

                $question_stat->add_option($option_stat);
            }

            $rtn[] = $question_stat;
        }

        return $rtn;
    }

    /**
     * Returning an array of records for how many voted for the specific options within a question of a survey.
     * @return \stdClass[]
     */
    private function get_option_votes(): array {
        global $DB;

        $sql = '
            SELECT eac.optionid, eac.questionid, COUNT(eac.userid) AS votes 
            FROM "ttr_engage_survey_question" esq
            INNER JOIN "ttr_engage_answer_choice" eac ON esq.questionid = eac.questionid
            WHERE esq.surveyid = :surveyid
            GROUP BY eac.optionid, eac.questionid
        ';

        return $DB->get_records_sql($sql, ['surveyid' => $this->survey->id]);
    }

    /**
     * Returning an array of records for how many total voted for specific options within a question.
     * @return \stdClass[]
     */
    private function get_question_votes(): array {
        global $DB;

        $sql = '
            SELECT esq.questionid, COUNT(eac.userid) AS votes, COUNT(DISTINCT (eac.userid)) As participants
            FROM "ttr_engage_survey_question" esq
            INNER JOIN "ttr_engage_answer_choice" eac ON eac.questionid = esq.questionid
            WHERE esq.surveyid = :surveyid
            GROUP BY esq.questionid
        ';

        return $DB->get_records_sql($sql, ['surveyid' => $this->survey->id]);
    }
}
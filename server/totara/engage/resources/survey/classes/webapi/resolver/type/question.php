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
namespace engage_survey\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use engage_survey\formatter\question_formatter;
use engage_survey\entity\survey_question;
use totara_engage\question\question_factory;

/**
 * Type resolver for graphql type engage_survey_question
 */
class question extends type_resolver {
    /**
     * @param string            $field
     * @param survey_question   $survey_question
     * @param array             $args
     * @param execution_context $ec
     *
     * @return mixed
     */
    public static function resolve(string $field, $survey_question, array $args, execution_context $ec) {
        if (!($survey_question instanceof survey_question)) {
            throw new \coding_exception("Invalid type of \$source, expecting a type of " . survey_question::class);
        }

        $question_id = $survey_question->questionid;
        $question = question_factory::get_question_by_id($question_id);

        switch ($field) {
            case 'user':
                $userid = $question->get_userid();
                return \core_user::get_user($userid);

            case 'options':
                return $question->get_answer_options();

            default:
                $format = null;
                if (isset($args['format'])) {
                    $format = $args['format'];
                }

                $context = $ec->get_relevant_context();
                $formatter = new question_formatter($question, $context);

                return $formatter->format($field, $format);
        }
    }
}
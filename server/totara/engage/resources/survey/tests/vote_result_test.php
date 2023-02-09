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
defined('MOODLE_INTERNAL') || die();

use engage_survey\totara_engage\resource\survey;
use totara_engage\answer\answer_type;

class engage_survey_vote_result_testcase extends advanced_testcase {

    public function test_vote_result(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey();

        $questions = $survey->get_questions();
        $question = reset($questions);

        $options = $question->get_answer_options();
        $optionids = [];

        foreach ($options as $option) {
            $optionids[] = $option->id;
        }

        $survey->add_answer($question->get_id(), $optionids);
        $resourceid = $survey->get_id();
        $survey = survey::from_resource_id($resourceid);
        $extra = $survey->get_extra();

        $this->assertNotEmpty($extra);
    }
}
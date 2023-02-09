<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use totara_engage\answer\answer_type;
use ml_recommender\entity\interaction;
use engage_survey\event\survey_viewed;

class engage_survey_event_survey_viewed_testcase extends advanced_testcase {

    public function test_view_private_survey_interaction(): void {
        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $articlegen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        $users = $surveygen->create_users(2);
        $this->setUser($users[0]);

        // Create survey.
        $survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PRIVATE
        ]);

        $event = survey_viewed::from_survey($survey);
        $this->assertFalse($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertEmpty($interactions);
    }

    public function test_view_public_survey_interaction(): void {
        $gen = $this->getDataGenerator();

        /** @var \engage_survey\testing\generator $articlegen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        $users = $surveygen->create_users(2);
        $this->setUser($users[0]);

        // Create survey.
        $survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $event = survey_viewed::from_survey($survey);
        $this->assertTrue($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertNotEmpty($interactions);
    }
}
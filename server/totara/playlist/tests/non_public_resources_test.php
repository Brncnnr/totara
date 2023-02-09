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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

class totara_playlist_non_public_resources_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_check_playlist_against_private_resources(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey();

        $playlist->add_resource($survey);
        $this->assertTrue($playlist->has_non_public_resources());
    }

    /**
     * @return void
     */
    public function test_check_playlist_against_restricted_resources(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_restricted_survey();

        $playlist->add_resource($survey);
        $this->assertTrue($playlist->has_non_public_resources());
    }

    /**
     * @return void
     */
    public function test_check_playlist_against_public_resources(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        $playlist->add_resource($survey);
        $this->assertFalse($playlist->has_non_public_resources());
    }

    /**
     * @return void
     */
    public function test_check_playlist_against_private_resources_with_public_resources(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        $private_survey = $survey_generator->create_survey();
        $public_survey = $survey_generator->create_public_survey();

        $playlist->add_resource($public_survey);
        $playlist->add_resource($private_survey);

        $this->assertTrue($playlist->has_non_public_resources());
    }
}
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
defined('MOODLE_INTERNAL') || die();

use totara_engage\answer\answer_type;

class engage_survey_availability_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_deleted_user_should_make_survey_unavailable(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey(
            "Is this for real ?",
            [],
            answer_type::MULTI_CHOICE,
            ['userid' => $user_one->id]
        );

        self::assertTrue($survey->is_available());

        // Delete user and check the availability of the survey after user is deleted.
        delete_user($user_one);
        self::assertFalse($survey->is_available());
    }

    /**
     * @return void
     */
    public function test_suspended_user_should_not_make_survey_unavailable(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey(
            'Wooops !?',
            [],
            answer_type::MULTI_CHOICE,
            ['userid' => $user_one->id]
        );

        self::assertTrue($survey->is_available());

        // Suspend user.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_one->id);

        // Suspend user will not make survey unavailable.
        self::assertTrue($survey->is_available());
    }
}
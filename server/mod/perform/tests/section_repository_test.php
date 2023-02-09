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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\section;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity;

/**
 * @group perform
 */
class mod_perform_section_respository_testcase extends advanced_testcase {

    public function test_find_first_for_subject_instance(): void {
        self::setAdminUser();

        $data_generator = self::getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');

        $config = new \mod_perform\testing\activity_generator_configuration();
        $config->set_number_of_sections_per_activity(3);

        /** @var activity $activity */
        $activity = $perform_generator->create_full_activities()->first();

        $subject_instance = subject_instance::repository()->order_by('id')->first();

        $first_section = section::repository()->find_first_for_subject_instance($subject_instance->id);

        self::assertEquals('1', $first_section->sort_order);
        self::assertEquals($activity->id, $first_section->activity_id);
    }

}
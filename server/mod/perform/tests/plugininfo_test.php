<?php
/*
 * This file is part of Totara Perform
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\plugininfo;
use mod_perform\entity\activity\subject_instance;
use totara_core\advanced_feature;

/**
 * @group perform
 */
class mod_perform_plugininfo_testcase extends advanced_testcase {

    /**
     */
    public function test_plugininfo_data() {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['performanceactivitiesenabled']);
        $this->assertEquals(0, $result['numactivities']);
        $this->assertEquals(0, $result['numuserassignments']);
        $this->assertEquals(0, $result['numsubjectinstances']);
        $this->assertEquals(0, $result['numparticipantinstances']);
        $this->assertEquals(0, $result['numelementresponses']);

        $config = (\mod_perform\testing\activity_generator_configuration::new())
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->set_number_of_elements_per_section(2)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT]);
        $activity = $generator->create_full_activities($config)->first();
        $subject_instance = subject_instance::repository()
            ->one();
        $generator->create_responses($subject_instance);

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['performanceactivitiesenabled']);
        $this->assertEquals(1, $result['numactivities']);
        $this->assertEquals(1, $result['numuserassignments']);
        $this->assertEquals(1, $result['numsubjectinstances']);
        $this->assertEquals(1, $result['numparticipantinstances']);
        // 2 questions so 2 responses
        $this->assertEquals(2, $result['numelementresponses']);

        advanced_feature::disable('performance_activities');
        $result = $plugininfo->get_usage_for_registration_data();

        // Plugin disabled but data still there.
        $this->assertEquals(0, $result['performanceactivitiesenabled']);
        $this->assertEquals(1, $result['numactivities']);
        $this->assertEquals(1, $result['numuserassignments']);
        $this->assertEquals(1, $result['numsubjectinstances']);
        $this->assertEquals(1, $result['numparticipantinstances']);
        $this->assertEquals(2, $result['numelementresponses']);

    }

}

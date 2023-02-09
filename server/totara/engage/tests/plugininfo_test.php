<?php
/*
 * This file is part of Totara Engage
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
 * @package totara_engage
 */

use totara_core\advanced_feature;
use totara_engage\access\access;
use totara_engage\bookmark\bookmark;
use totara_engage\plugininfo;
use totara_engage\rating\rating_manager;

/**
 * @group totara_engage
 */
class totara_engage_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['resourcesenabled']);
        $this->assertEquals(0, $result['numarticles']);
        $this->assertEquals(0, $result['numsurveys']);
        $this->assertEquals(0, $result['numbookmarks']);
        $this->assertEquals(0, $result['numratings']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['resourcesenabled']);
        $this->assertEquals(1, $result['numarticles']);
        $this->assertEquals(1, $result['numsurveys']);
        $this->assertEquals(1, $result['numbookmarks']);
        $this->assertEquals(1, $result['numratings']);

        advanced_feature::disable('engage_resources');
        $result = $plugininfo->get_usage_for_registration_data();

        // Data should be returned even if features are disabled.
        $this->assertEquals(0, $result['resourcesenabled']);
        $this->assertEquals(1, $result['numarticles']);
        $this->assertEquals(1, $result['numsurveys']);
        $this->assertEquals(1, $result['numbookmarks']);
        $this->assertEquals(1, $result['numratings']);
    }

    /**
     * Get engage generator
     *
     * @return \totara_engage\testing\generator
     * @throws coding_exception
     */
    protected function generator() {
        return \totara_engage\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $gen = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey();

        /** @var \totara_playlist\testing\generator $playlistgen */
        $playlistgen = $gen->get_plugin_generator('totara_playlist');

        $playlist = $playlistgen->create_playlist([
            'access' => access::PUBLIC
        ]);

        $user_two = $gen->create_user();
        $this->setUser($user_two);

        $bookmark = new bookmark($user_two->id, $article->get_id(), $article::get_resource_type());
        $bookmark->add_bookmark();

        $rating_manager = rating_manager::instance($playlist->get_id(), 'totara_playlist', 'playlist');
        $rating = $rating_manager->add(3);

        $this->setAdminUser();
    }
}

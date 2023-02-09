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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_topic
 */

use totara_topic\plugininfo;
use totara_core\advanced_feature;

/**
 * @group totara_topic
 */
class totara_topic_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(0, $result['numtopics']);
        $this->assertEquals(0, $result['numtopicinstances']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['numtopics']);
        $this->assertEquals(1, $result['numtopicinstances']);

        advanced_feature::disable('engage_resources');
        $result = $plugininfo->get_usage_for_registration_data();

        // Data should be returned even if features are disabled.
        $this->assertEquals(1, $result['numtopics']);
        $this->assertEquals(1, $result['numtopicinstances']);
    }

    /**
     * @return \totara_topic\testing\generator
     * @throws coding_exception
     */
    protected function generator() {
        return \totara_topic\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $topic = $this->generator()->create_topic();

        $gen = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');
        $article = $articlegen->create_article();

        // Create a tag instance.
        $article->add_topics_by_ids([$topic->get_id()]);
    }
}

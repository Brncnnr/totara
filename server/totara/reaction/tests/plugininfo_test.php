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
 * @package totara_reaction
 */

use totara_reaction\plugininfo;
use totara_reaction\resolver\resolver_factory;
use totara_reaction\reaction_helper;

/**
 * @group totara_reaction
 */
class totara_reaction_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(0, $result['numreactions']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['numreactions']);
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $resolver = new default_reaction_resolver();
        $resolver->set_component('core_course');

        resolver_factory::phpunit_set_resolver($resolver);
        return reaction_helper::create_reaction($course->id, 'core_course', 'course');
    }
}

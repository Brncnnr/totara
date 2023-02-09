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
 * @package container_workspace
 */

use container_workspace\plugininfo;
use totara_core\advanced_feature;

/**
 * @group container_workspace
 */
class container_workspace_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['workspacesenabled']);
        $this->assertEquals(0, $result['numworkspaces']);
        $this->assertEquals(0, $result['numworkspacediscussions']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['workspacesenabled']);
        $this->assertEquals(1, $result['numworkspaces']);
        $this->assertEquals(1, $result['numworkspacediscussions']);

        advanced_feature::disable('container_workspace');
        $result = $plugininfo->get_usage_for_registration_data();

        // Data should be returned even if workspaces are disabled.
        $this->assertEquals(0, $result['workspacesenabled']);
        $this->assertEquals(1, $result['numworkspaces']);
        $this->assertEquals(1, $result['numworkspacediscussions']);
    }

    /**
     * Get workspace generator
     *
     * @return \container_workspace\testing\generator
     * @throws coding_exception
     */
    protected function generator() {
        return \container_workspace\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $workspace =  $this->generator()->create_workspace();
        $this->generator()->create_discussion($workspace->get_id(), 'Test discussion');
    }
}

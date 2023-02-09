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
 * @package totara_comment
 */

use totara_comment\plugininfo;

/**
 * @group totara_comment
 */
class totara_comment_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(0, $result['numcomments']);

        // Generate test data
        $this->generate_data();

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals(1, $result['numcomments']);
    }

    /**
     * Get comment generator
     *
     * @return \totara_comment\testing\generator
     */
    protected function generator() {
        return \totara_comment\testing\generator::instance();
    }

    /**
     * Generate data required to set registration stats
     */
    private function generate_data() {
        $this->generator()->create_comment(
            42,
            'totara_comment',
            'comment_view',
            'Hello world'
        );
    }
}

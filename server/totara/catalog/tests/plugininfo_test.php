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
 * @package totara_catalog
 */

use totara_catalog\plugininfo;
use totara_catalog\local\config;

/**
 * @group totara_catalog
 */
class totara_catalog_plugininfo_testcase extends advanced_testcase {

    public function test_plugininfo_data() {
        $this->setAdminUser();

        $plugininfo = new plugininfo();

        // Grid layout with all learning types enabled.
        set_config('catalogtype', 'totara');
        $data['learning_types_in_catalog'] = ['course', 'engage_article', 'certification', 'playlist', 'program'];
        config::instance()->update($data);

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals('totara', $result['catalogview']);
        $this->assertEquals('course,engage_article,certification,playlist,program', $result['learningtypes']);

        // Disable some learning types.
        $data['learning_types_in_catalog'] = ['course', 'engage_article'];
        config::instance()->update($data);

        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals('totara', $result['catalogview']);
        $this->assertEquals('course,engage_article', $result['learningtypes']);

        // Switch to different catalog view.
        set_config('catalogtype', 'enhanced');
        $result = $plugininfo->get_usage_for_registration_data();
        $this->assertEquals('enhanced', $result['catalogview']);
        // Learning types not returned unless grid view used.
        $this->assertNull($result['learningtypes']);
    }
}
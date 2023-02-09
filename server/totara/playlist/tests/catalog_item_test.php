<?php
/**
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\provider_handler;
use \totara_catalog\task\refresh_catalog_data;

use totara_engage\access\access;

class totara_playlist_catalog_item_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_playlist_provider_exists(): void {
        $providerhandler = provider_handler::instance();

        $providers = $providerhandler->get_all_provider_classes();

        $found = false;
        foreach ($providers as $provider) {
            if ($provider == 'totara_playlist\totara_catalog\playlist') {
                $found = true;
            }
        }

        $this->assertTrue($found, 'playlist catalog provider not found.');
    }

    /**
     * @return void
     */
    public function test_playlist_sorttime_value(): void {
        global $DB;

        $this->setAdminUser();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $this->getDataGenerator()->get_plugin_generator('totara_playlist');
        $playlist = $playlist_generator->create_playlist([
            'name' => 'playlistItem',
            'access' => access::PUBLIC,
            'timecreated' => time() - 60 // Make this unique so we can check against it.
        ]);

        $refreshtask = new refresh_catalog_data();
        $refreshtask->execute();

        $catalogitems = $DB->get_records('catalog');
        $this->assertCount(1, $catalogitems);

        $item = array_pop($catalogitems);
        $this->assertEquals($playlist->get_timecreated(), $item->sorttime);
    }
}

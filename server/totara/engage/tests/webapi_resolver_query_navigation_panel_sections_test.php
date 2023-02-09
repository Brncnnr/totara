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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

use totara_engage\sidepanel\engage_provider;

defined('MOODLE_INTERNAL') || die();

use totara_playlist\totara_engage\sidepanel\playlist_provider;

class totara_engage_webapi_resolver_query_navigation_panel_sections_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_navigation_panel_sections(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('totara_engage_navigation_panel_sections');

        $engage_provider = new engage_provider();
        $playlist_provider = new playlist_provider();

        self::assertIsArray($result);
        self::assertEquals(
            [
                $engage_provider->get_navigation_section(),
                $playlist_provider->get_navigation_section()
            ],
            $result
        );

        $playlist_provider = $result[1];
        self::assertEquals('PlaylistNavigation', $playlist_provider['component']);
        self::assertTrue($playlist_provider['can_share']);
    }
}
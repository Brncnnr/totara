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
defined('MOODLE_INTERNAL') || die();

class totara_engage_webapi_resolver_query_modals_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_modals(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->resolve_graphql_query('totara_engage_modals');

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertEquals(
            [
                new \engage_article\totara_engage\modal\article_modal(),
                new \engage_survey\totara_engage\modal\survey_modal(),
                new \totara_playlist\totara_engage\modal\playlist_modal()
            ],
            $result
        );

        foreach ($result as $modal) {
            self::assertTrue($modal->can_share());
        }
    }
}
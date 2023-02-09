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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\webapi\resolver\query\available_dynamic_date_sources;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\models\activity\track
 *
 * @group perform
 */
class mod_perform_webapi_query_available_dynamic_date_sources_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_default_track_settings';

    use webapi_phpunit_helper;

    /**
     * Please note that this resolver is called from a multiple operation query.
     * @see mod_perform_webapi_query_default_track_settings_testcase
     */
    public function test_find(): void {
        $context = $this->create_webapi_context(self::QUERY);

        $dynamic_sources = available_dynamic_date_sources::resolve([], $context);

        self::assertGreaterThan(0, count($dynamic_sources));
        self::assertContainsOnlyInstancesOf(dynamic_source::class, $dynamic_sources);
    }

}

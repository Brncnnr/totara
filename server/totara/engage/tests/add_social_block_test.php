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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\advanced_feature;

class totara_engage_add_social_block_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_add_block_when_engage_is_on(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/engage/db/upgradelib.php");

        $total = $DB->count_records(
            'block_instances',
            [
                'blockname' => 'totara_user_profile',
                'pagetypepattern' => 'user-profile'
            ]
        );

        // Add the block.
        totara_engage_create_engage_profile_block();
        $result = $DB->count_records(
            'block_instances',
            [
                'blockname' => 'totara_user_profile',
                'pagetypepattern' => 'user-profile'
            ]
        );

        $this->assertGreaterThan($total, $result);
        $this->assertEquals($total + 1, $result);
    }

    /**
     * @return void
     */
    public function test_add_block_when_engage_is_off(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/engage/db/upgradelib.php");

        $total = $DB->count_records(
            'block_instances',
            [
                'blockname' => 'totara_user_profile',
                'pagetypepattern' => 'user-profile'
            ]
        );

        // Disable engage
        advanced_feature::disable('engage_resources');

        // Add the block.
        totara_engage_create_engage_profile_block();
        $result = $DB->count_records(
            'block_instances',
            [
                'blockname' => 'totara_user_profile',
                'pagetypepattern' => 'user-profile'
            ]
        );

        $this->assertEquals($total, $result);
    }
}
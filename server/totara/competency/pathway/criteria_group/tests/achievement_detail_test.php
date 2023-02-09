<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

use pathway_criteria_group\achievement_detail;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group totara_competency
 */
class pathway_criteria_group_achievement_detail_testcase extends advanced_testcase {

    public function test_get_achieved_via_strings_empty() {
        $detail = new achievement_detail();
        $detail->set_related_info([]);
        $this->assertSame([], $detail->get_achieved_via_strings());
    }

    public function test_get_achieved_via_strings_one() {
        /** @var achievement_detail|MockObject $detail */
        $detail = $this->getMockBuilder(achievement_detail::class)
            ->setMethods(['get_achievement_via_string'])
            ->getMock();
        $detail->method('get_achievement_via_string')
            ->willReturnCallback(function ($criteria_pluginname) {
                switch ($criteria_pluginname) {
                    case 'a':
                        return 'A';
                    case 'b':
                        return 'B';
                }

                return 'C';
            });

        $detail->set_related_info(['b']);
        $this->assertSame(['B'], $detail->get_achieved_via_strings());
    }

    public function test_get_achieved_via_strings_duplicates() {
        /** @var achievement_detail|MockObject $detail */
        $detail = $this->getMockBuilder(achievement_detail::class)
            ->setMethods(['get_achievement_via_string'])
            ->getMock();
        $detail->method('get_achievement_via_string')
            ->willReturnCallback(function ($criteria_pluginname) {
                switch ($criteria_pluginname) {
                    case 'a':
                        return 'A';
                    case 'b':
                        return 'B';
                }

                return 'C';
            });

        $detail->set_related_info(['b', 'b']);
        $this->assertSame(['B', 'B'], $detail->get_achieved_via_strings());
    }

    public function test_get_achieved_via_strings_multiple_different() {
        /** @var achievement_detail|MockObject $detail */
        $detail = $this->getMockBuilder(achievement_detail::class)
            ->setMethods(['get_achievement_via_string'])
            ->getMock();
        $detail->method('get_achievement_via_string')
            ->willReturnCallback(function ($criteria_pluginname) {
                switch ($criteria_pluginname) {
                    case 'a':
                        return 'A';
                    case 'b':
                        return 'B';
                    case 'c':
                        return 'C';
                }

                return null;
            });

        $detail->set_related_info(['b', 'a', 'b', 'c']);
        $this->assertContains('B', $detail->get_achieved_via_strings());
        $this->assertContains('A', $detail->get_achieved_via_strings());
        $this->assertContains('C', $detail->get_achieved_via_strings());
    }

    /**
     * For the activity log, we need an end-user understandable string to identify how a competency was achieved via the criteria.
     */
    public function test_all_criteria_plugins_have_achieved_via_string() {
        $criteria_plugins = array_keys(core_component::get_plugin_list('criteria'));
        foreach ($criteria_plugins as $plugin) {
            $this->assertTrue(
                get_string_manager()->string_exists('achievement_via', 'criteria_' . $plugin),
                "Must define \$string['achievement_via'] = 'xyz'; in the criteria_{$plugin} lang string file!\n" .
                "This is needed for displaying how a competency value was achieved in the activity log."
            );
            $this->assertNotEmpty((new achievement_detail())->get_achievement_via_string($plugin));
        }
    }

}

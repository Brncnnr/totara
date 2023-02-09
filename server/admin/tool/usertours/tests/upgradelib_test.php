<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package tool_usertours
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create records with patchmatch that have '%' in different places (or doesn't have at all) and confirms that upgrade process goes as expected.
 */
class tool_usertours_upgradelib_testcase extends advanced_testcase {
    public function test_tool_usertours_upgrade_addsuffixwildcard(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/admin/tool/usertours/db/upgradelib.php');

        $patterns = [
            '/totara/dashboard/index.php?id=1' => '/totara/dashboard/index.php?id=1%',
            '/totara/dashboard/index.php?id=1%' => '/totara/dashboard/index.php?id=1%',
            '/totara/dashboard/index.php?id=%&test=11' => '/totara/dashboard/index.php?id=%&test=11%',
            '%/user/profile.php' => '%/user/profile.php%',
            '%/user/profile.php%' => '%/user/profile.php%',
            '/my/%' => '/my/%',
            '/my/profile.php?param1=%&param2=%' => '/my/profile.php?param1=%&param2=%',
            'FRONTPAGE' => 'FRONTPAGE',
            '/?%' => '/?%',
        ];
        $oldrecords = [];
        $i = 0;
        foreach ($patterns as $original => $expected) {
            $i++;
            $record = (object)[
                'id' => null,
                'name' => 'Name ' . $i,
                'description' => 'Description ' . $i,
                'pathmatch' => $original,
                'enabled' => (string)($i % 2),
                'sortorder' => (string)$i,
                'configdata' => 'Config '. $i
            ];
            $id = $DB->insert_record('tool_usertours_tours', $record);

            $record->id = (string)$id;
            $record->pathmatch = $expected;
            $oldrecords[$id] = $record;
        }

        tool_usertours_upgrade_addsuffixwildcard();

        $upgraded = $DB->get_records('tool_usertours_tours');
        foreach ($upgraded as $id => $newrecord) {
            $this->assertEquals($oldrecords[$id], $newrecord);
        }

    }
}
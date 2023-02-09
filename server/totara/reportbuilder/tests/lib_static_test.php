<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simonc@catalyst.net.nz>
 * @package totara
 * @subpackage reportbuilder
 *
 * Unit tests for static functions in totara/reportbuilder/lib.php and
 * any other tests that don't require the monster setup occurring within
 * totara/reportbuilder/tests/lib_test.php
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_lib_static_testcase extends \core_phpunit\testcase {
    use \totara_reportbuilder\phpunit\report_testing;

    /**
     * Test \reportbuilder::find_source_dirs
     */
    public function test_find_source_dirs() {
        $key = 'all';

        // Ensure its not in the cache.
        $cache = cache::make('totara_reportbuilder', 'rb_source_directories');
        $cache->delete($key);
        $this->assertFalse($cache->get($key));

        // Generate the directories.
        $generateddirs = \reportbuilder::find_source_dirs(true);
        $this->assertIsArray($generateddirs);

        // Get it from the cache.
        $cacheddirs = $cache->get($key);
        $this->assertIsArray($cacheddirs);

        // Confirm that it is the exact same list from the method and the cache.
        $this->assertSame($generateddirs, $cacheddirs);

        // Now check that if we request it from find_source_dirs again its still the same,
        // this time however it will come internally from the cache.
        $this->assertSame($generateddirs, \reportbuilder::find_source_dirs());
    }

    /**
     * Test \reportbuilder::get_filtered_count
     */
    public function test_get_filtered_count() {
        global $CFG, $DB;

        // skip this due to can not change dboptions dynamically, should set `logall` in config.php
        $this->markTestSkipped("performance test only");

        if (empty($CFG->dboptions['logall'])) {
            $CFG->dboptions['logall'] = true;
        }

        self::setAdminUser();

        $rid = $this->create_report('courses', 'Test courses report 1');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        list($sql, $params, $cache) = $report->build_query();

        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(0, $records);

        $num = $report->get_filtered_count();

        $this->assertEquals(0, $num);

        $queries = $DB->get_records_select('log_queries', "sqltext like '%COUNT%' and backtrace like '%get_filtered_count%'");

        $this->assertCount(2, $queries);
    }

    /**
     * Test \reportbuilder::find_source_dirs
     */
    public function test_find_source_dirs_including_well_known_dirs() {
        // List of locations to search pre TL-30377
        $locations = array(
            'auth',
            'mod',
            'block',
            'tool',
            'totara',
            'local',
            'enrol',
            'repository',
        );
        $oldsourcedirs = [];
        foreach ($locations as $modtype) {
            foreach (core_component::get_plugin_list($modtype) as $mod => $path) {
                $dir = "$path/rb_sources/";
                if (file_exists($dir) && is_dir($dir)) {
                    $oldsourcedirs[] = $dir;
                }
            }
        }

        // Source directories from find_source_dirs()
        $generateddirs = \reportbuilder::find_source_dirs(true);

        // Make sure each of the old directories is also in the new (potentially bigger) list of source directories.
        foreach ($oldsourcedirs as $path) {
            $this->assertContains($path, $generateddirs, 'Expected to find rb_sources in '.$path);
        }
    }
}

<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the cache handler class for catalog component.
 *
 * @group totara_catalog
 */
class totara_catalog_cache_handler_testcase extends advanced_testcase {

    public function test_reset_all_caches() {

        // Providers are enabled by default, so there is some data.
        $this->assertGreaterThan(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertGreaterThan(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertNotEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertTrue(\totara_catalog\local\config::instance()->is_provider_active('course'));

        // Switch off the providers.
        set_config('learning_types_in_catalog', "[]", 'totara_catalog');

        // Check that the providers are still using the old cache (to prove our test is valid).
        $this->assertGreaterThan(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertGreaterThan(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertNotEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertTrue(\totara_catalog\local\config::instance()->is_provider_active('course'));

        // Reset the caches.
        \totara_catalog\cache_handler::reset_all_caches();

        // Show that the singletons now return the updated information, so must have been reset.
        $this->assertEquals(1, count(\totara_catalog\local\feature_handler::instance()->get_all_features()));
        $this->assertEquals(5, count(\totara_catalog\local\filter_handler::instance()->get_all_filters()));
        $this->assertEmpty(\totara_catalog\provider_handler::instance()->get_active_providers());
        $this->assertFalse(\totara_catalog\local\config::instance()->is_provider_active('course'));
    }

    /**
     * Test the cache priming for the catalog loads the expected data.
     */
    public function test_prime_catalog_categories_caches() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        $generator = $this->getDataGenerator();

        $multitenancy = $generator->get_plugin_generator('totara_tenant');
        $multitenancy->enable_tenants();
        set_config('tenantsisolated', 0);

        $cats = [];
        $misc = $DB->get_record('course_categories', ['name' => 'Miscellaneous']);

        // Create tenants with a sub category.
        $tenant1 = $multitenancy->create_tenant();
        $cats[] = $subt1 = $generator->create_category(['parent' => $tenant1->categoryid]);

        $tenant2 = $multitenancy->create_tenant();
        $cats[] = $subt2 = $generator->create_category(['parent' => $tenant2->categoryid]);

        $cats[] = $catp1 = $generator->create_category();
        $cats[] = $sub11 = $generator->create_category(['parent' => $catp1->id]);
        $cats[] = $sub12 = $generator->create_category(['parent' => $catp1->id]);
        $cats[] = $sub121 = $generator->create_category(['parent' => $sub12->id]);

        $cats[] = $catp2 = $generator->create_category();
        $cats[] = $sub21 = $generator->create_category(['parent' => $catp2->id]);
        $cats[] = $sub22 = $generator->create_category(['parent' => $catp2->id]);
        $cats[] = $sub221 = $generator->create_category(['parent' => $sub22->id]);

        // load the caches.
        $catscache = cache::make('core', 'coursecat');
        $catsrecordcache = cache::make('core', 'coursecatrecords');

        // purge the caches.
        $catscache->purge();
        $catsrecordcache->purge();
        $this->assertEmpty($catscache->get('isprimed'));

        // prime the caches.
        \coursecat::prime_catalog_categories_caches();

        // check the records cache.
        $this->assertTrue($catscache->get('isprimed'));
        foreach ($cats as $cat) {
            self::compare_categories($catsrecordcache->get($cat->id), $cat);
        }

        // Check the system categories aren't in there.
        $system = $DB->get_records('course_categories', ['issystem' => 1]);
        foreach ($system as $syscat) {
            $this->assertEmpty($catsrecordcache->get($syscat->id));
        }

        $structure = [
            0 => [$misc->id, $tenant1->categoryid, $tenant2->categoryid, $catp1->id, $catp2->id],
            $tenant1->categoryid => [$subt1->id],
            $tenant2->categoryid => [$subt2->id],
            $catp1->id => [$sub11->id, $sub12->id],
            $sub12->id => [$sub121->id],
            $catp2->id => [$sub21->id, $sub22->id],
            $sub22->id => [$sub221->id]
        ];

        $sortfields = array('sortorder' => 1);
        foreach ($structure as $parent => $children) {
            $cache_key = 'c'. $parent . ':' .  serialize($sortfields) . ':0';

            $values = $catscache->get($cache_key);
            $this->assertSame(count($children), count($values));
            foreach ($children as $cid) {
                $this->assertContains((int) $cid, $values);
            }
        }
    }

    /**
     * Quick function to call a bunch of asserts on 2 category records
     * to avoid the $fromcache failure.
     */
    private static function compare_categories($cat1, $cat2) {
        self::assertSame($cat1->id, $cat2->id);
        self::assertSame($cat1->idnumber, $cat2->idnumber);
        self::assertSame($cat1->name, $cat2->name);
        self::assertSame($cat1->sortorder, $cat2->sortorder);
        self::assertSame($cat1->depth, $cat2->depth);
        self::assertSame($cat1->path, $cat2->path);
    }
}

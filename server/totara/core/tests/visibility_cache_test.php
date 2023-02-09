<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

use \totara_core\local\visibility;
use \totara_core\visibility_controller;

/**
 * Test Totara visibility cache
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_core_visibility_cache_testcase
 *
 */
class totara_core_visibility_cache_testcase extends advanced_testcase {

    private const REGEX_PARAM = '#\:[a-z_]+_\d+#';

    public function test_course_basics() {
        $resolver = visibility_controller::course();
        self::assertInstanceOf(visibility\course\traditional::class, $resolver);
        self::assertNotInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\course\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$original_sql, $original_params] = $resolver->sql_where_visible(189, 'c');

        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'phpunit', 'test');
        $resolver = visibility_controller::course($cache);
        self::assertNotInstanceOf(visibility\course\traditional::class, $resolver);
        self::assertInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\course\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$sql, $params] = $resolver->sql_where_visible(189, 'c');

        self::assertSame(
            preg_replace(self::REGEX_PARAM, '?', $original_sql),
            preg_replace(self::REGEX_PARAM, '?', $sql)
        );

        self::assertSame(
            array_values($original_params),
            array_values($params)
        );
    }

    public function test_program_basics() {
        $resolver = visibility_controller::program();
        self::assertInstanceOf(visibility\program\traditional::class, $resolver);
        self::assertNotInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\program\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$original_sql, $original_params] = $resolver->sql_where_visible(189, 'c');

        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'phpunit', 'test');
        $resolver = visibility_controller::program($cache);
        self::assertNotInstanceOf(visibility\program\traditional::class, $resolver);
        self::assertInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\program\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$sql, $params] = $resolver->sql_where_visible(189, 'c');

        self::assertSame(
            preg_replace(self::REGEX_PARAM, '?', $original_sql),
            preg_replace(self::REGEX_PARAM, '?', $sql)
        );

        self::assertSame(
            array_values($original_params),
            array_values($params)
        );
    }

    public function test_certification_basics() {
        $resolver = visibility_controller::certification();
        self::assertInstanceOf(visibility\certification\traditional::class, $resolver);
        self::assertNotInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\certification\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$original_sql, $original_params] = $resolver->sql_where_visible(189, 'c');

        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'phpunit', 'test');
        $resolver = visibility_controller::certification($cache);
        self::assertNotInstanceOf(visibility\certification\traditional::class, $resolver);
        self::assertInstanceOf(visibility\cache::class, $resolver);
        self::assertInstanceOf(visibility\resolver::class, $resolver);

        self::assertSame('.', $resolver->sql_separator());
        $resolver->set_sql_separator('_');
        self::assertSame('_', $resolver->sql_separator());

        self::assertTrue($resolver->skip_checks_for_admin());
        $resolver->set_skip_checks_for_admin(false);
        self::assertFalse($resolver->skip_checks_for_admin());

        self::assertInstanceOf(visibility\certification\map::class, $resolver->map());
        self::assertSame('visible', $resolver->sql_field_visible());

        [$sql, $params] = $resolver->sql_where_visible(189, 'c');

        self::assertSame(
            preg_replace(self::REGEX_PARAM, '?', $original_sql),
            preg_replace(self::REGEX_PARAM, '?', $sql)
        );
    }

    public function test_get_visible_traditional() {
        global $DB;

        $fields = ['id', 'idnumber'];

        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        foreach (visibility\map::view_hidden_capabilities() as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context);
        }

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($roleid, $user2->id);

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $this->getDataGenerator()->create_course(['idnumber' => 'course_1', 'visible' => 1, 'category' => $category1->id]);
        $this->getDataGenerator()->create_course(['idnumber' => 'course_2', 'visible' => 0, 'category' => $category1->id]);

        /** @var \totara_program\testing\generator $proggen */
        $proggen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $proggen->create_program(['idnumber' => 'program_1', 'visible' => 0, 'category' => $category1->id]);
        $proggen->create_program(['idnumber' => 'program_2', 'visible' => 1, 'category' => $category1->id]);
        $proggen->create_certification(['idnumber' => 'certification_3', 'visible' => 0, 'category' => $category1->id]);
        $proggen->create_certification(['idnumber' => 'certification_4', 'visible' => 1, 'category' => $category1->id]);

        array_map(function (visibility\map $map) {
            $map->recalculate_complete_map();
        }, visibility_controller::get_all_maps());

        $course_uncached = visibility_controller::course();
        $program_uncached = visibility_controller::program();
        $certification_uncached = visibility_controller::certification();

        $cache = cache::make_from_params(cache_store::MODE_REQUEST, 'phpunit', 'test');

        $course_cached = visibility_controller::course($cache);
        $program_cached = visibility_controller::program($cache);
        $certification_cached = visibility_controller::certification($cache);

        $expected_user1 = [
            'course' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {course} WHERE visible=1 AND category <> 0 ORDER BY sortorder ASC'),
            'program' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {prog} WHERE visible=1 AND certifid IS NULL ORDER BY sortorder ASC'),
            'certification' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {prog} WHERE visible=1 AND certifid IS NOT NULL ORDER BY sortorder ASC'),
        ];

        $expected_user2 = [
            'course' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {course} WHERE category <> 0 ORDER BY sortorder ASC'),
            'program' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {prog} WHERE certifid IS NULL ORDER BY sortorder ASC'),
            'certification' =>
                $DB->get_fieldset_sql('SELECT idnumber FROM {prog} WHERE certifid IS NOT NULL ORDER BY sortorder ASC'),
        ];

        $ids = function($item) {
            return $item->idnumber;
        };

        $actual_uncached_user1 = $course_uncached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_cached_user1 = $course_cached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_uncached_user2 = $course_uncached->get_visible_in_category($category1->id, $user2->id, $fields);
        $actual_cached_user2 = $course_cached->get_visible_in_category($category1->id, $user2->id, $fields);
        self::assertSame(
            $expected_user1['course'],
            array_values(array_map($ids, $actual_uncached_user1))
        );
        self::assertSame(
            $expected_user1['course'],
            array_values(array_map($ids, $actual_cached_user1))
        );
        self::assertSame(
            $expected_user2['course'],
            array_values(array_map($ids, $actual_uncached_user2))
        );
        self::assertSame(
            $expected_user2['course'],
            array_values(array_map($ids, $actual_cached_user2))
        );
        self::assertSame(
            array_values(array_map($ids, $actual_cached_user2)),
            array_values(array_map($ids, $course_cached->get_visible_in_category($category1->id, $user2->id, $fields)))
        );
        self::assertSame(
            [],
            array_values(array_map($ids, $course_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );
        self::assertSame(
            array_values(array_map($ids, $course_cached->get_visible_in_category($category2->id, $user2->id, $fields))),
            array_values(array_map($ids, $course_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );

        $actual_uncached_user1 = $program_uncached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_cached_user1 = $program_cached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_uncached_user2 = $program_uncached->get_visible_in_category($category1->id, $user2->id, $fields);
        $actual_cached_user2 = $program_cached->get_visible_in_category($category1->id, $user2->id, $fields);
        self::assertSame(
            $expected_user1['program'],
            array_values(array_map($ids, $actual_uncached_user1))
        );
        self::assertSame(
            $expected_user1['program'],
            array_values(array_map($ids, $actual_cached_user1))
        );
        self::assertSame(
            $expected_user2['program'],
            array_values(array_map($ids, $actual_uncached_user2))
        );
        self::assertSame(
            $expected_user2['program'],
            array_values(array_map($ids, $actual_cached_user2))
        );
        self::assertSame(
            array_values(array_map($ids, $actual_cached_user2)),
            array_values(array_map($ids, $program_cached->get_visible_in_category($category1->id, $user2->id, $fields)))
        );
        self::assertSame(
            [],
            array_values(array_map($ids, $program_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );
        self::assertSame(
            array_values(array_map($ids, $program_cached->get_visible_in_category($category2->id, $user2->id, $fields))),
            array_values(array_map($ids, $program_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );

        $actual_uncached_user1 = $certification_uncached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_cached_user1 = $certification_cached->get_visible_in_category($category1->id, $user1->id, $fields);
        $actual_uncached_user2 = $certification_uncached->get_visible_in_category($category1->id, $user2->id, $fields);
        $actual_cached_user2 = $certification_cached->get_visible_in_category($category1->id, $user2->id, $fields);
        self::assertSame(
            $expected_user1['certification'],
            array_values(array_map($ids, $actual_uncached_user1))
        );
        self::assertSame(
            $expected_user1['certification'],
            array_values(array_map($ids, $actual_cached_user1))
        );
        self::assertSame(
            $expected_user2['certification'],
            array_values(array_map($ids, $actual_uncached_user2))
        );
        self::assertSame(
            $expected_user2['certification'],
            array_values(array_map($ids, $actual_cached_user2))
        );
        self::assertSame(
            array_values(array_map($ids, $actual_cached_user2)),
            array_values(array_map($ids, $certification_cached->get_visible_in_category($category1->id, $user2->id, $fields)))
        );
        self::assertSame(
            [],
            array_values(array_map($ids, $certification_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );
        self::assertSame(
            array_values(array_map($ids, $certification_cached->get_visible_in_category($category2->id, $user2->id, $fields))),
            array_values(array_map($ids, $certification_cached->get_visible_in_category($category2->id, $user2->id, $fields)))
        );
    }

}
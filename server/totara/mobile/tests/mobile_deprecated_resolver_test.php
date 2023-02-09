<?php
/*
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_core\user_learning\item_helper;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara core learning item type resolver.
 */
class totara_mobile_deprecated_resolver_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve_item($field, $learning_item, array $args = []) {
        return $this->resolve_graphql_type('mobile_currentlearning_item', $field, $learning_item, $args);
    }

    /**
     * Create some users and various learning items.
     * @return []
     */
    private function create_faux_learning_items($format = 'html') {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(['shortname' => 'crs1', 'fullname' => 'course1', 'summary' => 'first course']);

        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student', 'manual');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $program = $prog_gen->create_program(['shortname' => 'prg1', 'fullname' => 'program1', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        $certification = $prog_gen->create_certification(['shortname' => 'crt1', 'fullname' => 'certification1', 'summary' => 'first certification']);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1], [$c3]], CERTIFPATH_RECERT);
        $prog_gen->assign_program($certification->id, [$user->id]);

        return [$user, $course, $program, $certification];
    }

    /**
     * Check that current learning items work as expected but with a deprecation warning;
     */
    public function test_resolve_deprecated_current_learning_item() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);

        $items = item_helper::get_users_current_learning_items($user->id);
        $items = item_helper::expand_learning_item_specialisations($items);

        $item = array_shift($items); // we only need one item.
        $item->itemtype = $item->get_type();
        $item->itemcomponent = $item->get_component();
        if ($item->item_has_duedate()) {
            $item->ensure_duedate_loaded();
        }
        if (method_exists($item, 'get_progress_percentage')) {
            $item->progress = $item->get_progress_percentage();
        }

        $value = $this->resolve_graphql_type('totara_mobile_learning_item', 'id', $item, []);
        self::assertDebuggingCalled(['This class has been deprecated, please use \item in mobile_currentlearning']);
        $this->assertSame($item->get_type() . '_' . $item->id, $value);
    }

    /**
     * Check that current learning query works as expected but with a deprecation warning;
     */
    public function test_resolve_deprecated_current_learning_query() {
        list($user, $course, $program, $certification) = $this->create_faux_learning_items();
        $this->setUser($user);

        $items = $this->resolve_graphql_query('totara_mobile_current_learning', []);
        self::assertDebuggingCalled(['This class has been deprecated, please use my_items in mobile_currentlearning']);
        $this->assertCount(3, $items);
    }
}

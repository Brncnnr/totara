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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_hierarchy
 */

use core_phpunit\testcase;
use totara_hierarchy\totara_notification\placeholder\organisation as organisation_placeholder_group;
use totara_notification\placeholder\option;
use totara_hierarchy\testing\generator as totara_hierarchy_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group approval_workflow
 */
class mod_approval_totara_notification_organisation_placeholder_testcase extends testcase {
    public function test_get_placeholders(): void {
        // Make devs aware they should extend this test when adding placeholders.
        $option_keys = array_map(static function (option $option) {
            return $option->get_key();
        }, organisation_placeholder_group::get_options());
        self::assertEqualsCanonicalizing(
            [
                'short_name',
                'full_name',
                'id_number',
            ],
            $option_keys,
            'Please add missing placeholders to test coverage.'
        );

        // Create an organisation.
        $totara_hierarchy_generator = totara_hierarchy_generator::instance();
        $organisation_framework = $totara_hierarchy_generator->create_framework('organisation');
        $organisation = $totara_hierarchy_generator->create_hierarchy($organisation_framework->id, 'organisation');

        $placeholder_group = organisation_placeholder_group::from_record($organisation);

        // Check each placeholder.
        self::assertEquals('', $placeholder_group->do_get('short_name'));
        $organisation->shortname = 'test_short_name';
        $placeholder_group = organisation_placeholder_group::from_record($organisation);
        self::assertEquals('test_short_name', $placeholder_group->do_get('short_name'));

        self::assertEquals($organisation->fullname, $placeholder_group->do_get('full_name'));

        self::assertEquals($organisation->idnumber, $placeholder_group->do_get('id_number'));
        $organisation->idnumber = '';
        $placeholder_group = organisation_placeholder_group::from_record($organisation);
        self::assertEquals('', $placeholder_group->do_get('id_number'));
    }

    public function test_not_available(): void {
        $placeholder_group = new organisation_placeholder_group(null);
        self::assertEquals('', $placeholder_group->get('full_name'));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('The organisation record is empty');
        $placeholder_group->do_get('full_name');
    }

    public function test_instances_are_cached(): void {
        global $DB;

        $totara_hierarchy_generator = totara_hierarchy_generator::instance();
        $organisation_framework = $totara_hierarchy_generator->create_framework('organisation');
        $organisation1 = $totara_hierarchy_generator->create_hierarchy($organisation_framework->id, 'organisation');
        $organisation2 = $totara_hierarchy_generator->create_hierarchy($organisation_framework->id, 'organisation');

        $query_count = $DB->perf_get_reads();
        organisation_placeholder_group::from_id($organisation1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        organisation_placeholder_group::from_id($organisation1->id);
        self::assertEquals($query_count + 1, $DB->perf_get_reads());

        organisation_placeholder_group::from_id($organisation2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());

        organisation_placeholder_group::from_id($organisation1->id);
        organisation_placeholder_group::from_id($organisation2->id);
        self::assertEquals($query_count + 2, $DB->perf_get_reads());
    }
}
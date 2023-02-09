<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 * @category test
 */

use core\entity\user;
use core\orm\query\builder;
use totara_core\phpunit\webservice_utils;
use totara_evidence\customfield_area\evidence;
use totara_evidence\entity;
use totara_evidence\models;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_service_type_testcase extends totara_evidence_testcase {

    use webservice_utils;

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/externallib.php');
        require_once($CFG->dirroot . '/webservice/tests/helpers.php');
    }

    /**
     * Test the data service method returns value and handles permissions correctly
     */
    public function test_service_type_data(): void {
        self::setGuestUser();
        $fail_type = $this->generator()->create_evidence_type_entity();
        $fail = $this->call_webservice_api('totara_evidence_type_data', [
            'id' => $fail_type->id
        ]);
        $this->assert_webservice_has_exception_message('permissions', $fail);

        self::setAdminUser();
        $types = [];
        for ($i = 0; $i < 3; $i++) {
            $type = $this->generator()->create_evidence_type(['name' => "Test Data $i Name"]);
            for ($j = 0; $j < $i * 5; $j++) {
                $this->generator()->create_evidence_item(['typeid' => $type->get_id()]);
                $this->generator()->create_evidence_field(['typeid' => $type->get_id(), 'sortorder' => $i]);
            }
            $types[] = array_merge($type->get_data(), [
                'edit_url' => evidence::get_url($type->get_id())
            ]);
        }

        foreach ($types as $type) {
            $response = $this->call_webservice_api('totara_evidence_type_data', [
                'id' => $type['id']
            ]);
            $this->assert_webservice_success($response);

            // There is no guarantee the system returns the $response['data']['fields']
            // value with the same order as the type['fields'] value. This is why this
            // attribute has to be validated separately - need to be sorted first.
            $expected_fields = $type['fields'];
            unset($type['fields']);

            $actual = $response['data'];
            $actual_fields = $actual['fields'];
            unset($actual['fields']);

            $this->assertEquals($type, $actual);
            $this->assertEqualsCanonicalizing($expected_fields, $actual_fields);
        }
    }

    /**
     * Test the data service method returns value and handles permissions correctly
     */
    public function test_service_type_details(): void {
        $role = builder::table('role')->where('shortname', 'user')->value('id');
        self::setGuestUser();
        $fail_type = $this->generator()->create_evidence_type_entity();
        unassign_capability('totara/evidence:viewanyevidenceonself', $role);
        $fail = $this->call_webservice_api('totara_evidence_type_details', [
            'type_id' => $fail_type->id,
            'user_id' => user::logged_in()->id,
        ]);
        $this->assert_webservice_has_exception_message('permissions', $fail);

        self::setAdminUser();
        $fail_type->delete();
        $types = [];
        for ($i = 0; $i < 3; $i++) {
            $types[] = [
                'name' => "Test Details $i Name",
                'description' => "Test Details $i Name",
            ];
            $this->generator()->create_evidence_type($types[$i]);
        }

        $i = 0;
        foreach ($this->type_repository()->get()->all() as $type) {
            $response = $this->call_webservice_api('totara_evidence_type_details', [
                'type_id' => $type->id,
                'user_id' => user::logged_in()->id,
            ]);
            $this->assert_webservice_success($response);
            $this->assertEquals($types[$i]['name'], $response['data']['name']);
            $this->assertStringContainsString($types[$i]['description'], $response['data']['description']);
            $i++;
        }
    }

    /**
     * Test the delete service method deletes type
     */
    public function test_service_type_delete(): void {
        self::setAdminUser();
        $type = $this->generator()->create_evidence_type(['name' => 0]);
        $this->assertCount(1, $this->types());

        $item = $this->generator()->create_evidence_item(['typeid' => $type->get_id()]);
        $response = $this->call_webservice_api('totara_evidence_type_delete', [
            'id' => $type->get_id()
        ]);
        $this->assert_webservice_error($response);
        $this->assert_webservice_has_exception_message('currently in use elsewhere', $response);
        $this->assertCount(1, $this->types());

        $item->delete();
        $response = $this->call_webservice_api('totara_evidence_type_delete', [
            'id' => $type->get_id()
        ]);
        $this->assert_webservice_success($response);
        $this->assertTrue($response['data']);
        $this->assertCount(0, $this->types());
    }

    /**
     * Test the update_status() service method correctly hides and shows a type
     */
    public function test_service_type_set_status(): void {
        self::setAdminUser();

        $visible = $this->generator()->create_evidence_type(['name' => 'shown', 'status' => models\evidence_type::STATUS_ACTIVE]);
        $hidden = $this->generator()->create_evidence_type(['name' => 'hidden', 'status' => models\evidence_type::STATUS_HIDDEN]);

        $this->assertTrue($visible->is_visible());
        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $visible->get_id(),
            'visible' => false
        ]);
        $this->assert_webservice_success($response);
        $visible = models\evidence_type::load_by_id($visible->get_id());
        $this->assertFalse($visible->is_visible());

        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $visible->get_id(),
            'visible' => false
        ]);
        $this->assert_webservice_success($response);
        $visible = models\evidence_type::load_by_id($visible->get_id());
        $this->assertFalse($visible->is_visible());

        $this->assertFalse($hidden->is_visible());
        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $hidden->get_id(),
            'visible' => true
        ]);
        $this->assert_webservice_success($response);
        $hidden = models\evidence_type::load_by_id($hidden->get_id());
        $this->assertTrue($hidden->is_visible());

        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $hidden->get_id(),
            'visible' => true
        ]);
        $this->assert_webservice_success($response);
        $hidden = models\evidence_type::load_by_id($hidden->get_id());
        $this->assertTrue($hidden->is_visible());


        self::setGuestUser();

        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $visible->get_id(),
            'visible' => true
        ]);
        $this->assert_webservice_error($response);
        $this->assert_webservice_has_exception_message('permissions', $response);

        $response = $this->call_webservice_api('totara_evidence_type_set_visibility', [
            'id'      => $hidden->get_id(),
            'visible' => true
        ]);
        $this->assert_webservice_error($response);
        $this->assert_webservice_has_exception_message('permissions', $response);
    }

    /**
     * Test the search() service method queries type names and doesn't return hidden types
     */
    public function test_service_type_search(): void {
        self::setAdminUser();

        $type_count = 5;
        for ($i = 0; $i < $type_count; $i++) {
            $this->generator()->create_evidence_type(['name' => "Evidence Type %$i (Test)"]);
        }
        for ($i = 0; $i < $type_count; $i++) {
            $this->generator()->create_evidence_type(['name' => "HIDDEN TYPE $i", 'status' => models\evidence_type::STATUS_HIDDEN]);
        }

        // When query is empty, list all types
        $expected = $this->types()->map(static function (entity\evidence_type $type) {
            return [
                'value' => $type->id,
                'label' => $type->name
            ];
        })->all();

        self::setAdminUser();

        $response_one = $this->call_webservice_api('totara_evidence_type_search', [
            'string' => '%'
        ]);
        $response_two = $this->call_webservice_api('totara_evidence_type_search', [
            'string' => '%%'
        ]);
        $this->assert_webservice_success($response_one);
        $this->assert_webservice_success($response_two);
        for ($i = 0; $i < $type_count; $i++) {
            if (stripos($expected[$i]['label'], 'hidden') !== false) {
                $this->assertNotContainsEquals($expected[$i], array_values($response_one['data']));
            } else {
                $this->assertContains($expected[$i], array_values($response_one['data']));
            }
        }
        $this->assertEmpty(array_values($response_two['data']));

        // Actually test each query
        for ($i = 0, $types_count = count($this->types()); $i < $types_count; $i++) {
            $type = $this->types()->all()[$i];
            if (stripos($type->name, 'hidden') !== false) {
                $query = 'hidden';
                $expected = [];
            } else {
                $query = "tYpE %$i (";
                $expected = [
                    [
                        'value' => $type->id,
                        'label' => $type->name
                    ]
                ];
            }

            $response = $this->call_webservice_api('totara_evidence_type_search', [
                'string' => $query
            ]);
            $this->assert_webservice_success($response);
            $this->assertEquals($expected, array_values($response['data']));
        }
    }

    public function test_service_type_search_sorts_items_properly(): void {
        // Enable multi-language filter
        filter_manager::reset_caches();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        $this->generator()->create_evidence_types([
            ['name' => $this->get_multi_lang_description('First 1', 'Second 2')],
            ['name' => $this->get_multi_lang_description('Second 1', 'First 2')],
        ]);

        self::setAdminUser();

        $response = $this->call_webservice_api('totara_evidence_type_search', [
            'string' => ''
        ]);

        $this->assert_webservice_success($response);
        $this->assertEquals([
            'First 2',
            'Second 2',
        ], array_column($response['data'], 'label'));
    }

    /**
     * Test the data service method returns value and handles permissions correctly
     */
    public function test_service_type_get_import_fields(): void {
        self::setAdminUser();

        $types = [];
        for ($i = 1; $i <= 3; $i++) {
            $type = [
                'name' => "Test Import Fields $i Name",
                'description' => "Test Import Fields $i Description",
                'location' => $i === 1 ? models\evidence_type::LOCATION_RECORD_OF_LEARNING : models\evidence_type::LOCATION_EVIDENCE_BANK,
            ];
            $types[$i] = $this->generator()->create_evidence_type($type);

            // Stupid generator creates additional fields if min and max evidence fields are set to defaults!
            $this->generator()->set_min_max(200,45,20,0,0);
            for ($j = 1; $j <= 3; $j++) {
                $this->generator()->create_evidence_field([
                    'typeid' => $types[$i]->id,
                    'fullname' => "Field {$i}-{$j}",
                    'shortname' => "fld{$i}-{$j}",
                    'sortorder' => $j,
                ]);
            }
        }

        foreach ($types as $idx => $type) {
            $response = $this->call_webservice_api('totara_evidence_type_import_fields', [
                'type_id' => $type->id,
            ]);
            $this->assert_webservice_success($response);

            $expected = [];
            for ($j = 1; $j <= 3; $j++) {
                $expected[] = "customfield_fld{$idx}-{$j}";
            }

            self::assertEqualsCanonicalizing($expected, $response['data']);
        }
    }



    /**
     * Generate multi-language string
     *
     * @param string $p1 Placeholder for string 1
     * @param string $p2 Placeholder for string 2
     * @return string
     */
    protected function get_multi_lang_description($p1 = 'Привет', $p2 = 'Hello'): string {
        return '<span class="multilang" lang="ru_ru">' . $p1 . '</span>' .
        '<span class="multilang" lang="en">' . $p2. '</span>';
    }
}

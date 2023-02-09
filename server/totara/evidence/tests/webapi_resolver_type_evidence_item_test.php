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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

use core\format;
use totara_evidence\models\evidence_item;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class webapi_resolver_type_evidence_item_testcase extends totara_evidence_testcase {
    private const TYPE = 'totara_evidence_evidence_item';

    use webapi_phpunit_helper;

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/evidence item/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $item = $this->create_evidence_item();

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $item);
    }

    public function test_resolve() {
        $item = $this->create_evidence_item();

        $testcases = [
            'id' => ['id', null, $item->id],
            'name' => ['name', format::FORMAT_PLAIN, "This is a test name"],
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $item, $args);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

    /**
     * Create evidence item
     *
     * @return evidence_item
     */
    private function create_evidence_item() {
        self::setAdminUser();

        $user = $this->generator()->create_evidence_user();

        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        $field_data = (object) [
            'key' => 'value'
        ];
        $item_name = '<h1>This is a <strong>test</strong> name</h1>';

        $item = evidence_item::create($type, $user, $field_data, $item_name);

        return $item;
    }
}

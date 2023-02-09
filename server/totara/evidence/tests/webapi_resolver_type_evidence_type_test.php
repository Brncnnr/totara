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

use totara_evidence\entity\evidence_type as evidence_type_entity;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class webapi_type_evidence_type_testcase extends totara_evidence_testcase {
    use webapi_phpunit_helper;

    private const TYPE = 'totara_evidence_evidence_type';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/evidence type/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        /** @var evidence_type_entity $course_type_entity */
        $course_type_entity = evidence_type_entity::repository()
            ->where('idnumber', 'coursecompletionimport')->one();
        $type = evidence_type_model::load_by_entity($course_type_entity);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $type);
    }

    public function test_resolve() {
        self::setAdminUser();

        $expected_name = get_string('system_type_name:completion_course', 'totara_evidence');

        /** @var evidence_type_entity $course_type_entity */
        $course_type_entity = evidence_type_entity::repository()
            ->where('idnumber', 'coursecompletionimport')->one();
        $type = evidence_type_model::load_by_entity($course_type_entity);

        $testcases = [
            'id' => ['id', null, $type->id],
            'name' => ['name', null, $expected_name],
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $type, $args);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

}
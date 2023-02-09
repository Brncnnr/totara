<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 * @category test
 */

use core\format;
use core\webapi\execution_context;
use core\entity\cohort as cohort_entity;
use core\webapi\resolver\type\cohort as cohort_type;


/**
 * @coversDefaultClass \core\webapi\resolver\type\cohort
 *
 * @group core_cohort
 */
class core_webapi_resolver_type_cohort_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $webapi_context = $this->get_webapi_context();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/cohort_entity/");
        cohort_type::resolve('id', [], [], $webapi_context);
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        $cohort_entity = $this->create_cohort();
        $webapi_context = $this->get_webapi_context();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");
        cohort_type::resolve($field, $cohort_entity, [], $webapi_context);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        // Note: cannot use dataproviders here because PHPUnit runs these before
        // everything else. Incredibly, if a dataprovider in a random testsuite
        // creates database records or sends messages, etc, those will also be
        // visible to _all_ tests. In other words, with dataproviders, current
        // and yet unwritten tests do not start in a clean state!
        $webapi_context = $this->get_webapi_context();

        $static_cohort = $this->create_cohort();
        $static_type = 'STATIC';

        $html_desc = '<strong>This is a test description</strong>';
        $plain_desc = strtoupper(format_string($html_desc, true));
        $dynamic_cohort = $this->create_cohort(cohort::TYPE_DYNAMIC, $html_desc);
        $dynamic_type = 'DYNAMIC';

        $testcases = [
            'active' => [$static_cohort, 'active', null, $static_cohort->active],
            'default desc' => [$dynamic_cohort, 'description', null, $html_desc],
            'plain desc' => [$dynamic_cohort, 'description', format::FORMAT_PLAIN, $plain_desc],
            'id' => [$static_cohort, 'id', null, $static_cohort->id],
            'idnumber' => [$dynamic_cohort, 'idnumber', null, $dynamic_cohort->idnumber],
            'name' => [$static_cohort, 'name', null, $static_cohort->name],
            'dynamic type' => [$dynamic_cohort, 'type', null, $dynamic_type],
            'static type' => [$static_cohort, 'type', null, $static_type]
        ];

        foreach ($testcases as $id => $testcase) {
            [$source, $field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = cohort_type::resolve($field, $source, $args, $webapi_context);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

    /**
     * Generates a test cohort.
     *
     * @param int $cohort_type cohort type.
     * @param string $description cohort description.
     *
     * @return cohort_entity generated cohort.
     */
    private function create_cohort(
        int $cohort_type = cohort::TYPE_STATIC,
        string $description = ''
    ): cohort_entity {
        $base = $cohort_type === cohort::TYPE_STATIC
            ? 'My static audience'
            : 'My dynamic audience';

        $entity = new cohort_entity([
            'active' => true,
            'component' => '',
            'contextid' => context_system::instance()->id,
            'description' => $description,
            'descriptionformat' => FORMAT_MOODLE,
            'idnumber' => "$base idnumber",
            'name' => $base,
            'cohorttype' => $cohort_type,
            'visible' => true
        ]);

        $entity->save();

        return $entity;
    }

    /**
     * Creates a graphql execution context.
     *
     * @return execution_context the context.
     */
    private function get_webapi_context(): execution_context {
        return execution_context::create('dev', null);
    }
}

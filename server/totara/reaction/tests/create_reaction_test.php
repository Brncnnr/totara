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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_reaction
 */
defined('MOODLE_INTERNAL')|| die();

use core\webapi\execution_context;
use totara_webapi\graphql;
use totara_reaction\reaction;
use totara_reaction\resolver\resolver_factory;
use totara_reaction\reaction_helper;

class totara_reaction_create_reaction_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_reaction_from_graphql(): void {
        global $USER, $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $this->setAdminUser();

        $resolver = new default_reaction_resolver();
        $resolver->set_component('core_course');

        resolver_factory::phpunit_set_resolver($resolver);

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $variables = [
            'component' => 'core_course',
            'instanceid' => $course->id,
            'area' => 'course'
        ];

        $ec = execution_context::create('ajax', 'totara_reaction_create_like');
        $result = graphql::execute_operation($ec, $variables);

        $this->assertNotEmpty($result->data);
        $this->assertEmpty($result->errors);
        $this->assertArrayHasKey('reaction', $result->data);

        $this->assertTrue(
            $DB->record_exists(
                'reaction',
                [
                    'instanceid' => $course->id,
                    'component' => 'core_course',
                    'area' => 'course',
                    'userid' => $USER->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $resolver = new default_reaction_resolver();
        $resolver->set_component('core_course');

        resolver_factory::phpunit_set_resolver($resolver);
        $reaction = reaction_helper::create_reaction($course->id, 'core_course', 'course');

        $this->assertTrue($DB->record_exists('reaction', ['id' => $reaction->get_id()]));

        // Create again and see whether it duplicated the record or not. Expecting only 1 record created.
        reaction_helper::create_reaction($course->id, 'core_course', 'course');

        $this->assertDebuggingCalled();
        $this->assertCount(
            1,
            $DB->get_records(
                'reaction',
                [
                    'component' => 'core_course',
                    'area' => 'course',
                    'instanceid' => $course->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_reaction_with_exception(): void {
        global $USER, $DB;
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $context = \context_course::instance($course->id);
        reaction::create('core_course', 'course', $course->id, $context->id);

        // Create first record, then start creating the second with directly access (without validation)
        $this->assertCount(
            1,
            $DB->get_records(
                'reaction',
                [
                    'component' => 'core_course',
                    'area' => 'course',
                    'instanceid' => $course->id
                ]
            )
        );

        $this->expectException(\dml_write_exception::class);
        reaction::create('core_course', 'course', $course->id, $context->id);
    }
}
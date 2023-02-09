<?php
/*
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use hierarchy_competency\event\evidence_deleted;
use totara_competency\event\linked_courses_updated;
use totara_competency\linked_courses;

/**
 * @group totara_competency
 */
class totara_competency_linked_courses_testcase extends advanced_testcase {

    public function test_get_linked_courses_none() {
        $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $linked_courses = linked_courses::get_linked_courses($comp->id);
        $this->assertEmpty($linked_courses);
    }

    public function test_set_linked_courses_none() {

        $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        linked_courses::set_linked_courses($comp->id, []);

        $linked_courses = linked_courses::get_linked_courses($comp->id);
        $this->assertEmpty($linked_courses);
    }

    public function test_get_and_set_linked_courses_some() {
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);
        $comp2 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        linked_courses::set_linked_courses(
            $comp2->id,
            [
                ['id' => $course3->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ]
        );

        $linked_courses1 = linked_courses::get_linked_courses($comp1->id);
        $this->assertCount(2, $linked_courses1);
        foreach ($linked_courses1 as $course) {
            $this->assertContains($course->id, [$course1->id, $course2->id]);
            $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $course->linktype);
        }

        $linked_courses2 = linked_courses::get_linked_courses($comp2->id);
        $this->assertCount(1, $linked_courses2);
        $course = array_pop($linked_courses2);
        $this->assertEquals($course->id, $course3->id);
        $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $course->linktype);

        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL]
            ]
        );

        $linked_courses1 = linked_courses::get_linked_courses($comp1->id);
        $this->assertCount(2, $linked_courses1);
        foreach ($linked_courses1 as $course) {
            switch ($course->id) {
                case $course1->id:
                    $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $course->linktype);
                    break;
                case $course2->id:
                    $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $course->linktype);
                    break;
                default:
                    $this->fail('Unexpected course included in linked courses');
            }
        }

        $linked_courses2 = linked_courses::get_linked_courses($comp2->id);
        $this->assertCount(1, $linked_courses2);
        $course = array_pop($linked_courses2);
        $this->assertEquals($course->id, $course3->id);
        $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $course->linktype);

        linked_courses::set_linked_courses($comp1->id, []);

        $linked_courses1 = linked_courses::get_linked_courses($comp1->id);
        $this->assertCount(0, $linked_courses1);

        $linked_courses2 = linked_courses::get_linked_courses($comp2->id);
        $this->assertCount(1, $linked_courses2);
        $course = array_pop($linked_courses2);
        $this->assertEquals($course->id, $course3->id);
        $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $course->linktype);
    }

    public function test_get_and_set_linked_courses_event_is_fired() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $sink = $this->redirectEvents();

        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);
        $this->assertInstanceOf(linked_courses_updated::class, $event);
        $this->assertEquals($comp1->id, $event->get_data()['objectid']);
        $sink->clear();

        // No delete some
        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        $events = $sink->get_events();
        $this->assertCount(2, $events);
        foreach ($events as $idx => $event) {
            if ($event instanceof linked_courses_updated) {
                $this->assertEquals($comp1->id, $event->objectid);
                unset($events[$idx]);
            } else if ($event instanceof evidence_deleted) {
                $this->assertEquals($comp1->id, $event->other['competencyid']);
                $this->assertEquals('coursecompletion', $event->other['itemtype']);
                $this->assertEquals($course2->id, $event->other['instanceid']);
            }
        }

        $sink->close();
    }

    /**
     * @param int $numberofcourses
     * @dataProvider courses_number_data_provider
     */
    public function test_set_linked_courses_updates_linked_course_count_of_competency(int $numberofcourses): void {
        $courses_to_update = [];
        for ($i = 0; $i < $numberofcourses; $i++) {
            $courses_to_update[] = self::getDataGenerator()->create_course();
        }
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $count_before = $this->get_linked_course_count($comp1->id);
        $this->assertEquals(0, $count_before);

        $courses = array_map(static function (object $course) {
            return ['id' => $course->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY];
        }, $courses_to_update);

        linked_courses::set_linked_courses($comp1->id, $courses);

        $count_after = $this->get_linked_course_count($comp1->id);
        $this->assertCount($count_after, $courses_to_update);
    }

    public function courses_number_data_provider(): array {
        // NOTE: data providers MUST NOT modify database!
        return [
            'Update all' => [2],
            'Remove all' => [0],
        ];
    }

    public function test_remove_course(): void {
        [$course_id, [$competency1, $competency2]] = $this->link_course_to_multiple_competencies();
        [$other_course_id, [$other_competency1, $other_competency2]] = $this->link_course_to_multiple_competencies();

        // Precondition check, check count is originally correct.
        $before_removal1 = Linked_courses::get_linked_courses($competency1->id);
        $this->assertCount(1, $before_removal1);

        $before_removal2 = Linked_courses::get_linked_courses($competency2->id);
        $this->assertCount(1, $before_removal2);

        linked_courses::remove_course($course_id);

        $after_removal1 = Linked_courses::get_linked_courses($competency1->id);
        $this->assertCount(0, $after_removal1);

        $after_removal2 = Linked_courses::get_linked_courses($competency2->id);
        $this->assertCount(0, $after_removal2);

        // Check the other/unrelated courses and competencies are not touched by the removal of the course
        $other_before_removal1 = Linked_courses::get_linked_courses($other_competency1->id);
        $this->assertCount(1, $other_before_removal1);

        $other_before_removal2 = Linked_courses::get_linked_courses($other_competency2->id);
        $this->assertCount(1, $other_before_removal2);
    }

    public function test_remove_course_updates_competency_count(): void {
        [$course_id, [$competency1, $competency2]] = $this->link_course_to_multiple_competencies();

        $count_before1 = $this->get_linked_course_count($competency1->id);
        $this->assertEquals(1, $count_before1);

        $count_before2 = $this->get_linked_course_count($competency2->id);
        $this->assertEquals(1, $count_before2);

        linked_courses::remove_course($course_id);

        $count_after1 = $this->get_linked_course_count($competency1->id);
        $this->assertEquals(0, $count_after1);

        $count_after2 = $this->get_linked_course_count($competency2->id);
        $this->assertEquals(0, $count_after2);
    }

    public function test_remove_course_fires_events(): void {
        [$course_id, [$competency1, $competency2]] = $this->link_course_to_multiple_competencies();
        $competency_course_ids = array_keys($this->get_competency_criteria_by_course($course_id));

        $sink = $this->redirectEvents();
        linked_courses::remove_course($course_id);

        $events = $sink->get_events();

        $this->assertCount(4, $events);

        // Check new courses updated events are fired
        $courses_updated_events = array_filter($events, static function (core\event\base $event) {
            return $event instanceof linked_courses_updated;
        });

        $this->assertEqualsCanonicalizing([$competency1->id, $competency2->id], array_column($courses_updated_events, 'objectid'), 'The events should contain the competency ids');

        // Check legacy evidence deleted events are fired (for backwards compatibility
        $evidence_deleted_events = array_filter($events, static function (core\event\base $event) {
            return $event instanceof evidence_deleted;
        });

        $this->assertEqualsCanonicalizing($competency_course_ids, array_column($evidence_deleted_events, 'objectid'), 'The events should contain the competency_criteria ids');
    }

    public function test_update_linktype() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $orig_rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(2, count($orig_rows));

        $mandatory = array_filter($orig_rows, function ($row) {
            return $row->linktype == linked_courses::LINKTYPE_MANDATORY;
        });

        $optional = array_filter($orig_rows, function ($row) {
            return $row->linktype == linked_courses::LINKTYPE_OPTIONAL;
        });

        $this->assertSame(2, count($mandatory));
        $this->assertSame(0, count($optional));

        // Now for the tests
        $first = reset($orig_rows);

        $event_sink = $this->redirectEvents();

        // No changes - no expected event
        linked_courses::update_linktype($first->id, linked_courses::LINKTYPE_MANDATORY);

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(2, count($rows));

        $mandatory = array_filter($orig_rows, function ($row) {
            return $row->linktype == linked_courses::LINKTYPE_MANDATORY;
        });

        $optional = array_filter($orig_rows, function ($row) {
            return $row->linktype == linked_courses::LINKTYPE_OPTIONAL;
        });

        $this->assertSame(2, count($mandatory));
        $this->assertSame(0, count($optional));
        $this->assertSame(0, $event_sink->count());

        // Change type
        linked_courses::update_linktype($first->id, linked_courses::LINKTYPE_OPTIONAL);
        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(2, count($rows));
        foreach ($rows as $row) {
            if ($row->id == $first->id) {
                $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $row->linktype);
            } else {
                $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $row->linktype);
            }
        }

        $events = $event_sink->get_events();
        $this->assertSame(1, count($events));
        $event = reset($events);
        $this->assertTrue($event instanceof linked_courses_updated);
        $this->assertEquals($comp1->id, $event->objectid);

        $event_sink->clear();

        // Now try to set it to something illegal
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid linktype');
        linked_courses::update_linktype($first->id, -5);

        $event_sink->close();
    }

    public function test_add_linked_courses() {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();
        $course4 = $this->getDataGenerator()->create_course();
        $course5 = $this->getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $hierarchy_generator->create_comp_frame([]);
        $comp1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        linked_courses::set_linked_courses(
            $comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]
            ]
        );

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(2, count($rows));

        $event_sink = $this->redirectEvents();

        // Add already linked course - should have no effect
        linked_courses::add_linked_courses($comp1->id,
            [
                ['id' => $course1->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(2, count($rows));
        $this->assertSame(0, $event_sink->count());

        // Update linktype of some, add new ones
        linked_courses::add_linked_courses($comp1->id,
            [
                ['id' => $course2->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
                ['id' => $course3->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
                ['id' => $course4->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ]
        );

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(4, count($rows));

        foreach ($rows as $row) {
            if ($row->iteminstance == $course1->id || $row->iteminstance == $course3->id) {
                $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $row->linktype);
            } else {
                $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $row->linktype);
            }
        }

        $events = $event_sink->get_events();
        $this->assertSame(1, count($events));
        $event = reset($events);
        $this->assertTrue($event instanceof linked_courses_updated);
        $this->assertEquals($comp1->id, $event->objectid);
        $event_sink->clear();

        // Now only add new one
        linked_courses::add_linked_courses($comp1->id,
            [
                ['id' => $course5->id, 'linktype' => linked_courses::LINKTYPE_OPTIONAL],
            ]
        );

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(5, count($rows));

        foreach ($rows as $row) {
            if ($row->iteminstance == $course1->id || $row->iteminstance == $course3->id) {
                $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $row->linktype);
            } else {
                $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $row->linktype);
            }
        }

        $events = $event_sink->get_events();
        $this->assertSame(1, count($events));
        $event = reset($events);
        $this->assertTrue($event instanceof linked_courses_updated);
        $this->assertEquals($comp1->id, $event->objectid);
        $event_sink->clear();

        // Now only update one
        linked_courses::add_linked_courses($comp1->id,
            [
                ['id' => $course5->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY],
            ]
        );

        $rows = $DB->get_records('comp_criteria', ['competencyid' => $comp1->id, 'itemtype' => 'coursecompletion']);
        $this->assertSame(5, count($rows));

        foreach ($rows as $row) {
            if ($row->iteminstance == $course1->id || $row->iteminstance == $course3->id || $row->iteminstance == $course5->id) {
                $this->assertEquals(linked_courses::LINKTYPE_MANDATORY, $row->linktype);
            } else {
                $this->assertEquals(linked_courses::LINKTYPE_OPTIONAL, $row->linktype);
            }
        }

        $events = $event_sink->get_events();
        $this->assertSame(1, count($events));
        $event = reset($events);
        $this->assertTrue($event instanceof linked_courses_updated);
        $this->assertEquals($comp1->id, $event->objectid);

        $event_sink->close();
    }


    private function link_course_to_multiple_competencies(): array {
        $course = self::getDataGenerator()->create_course();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = self::getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $compfw1 = $hierarchy_generator->create_comp_frame([]);
        $competency1 = $hierarchy_generator->create_comp(['frameworkid' => $compfw1->id]);

        $compfw2 = $hierarchy_generator->create_comp_frame([]);
        $competency2 = $hierarchy_generator->create_comp(['frameworkid' => $compfw2->id]);

        linked_courses::set_linked_courses($competency1->id, [['id' => $course->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]]);
        linked_courses::set_linked_courses($competency2->id, [['id' => $course->id, 'linktype' => linked_courses::LINKTYPE_MANDATORY]]);

        return [$course->id, [$competency1, $competency2]];
    }

    private function get_linked_course_count(int $competency_id): int {
        global $DB;

        return $DB->count_records('comp_criteria', ['itemtype' => 'coursecompletion', 'competencyid' => $competency_id]);
    }

    private function get_competency_criteria_by_course(int $course_id): array {
        global $DB;

        return $DB->get_records('comp_criteria', ['itemtype' => 'coursecompletion', 'iteminstance' => $course_id]);
    }

}

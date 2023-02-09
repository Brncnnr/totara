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
 * @package mod_perform
 */

require_once(__DIR__ . '/section_element_reference_test.php');

use core\orm\collection;
use mod_perform\entity\activity\element;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_element;
use mod_perform\models\activity\activity;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\models\activity\section_element_reference;
use mod_perform\testing\generator as perform_generator;
use performelement_redisplay\redisplay;

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_reference_element_clone_testcase extends section_element_reference_testcase {

    public function test_clone_redisplay_referencing_different_activity(): void {
        self::setAdminUser();
        
        $perform_generator = perform_generator::instance();

        /*
         * activity1                    [SOURCE ACTIVITY]
         * ** section1                  [SOURCE SECTION]
         *    ** element1(short-text)   [SOURCE SECTION ELEMENT]
         *
         * activity2
         * ** section2
         *    ** element2(redisplay) --> element1
         */
        $activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'activity1']);
        $section1 = $perform_generator->create_section($activity1, ['title' => 'section1']);
        $element1 = $perform_generator->create_element();
        $section_element1 = $perform_generator->create_section_element($section1, $element1);

        $redisplay_data = [
            'activityId' => $activity1->id,
            redisplay::SOURCE_SECTION_ELEMENT_ID => $section_element1->id,
        ];

        $activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'activity2']);
        $section2 = $perform_generator->create_section($activity2, ['title' => 'section2']);
        $element2 = $perform_generator->create_element(['plugin_name' => 'redisplay', 'data' => json_encode($redisplay_data)]);
        $perform_generator->create_section_element($section2, $element2);

        $section_element_references = $this->get_references_by_source_activity_id($activity1->id);

        // Only one redisplay section_element_reference exists before cloning
        self::assertCount(1, $section_element_references);

        // Clone the activity.
        activity::load_by_id($activity2->id)->clone();

        $section_element_references = $this->get_references_by_source_activity_id($activity1->id);
        self::assertCount(2, $section_element_references);

        /** @var section_element_reference $section_element_reference */
        $section_element_reference = $section_element_references->first();

        /** @var section_element_reference $cloned_reference */
        $cloned_reference = $section_element_references->last();

        self::assertNotEquals($section_element_reference->id, $cloned_reference->id);
        self::assertEquals($section_element_reference->get_source_activity_id(), $cloned_reference->get_source_activity_id());
        self::assertEquals($section_element_reference->source_section_element_id, $cloned_reference->source_section_element_id);
        self::assertNotEquals($section_element_reference->referencing_element_id, $cloned_reference->referencing_element_id);
    }

    public function test_clone_aggregation_element(): void {
        $this->create_test_data();

        // Has a aggregation element in it's second section.
        $cloned_activity = $this->source_activity->clone();

        $section_element_references = $this->get_references_by_source_activity_id($cloned_activity->id);
        self::assertCount(1, $section_element_references);

        /** @var section_element_reference $cloned_reference */
        $cloned_reference = $section_element_references->first();

        self::assertNotEquals($cloned_reference->get_source_activity_id(), $this->source_activity->id);
        self::assertNotEquals($cloned_reference->source_section_element_id, $this->referencing_aggregation_element->id);

        /** @var element $cloned_aggregation_element */
        $cloned_aggregation_element = element::repository()->find($cloned_reference->referencing_element_id);

        self::assertEquals($this->referencing_aggregation_element->title, $cloned_aggregation_element->title);

        // Because the aggregations always reference their own activity, we aggregate on the newly created source section elements.
        self::assertNotEquals($this->aggregation_section_element_reference->id, $cloned_reference->id);
        self::assertNotEquals($this->aggregation_section_element_reference->get_source_activity_id(), $cloned_reference->get_source_activity_id());
        self::assertNotEquals($this->aggregation_section_element_reference->source_section_element_id, $cloned_reference->source_section_element_id);
        self::assertNotEquals($this->aggregation_section_element_reference->referencing_element_id, $cloned_reference->referencing_element_id);
    }

    /**
     * There is a special case when cloning a redisplay question that points to an element of the same activity:
     * The cloned redisplay question should then point to the cloned element of the cloned activity, not the source one.
     */
    public function test_clone_with_redisplay_pointing_to_same_activity(): void {
        self::setAdminUser();

        $perform_generator = perform_generator::instance();

        /*
         * activity1                    [SOURCE ACTIVITY]
         * ** section1                  [SOURCE SECTION]
         *    ** element1(short-text)   [SOURCE SECTION ELEMENT]
         * ** section2
         *    ** element2(redisplay) --> element1
         */
        $activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'activity1']);
        $section1 = $perform_generator->create_section($activity1, ['title' => 'section1']);
        $section2 = $perform_generator->create_section($activity1, ['title' => 'section2']);
        $element1 = $perform_generator->create_element();
        $section_element1 = $perform_generator->create_section_element($section1, $element1);

        $redisplay_data = [
            redisplay::SOURCE_SECTION_ELEMENT_ID => $section_element1->id,
            'someOtherKey' => 'some other value',
        ];
        $redisplay_element = $perform_generator->create_element(
            ['plugin_name' => 'redisplay', 'data' => json_encode($redisplay_data, JSON_THROW_ON_ERROR)]
        );

        $perform_generator->create_section_element($section2, $redisplay_element);

        $section_element_references = $this->get_references_by_source_activity_id($activity1->id);

        // Only one redisplay relationship exists before cloning
        self::assertCount(1, $section_element_references);

        // Clone the activity.
        $cloned_activity = activity::load_by_id($activity1->id)->clone();

        // Still only one redisplay relationship exists for activity1
        $section_element_references = $this->get_references_by_source_activity_id($activity1->id);
        self::assertCount(1, $section_element_references);

        // One redisplay relationship should exist for cloned_activity.
        $section_element_references = $this->get_references_by_source_activity_id($cloned_activity->id);
        self::assertCount(1, $section_element_references);

        /** @var section_element_reference $cloned_section_element_reference */
        $cloned_section_element_reference = $section_element_references->first();

        // Verify that the redisplay_relationship data is pointing to the cloned section1 element, not the original one.
        /** @var collection|section[] $cloned_sections */
        $cloned_sections = section::repository()
            ->where('activity_id', $cloned_activity->get_id())
            ->get();
        $cloned_section1 = $cloned_sections->filter('title', 'section1')->first();
        $cloned_section2 = $cloned_sections->filter('title', 'section2')->first();
        /** @var section_element $cloned_section1_element */
        $cloned_section1_element = section_element::repository()
            ->where('section_id', $cloned_section1->id)
            ->one(true);
        /** @var section_element $cloned_redisplay_section_element */
        $cloned_redisplay_section_element = section_element::repository()
            ->where('section_id', $cloned_section2->id)
            ->one(true);
        self::assertEquals($cloned_activity->get_id(), $cloned_section_element_reference->get_source_activity_id());
        self::assertEquals($cloned_section1_element->id, $cloned_section_element_reference->source_section_element_id);
        self::assertEquals($cloned_redisplay_section_element->element_id, $cloned_section_element_reference->referencing_element_id);
    }

    /**
     * Get redisplay relationships by source activity id
     *
     * @param $source_activity_id
     * @return collection|section_element_reference[]
     * @throws coding_exception
     */
    private function get_references_by_source_activity_id($source_activity_id): collection {
        return section_element_reference_entity::repository()
            ->as('ser')
            ->join([section_element::TABLE, 'source_section_element'], 'source_section_element.id', 'ser.source_section_element_id')
            ->join([section::TABLE, 'source_section'], 'source_section.id', 'source_section_element.section_id')
            ->where('source_section.activity_id', $source_activity_id)
            ->get()
            ->map_to(section_element_reference::class);
    }
}
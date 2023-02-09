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

use container_perform\perform as perform_container;
use core\orm\collection;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_element_reference;
use mod_perform\entity\activity\section_element_reference as section_element_reference_entity;
use mod_perform\testing\generator as perform_generator;
use performelement_redisplay\redisplay;

require_once(__DIR__ . '/section_element_reference_test.php');

/**
 * @group perform
 * @group perform_element
 */
class section_element_reference_model_testcase extends section_element_reference_testcase {

    public function test_create(): void {
        self::setAdminUser();

        /** @var perform_generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $source_activity = $perform_generator->create_activity_in_container(['activity_name' => 'activity']);
        $source_section = $perform_generator->create_section($source_activity, ['title' => 'section']);

        $element = $perform_generator->create_element();
        $source_section_element = $perform_generator->create_section_element($source_section, $element);

        $redisplay_element = new element_entity();
        $redisplay_element->context_id = perform_container::get_default_category_id();
        $redisplay_element->plugin_name = redisplay::get_plugin_name();
        $redisplay_element->title = 'Redisplay';
        $redisplay_element->data = json_encode([redisplay::SOURCE_SECTION_ELEMENT_ID => $source_section_element->id], JSON_THROW_ON_ERROR);
        $redisplay_element->is_required  = false;
        $redisplay_element->save();

        $section_element_reference = section_element_reference::create($source_section_element->id, $redisplay_element->id);

        self::assertEquals($source_section_element->id, $section_element_reference->source_section_element_id);
        self::assertEquals($redisplay_element->id, $section_element_reference->referencing_element_id);
    }

    public function test_update(): void {
        $this->create_test_data();

        /** @var perform_generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'activity']);
        $section = $perform_generator->create_section($activity, ['title' => 'section']);
        $element = $perform_generator->create_element();
        $section_element = $perform_generator->create_section_element($section, $element);

        section_element_reference::update($section_element->id, $this->referencing_redisplay_element->id);
        $section_element_reference = section_element_reference::load_by_id($this->redisplay_section_element_reference->id);

        self::assertEquals($section_element->id, $section_element_reference->source_section_element_id);
    }

    public function test_patch_multiple(): void {
        $this->create_test_data();

        /** @var perform_generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'activity']);
        $section = $perform_generator->create_section($activity, ['title' => 'section']);

        $section_elements = array_map(function (string $question_title) use ($perform_generator, $section) {
            $element = $perform_generator->create_element(['title' => $question_title]);
            return $perform_generator->create_section_element($section, $element);
        }, ['a', 'b', 'c']);

        $section_element_ids = array_map(function (section_element $section_element) {
            return $section_element->id;
        }, $section_elements);

        $new_section_element_references = section_element_reference::patch_multiple($section_element_ids, $this->referencing_redisplay_element->id);
        /** @var section_element_reference_entity[] $new_section_element_references_from_db */
        $new_section_element_references_from_db = section_element_reference_entity::repository()
            ->where('referencing_element_id', $this->referencing_redisplay_element->id)
            ->order_by('id')
            ->get()
            ->all(false);

        self::assertCount(3, $new_section_element_references);
        self::assertCount(3, $new_section_element_references_from_db);

        /** @var section_element_reference $new_section_element_reference */
        foreach ($new_section_element_references as $i => $new_section_element_reference) {
            self::assertEquals($section_elements[$i]->id, $new_section_element_reference->source_section_element_id);
            self::assertEquals($section_elements[$i]->id, $new_section_element_references_from_db[$i]->source_section_element_id);

            self::assertEquals($this->referencing_redisplay_element->id, $new_section_element_reference->referencing_element_id);
            self::assertEquals($this->referencing_redisplay_element->id, $new_section_element_references_from_db[$i]->referencing_element_id);

            self::assertNotNull(section_element_reference::load_by_id($new_section_element_reference->id));
        }


        // Completely replace the section_element_references with another batch.
        $second_batch_of_section_elements = array_map(function (string $question_title) use ($perform_generator, $section) {
            $element = $perform_generator->create_element(['title' => $question_title]);
            return $perform_generator->create_section_element($section, $element);
        }, ['d', 'e']);

        $second_batch_section_element_ids = array_map(function (section_element $section_element) {
            return $section_element->id;
        }, $second_batch_of_section_elements);

        $second_batch_section_element_references = section_element_reference::patch_multiple($second_batch_section_element_ids, $this->referencing_redisplay_element->id);
        /** @var section_element_reference_entity[] $new_section_element_references_from_db */
        $second_batch_section_element_references_from_db = section_element_reference_entity::repository()
            ->where('referencing_element_id', $this->referencing_redisplay_element->id)
            ->order_by('id')
            ->get()
            ->all(false);

        self::assertCount(2, $second_batch_section_element_references);
        self::assertCount(2, $second_batch_section_element_references_from_db);

        /** @var section_element_reference $second_batch_section_element_reference */
        foreach ($second_batch_section_element_references as $i => $second_batch_section_element_reference) {
            self::assertEquals($second_batch_of_section_elements[$i]->id, $second_batch_section_element_reference->source_section_element_id);
            self::assertEquals($second_batch_of_section_elements[$i]->id, $second_batch_section_element_references_from_db[$i]->source_section_element_id);

            self::assertEquals($this->referencing_redisplay_element->id, $second_batch_section_element_reference->referencing_element_id);
            self::assertEquals($this->referencing_redisplay_element->id, $second_batch_section_element_references_from_db[$i]->referencing_element_id);

            self::assertNotNull(section_element_reference::load_by_id($second_batch_section_element_reference->id));
        }
    }

    public function test_cant_update_when_entity_doesnt_exist(): void {
        $this->create_test_data();

        /** @var perform_generator $perform_generator */
        $perform_generator = self::getDataGenerator()->get_plugin_generator('mod_perform');

        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'activity']);
        $section = $perform_generator->create_section($activity, ['title' => 'section']);
        $element = $perform_generator->create_element();
        $section_element = $perform_generator->create_section_element($section, $element);

        section_element_reference_entity::repository()->find($this->redisplay_section_element_reference->id)->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can not update a section element reference that does not exist');

        section_element_reference::update($section_element->id, $this->referencing_redisplay_element->id);
    }

    public function test_get_sections_by_source_activity_id(): void {
        $this->create_test_data();

        $section_elements = section_element_reference::get_section_elements_that_reference_activity($this->source_activity->id);

        $this->assert_activity_sections_that_reference($section_elements);
    }

    public function test_get_sections_by_source_section_id(): void {
        $this->create_test_data();

        $section_elements = section_element_reference::get_section_elements_that_reference_section($this->source_section->id);

        $this->assert_activity_sections_that_reference($section_elements);
    }

    public function test_get_sections_by_source_section_element_id(): void {
        $this->create_test_data();

        $section_elements = section_element_reference::get_referenced_section_elements_by_source_section_element($this->source_section_element->id);

        $this->assert_activity_sections_that_reference($section_elements);
    }

    /**
     * Assert returns activity sections with correct order
     *
     * @param collection|section_element[] $section_elements
     */
    private function assert_activity_sections_that_reference(collection $section_elements): void {
        $section_titles = $section_elements->map(function (section_element $section_element) {
            return $section_element->section->title;
        });
        self::assertNotContains($this->source_section->title, $section_titles);

        $section_elements = $section_elements->all(false);
        self::assertCount(2, $section_elements);

        self::assertEquals($this->referencing_redisplay_activity->name, $section_elements[0]->section->activity->name);
        self::assertEquals($this->referencing_redisplay_section->title, $section_elements[0]->section->get_display_title());

        // Aggregation elements source and referencing elements must be from the same activity.
        self::assertEquals($this->source_activity->name, $section_elements[1]->section->activity->name);
        self::assertEquals($this->referencing_aggregation_section->title, $section_elements[1]->section->get_display_title());
    }
}
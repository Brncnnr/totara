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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element;
use performelement_competency_rating\element_usage;
use totara_core\advanced_feature;

/**
 * @group perform
 * @group perform_element
 */
class performelement_competency_rating_testcase extends testcase {

    private const COMPETENCY_RATING_PLUGIN = 'competency_rating';
    private const LINKED_REVIEW_PLUGIN = 'linked_review';

    public function test_create_as_child_element() {
        $parent_element = new element_entity([
            'context_id' => context_system::instance()->id,
            'plugin_name' => self::LINKED_REVIEW_PLUGIN,
            'title' => 'Parent element',
            'data' => json_encode(['content_type' => 'totara_evidence']),
        ]);
        $parent_element->save();
        $linked_review_model = element::load_by_entity($parent_element);

        try {
            $linked_review_data = json_decode($linked_review_model->data, true);
        } catch (Exception $exception) {
            $this->markTestSkipped('evidence type not available yet.');
        }
        $this->assertNotEmpty($linked_review_data['compatible_child_element_plugins']);
        $this->assertNotContains(self::COMPETENCY_RATING_PLUGIN, $linked_review_data['compatible_child_element_plugins']);

        try {
            $linked_review_model->get_child_element_manager()->create_child_element(
                [
                    'title' => 'Incompatible plugin',
                ],
                self::COMPETENCY_RATING_PLUGIN
            );
            $this->fail('Expected exception was not thrown');
        } catch (coding_exception $e) {
            $this->assertStringContainsString(
                self::COMPETENCY_RATING_PLUGIN . " element is not compatible with " . self::LINKED_REVIEW_PLUGIN,
                $e->getMessage()
            );
        }
        $this->assertEmpty($linked_review_model->children);
        $linked_review_model->update_details('Parent element', json_encode(['content_type' => 'totara_competency']));

        $linked_review_model->get_child_element_manager()->create_child_element(
            [
                'title' => 'Incompatible plugin',
            ],
            self::COMPETENCY_RATING_PLUGIN
        );
        $this->assertCount(1, $linked_review_model->children);

        /** @var element $child_element*/
        $child_element = $linked_review_model->children->first();
        $this->assertEquals(self::COMPETENCY_RATING_PLUGIN, $child_element->plugin_name);
    }

    public function test_create_as_top_level_element() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(self::COMPETENCY_RATING_PLUGIN . " can not be used as a top level element.");
        element::create(
            context_system::instance(),
            self::COMPETENCY_RATING_PLUGIN,
            'Top level question'
        );
    }

    public function test_competency_rating_plugin_is_compatible_child_element() {
        $competency_rating_element_usage = new element_usage();
        $compatible_with_evidence = $competency_rating_element_usage->is_compatible_child_element(
            self::LINKED_REVIEW_PLUGIN,
            json_encode(['content_type' => 'totara_evidence'])
        );
        $this->assertFalse($compatible_with_evidence, self::COMPETENCY_RATING_PLUGIN . " is not compatible with evidence content type");

        $compatible_with_competency = $competency_rating_element_usage->is_compatible_child_element(
            self::LINKED_REVIEW_PLUGIN,
            json_encode(['content_type' => 'totara_competency'])
        );
        $this->assertTrue($compatible_with_competency);

        // Test compatibility when competency features are disabled.
        advanced_feature::disable('competencies');
        advanced_feature::disable('competency_assignment');
        $compatible_with_competency = $competency_rating_element_usage->is_compatible_child_element(
            self::LINKED_REVIEW_PLUGIN,
            json_encode(['content_type' => 'totara_competency'])
        );

        $this->assertFalse($compatible_with_competency);
    }
}
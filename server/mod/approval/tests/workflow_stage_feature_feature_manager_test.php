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
use mod_approval\model\workflow\stage_feature\approval_levels;
use mod_approval\model\workflow\stage_feature\feature_manager;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_feature\interactions;
use mod_approval\model\workflow\workflow_stage;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_feature\feature_manager
 */
class mod_approval_workflow_stage_feature_feature_manager_testcase extends testcase {

    public function test_constructing_feature_manager_with_invalid_feature() {
        $mock_stage = $this->createStub(workflow_stage::class);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid feature class not_found");

        new feature_manager(['not_found'], $mock_stage);
    }

    public function test_get() {
        $mock_stage = $this->createStub(workflow_stage::class);
        $features_manager = new feature_manager([formviews::class], $mock_stage);
        $this->assertInstanceOf(formviews::class, $features_manager->formviews);

        $features_manager = new feature_manager([approval_levels::class], $mock_stage);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Feature with formviews enum not available in manager');
        $features_manager->formviews;
    }

    public function test_has() {
        $mock_stage = $this->createStub(workflow_stage::class);
        $features_manager = new feature_manager([formviews::class], $mock_stage);
        $this->assertTrue($features_manager->has(formviews::get_enum()));
        $this->assertFalse($features_manager->has(approval_levels::get_enum()));
    }

    /**
     * @dataProvider possible_configurations
     * @param array $feature_classes
     * @param array $expected_result
     */
    public function test_get_all(array $feature_classes, array $expected_result) {
        $mock_stage = $this->createStub(workflow_stage::class);
        $features_manager = new feature_manager($feature_classes, $mock_stage);
        $features = $features_manager->all();
        $this->assertEquals(
            $expected_result,
            $features
        );
    }

    public function possible_configurations(): array {
        $mock_stage = $this->createMock(workflow_stage::class);
        return [
            'no features' => [
                [],
                [],
            ],
            'only form views' => [
                [formviews::class],
                [
                    new formviews($mock_stage),
                ]
            ],
            'only approval levels' => [
                [approval_levels::class],
                [
                    new approval_levels($mock_stage),
                ]
            ],
            'only interactions' => [
                [interactions::class],
                [
                    new interactions($mock_stage),
                ]
            ],
            'form views and approval levels, formviews first' => [
                [formviews::class, approval_levels::class],
                [
                    new formviews($mock_stage),
                    new approval_levels($mock_stage),
                ]
            ],
            'formviews and approval levels, approval levels first' => [
                [approval_levels::class, formviews::class],
                [
                    new formviews($mock_stage),
                    new approval_levels($mock_stage),
                ]
            ]
        ];
    }
}
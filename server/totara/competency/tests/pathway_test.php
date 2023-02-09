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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use totara_competency\pathway;
use totara_competency\pathway_factory;
use totara_hierarchy\entity\scale;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_pathway_testcase extends testcase {

    /**
     * Test whether each pathway returns a label
     */
    public function test_labels() {
        $types = pathway_factory::get_pathway_types();
        foreach ($types as $type) {
            $pathway = pathway_factory::create($type);
            $label = $pathway::get_label();
            $this->assertNotEmpty($label);
        }
        $this->assertDebuggingNotCalled('This pathway does not have a label. Every pathway needs a label so that it shows up correctly in the interface.');
    }

    /**
     * Test leads_to_proficiency with a MULTI_VALUE type pathway
     */
    public function test_leads_to_proficiency_multi_value() {
        /** @var pathway $pathway_mock */
        $pathway_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['get_classification', 'get_scale_value', 'is_valid', 'is_active'])
                         ->getMockForAbstractClass();

        $pathway_mock->method('get_classification')->willReturn(pathway::PATHWAY_MULTI_VALUE);
        $pathway_mock->method('get_scale_value')->willReturn(null);
        $pathway_mock->method('is_active')->willReturn(true);
        $pathway_mock->method('is_valid')->willReturn(true);
        $this->assertTrue($pathway_mock->leads_to_proficiency());
    }

    /**
     * Test leads_to_proficiency with SINGLE_VALUE type pathways
     */
    public function test_leads_to_proficiency_single_value() {
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        /** @var scale $scale */
        $scale = new scale($scale->id);
        /** @var scale_value $prof_scale_value */
        $prof_scale_value = $scale->min_proficient_value;
        /** @var scale_value $non_prof_scale_value */
        $non_prof_scale_value = $scale->default_value;

        // Non proficient
        /** @var pathway $pathway1_mock */
        $pathway1_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['get_classification', 'get_scale_value', 'is_valid', 'is_active'])
                         ->getMockForAbstractClass();
        $pathway1_mock->method('get_classification')->willReturn(pathway::PATHWAY_SINGLE_VALUE);
        $pathway1_mock->method('get_scale_value')->willReturn($non_prof_scale_value);
        $pathway1_mock->method('is_active')->willReturn(true);
        $pathway1_mock->method('is_valid')->willReturn(true);

        $this->assertFalse($pathway1_mock->leads_to_proficiency());

        // Proficient
        /** @var pathway $pathway2_mock */
        $pathway2_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['get_classification', 'get_scale_value', 'is_valid', 'is_active'])
                         ->getMockForAbstractClass();
        $pathway2_mock->method('get_classification')->willReturn(pathway::PATHWAY_SINGLE_VALUE);
        $pathway2_mock->method('get_scale_value')->willReturn($prof_scale_value);
        $pathway2_mock->method('is_active')->willReturn(true);
        $pathway2_mock->method('is_valid')->willReturn(true);

        $this->assertTrue($pathway2_mock->leads_to_proficiency());

        // Null
        /** @var pathway $pathway3_mock */
        $pathway3_mock = $this->getMockBuilder(pathway::class)
                         ->setMethods(['get_classification', 'get_scale_value', 'is_valid', 'is_active'])
                         ->getMockForAbstractClass();
        $pathway3_mock->method('get_classification')->willReturn(pathway::PATHWAY_SINGLE_VALUE);
        $pathway3_mock->method('get_scale_value')->willReturn(null);
        $pathway3_mock->method('is_active')->willReturn(true);
        $pathway3_mock->method('is_valid')->willReturn(true);

        $this->assertFalse($pathway3_mock->leads_to_proficiency());
        $this->assertDebuggingCalled('A single value pathway without a scale value exists in the single_value pathway.');
    }

}

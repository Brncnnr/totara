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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_perform_rating
 */

use pathway_perform_rating\entity\perform_rating;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once __DIR__.'/perform_rating_base_testcase.php';

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_webapi_resolver_mutation_linked_competencies_rate_testcase extends perform_rating_base_testcase {

    use webapi_phpunit_helper;

    private const MUTATION = 'pathway_perform_rating_linked_competencies_rate';

    private $data;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        parent::setUp();

        if (!core_component::get_plugin_directory('performelement', 'linked_review')) {
            $this->markTestSkipped('Required linked review plugin is not present');
        }

        $this->data = $this->create_data();
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
    }

    private function resolve_mutation(array $args = []): array {
        $args = array_merge([
            'competency_id' => $this->data->competency->id,
            'participant_instance_id' => $this->data->participant_instance1->id,
            'section_element_id' => $this->data->section_element->id,
            'scale_value_id' => null,
        ], $args);

        return $this->resolve_graphql_mutation(self::MUTATION, ['input' => $args]);
    }

    public function test_resolve_mutation_successful(): void {
        self::setUser($this->data->manager_user);
        $this->assertEquals(0, perform_rating::repository()->count());

        $result1 = $this->resolve_mutation();
        $this->assertArrayHasKey('rating', $result1);
        $this->assertArrayNotHasKey('already_exists', $result1);
        $this->assertEquals(1, perform_rating::repository()->count());
        $this->assertEquals(perform_rating::repository()->one()->id, $result1['rating']->id);

        $result2 = $this->resolve_mutation();
        $this->assertArrayHasKey('rating', $result2);
        $this->assertTrue($result2['already_exists']);
        $this->assertEquals(1, perform_rating::repository()->count());
        $this->assertEquals($result1['rating']->id, $result2['rating']->id);
    }

    public function test_user_not_logged_in(): void {
        self::setUser(null);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('You are not logged in');

        $this->resolve_mutation();
    }

    public function test_competency_assignment_feature_disabled(): void {
        self::setUser($this->data->manager_user);
        advanced_feature::disable('competency_assignment');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature competency_assignment is not available.');

        $this->resolve_mutation();
    }

    public function test_performance_activities_feature_disabled(): void {
        self::setUser($this->data->manager_user);
        advanced_feature::disable('performance_activities');

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Feature performance_activities is not available.');

        $this->resolve_mutation();
    }

}

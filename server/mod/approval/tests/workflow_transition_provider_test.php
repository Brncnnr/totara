<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 */

use mod_approval\exception\model_exception;
use mod_approval\model\workflow\interaction\transition\next;
use mod_approval\model\workflow\interaction\transition\stage;
use mod_approval\model\workflow\interaction\transition\provider;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\interaction\transition\provider
 */
class mod_approval_workflow_transition_provider_testcase extends mod_approval_testcase {

    public function test_get_class_by_enum() {
        $this->assertEquals(next::class, provider::get_class_by_enum('NEXT'));
        $this->assertEquals(stage::class, provider::get_class_by_enum('STAGE'));

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Unknown transition code');
        provider::get_class_by_enum('TOTARA');
    }

    public function test_get_transition_by_field() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);

        $transition = provider::get_transition_by_field('NEXT', $stage1);
        $this->assertInstanceOf(next::class, $transition);

        $transition = provider::get_transition_by_field($stage2->id, $stage1);
        $this->assertInstanceOf(stage::class, $transition);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Unknown transition code');
        provider::get_transition_by_field('TOTARA', $stage1);
    }

    public function test_get_resolvers_for_stage() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $stage3 = $workflow->latest_version->stages->last();

        $resolvers = provider::get_resolver_options_for_stage($stage1);
        $this->assertCount(4, $resolvers);

        $resolvers_max = provider::get_resolver_options_for_stage($stage2);
        $this->assertCount(5, $resolvers_max);

        $resolvers = provider::get_resolver_options_for_stage($stage3);
        $this->assertCount(3, $resolvers);

        $this->assertEquals("PREVIOUS", $resolvers_max[0]->value);
        $this->assertEquals("NEXT", $resolvers_max[1]->value);
        $this->assertEquals("RESET", $resolvers_max[2]->value);
        $this->assertEquals((string) $stage1->id, $resolvers_max[3]->value);
        $this->assertEquals((string) $stage3->id, $resolvers_max[4]->value);
    }
}
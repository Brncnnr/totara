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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\tenant;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\helper\access_checks;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\generator as approval_generator;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\helper\access_checks
 */
class mod_approval_assignment_helper_access_checks_testcase extends testcase {
    /**
     * @covers ::check
     */
    public function test_check_on_singletenant(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $assignment = $this->create_assignment_for_user();
        $helper = access_checks::from_assignment($assignment);
        // succeeds
        $this->setAdminUser();
        $helper->check();
        // succeeds
        $this->setUser($user);
        $helper->check();
        // should fail?
        $this->setGuestUser();
        $helper->check();
        // should fail?
        $this->setUser();
        $helper->check();

        $this->update_course_visibility($assignment->course_id, 2, false);
        // fails
        $this->setAdminUser();
        try {
            $helper->check();
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this workflow (Parent workflow is hidden)', $ex->getMessage());
        }
    }

    /**
     * @covers ::check
     */
    public function test_check_on_multitenant(): void {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tengen = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tengen->enable_tenants();
        $ten1 = new tenant($tengen->create_tenant());
        $ten2 = new tenant($tengen->create_tenant());
        $user0a = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0b = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user1a = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $user1b = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $user2a = $this->getDataGenerator()->create_user(['tenantid' => $ten2->id]);
        $this->setUser($user0a);
        $assignment0 = $this->create_assignment_for_user();
        $helper0 = access_checks::from_assignment($assignment0);
        $this->setUser($user1a);
        $assignment1 = $this->create_assignment_for_user();
        $helper1 = access_checks::from_assignment($assignment1);

        // rule #1: non-tenant user can access tenant user's assignment
        // rule #2: tenant user can access non-tenant user's assignment
        // rule #3: guest user/logged out user can access non-tenant user's assignment?

        // succeeds
        $this->setUser($user0a);
        $helper0->check();
        // succeeds
        $this->setUser($user0b);
        $helper0->check();
        // succeeds
        $this->setUser($user1a);
        $helper0->check();
        // should fail?
        $this->setGuestUser();
        $helper0->check();
        // should fail?
        $this->setUser();
        $helper0->check();
        // succeeds
        $this->setUser($user0a);
        $helper1->check();
        // succeeds
        $this->setUser($user1a);
        $helper1->check();
        // succeeds
        $this->setUser($user1b);
        $helper1->check();
        // fails
        $this->setUser($user2a);
        try {
            $helper1->check();
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this workflow assignment (Cannot access assignment)', $ex->getMessage());
        }
        // fails
        $this->setGuestUser();
        try {
            $helper1->check();
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this workflow assignment (Cannot access assignment)', $ex->getMessage());
        }
        // fails
        $this->setUser();
        try {
            $helper1->check();
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this workflow assignment (Cannot access assignment)', $ex->getMessage());
        }
    }

    /**
     * @param integer $course_id
     * @param boolean $visibility
     */
    private function update_course_visibility(int $course_id, int $user_id, bool $visibility): void {
        // HACK: update only the cache entry totara_course_is_viewable looks for.
        $cache = cache::make('totara_core', 'totara_course_is_viewable', ['userid' => $user_id]);
        $cache->set($course_id, $visibility ? 1 : 0);
    }

    /**
     * @return assignment
     */
    private function create_assignment_for_user(): assignment {
        // Make unique workflow type name
        $generator = approval_generator::instance();
        $workflow_type_entity = $generator->create_workflow_type('test workflow type');
        $workflow_type = workflow_type::load_by_entity($workflow_type_entity);
        $cohort_id = $this->getDataGenerator()->create_cohort()->id;
        $form = form::create('simple', 'kia kaha');
        $workflow = workflow::create(
            $workflow_type,
            $form,
            'Test Workflow',
            '',
            assignment_type\cohort::get_code(),
            $cohort_id
        );
        $assignment = $workflow->get_default_assignment();
        $assignment->activate();
        return $assignment;
    }
}

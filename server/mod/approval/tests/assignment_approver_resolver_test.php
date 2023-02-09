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

use core\entity\user;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_approver_resolver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use totara_core\relationship\relationship;
use totara_job\job_assignment;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\assignment\assignment_approver_resolver
 */
class mod_approval_assignment_approver_resolver_testcase extends testcase {
    /** @var workflow_stage_approval_level */
    private $level1;

    /** @var workflow_stage_approval_level */
    private $level2;

    /** @var assignment */
    private $assignment;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $form = form::create('simple', 'form');
        $workflow = workflow::create(
            workflow_type::create('type'),
            $form,
            'workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            '1'
        );
        $stage = workflow_stage::create(workflow_version::create($workflow, form_version::create($form, '1', '{}')), 'stage', approvals::get_enum());

        $this->level1 = $stage->add_approval_level('level1');
        $this->level2 = $stage->add_approval_level('level2');
        $this->assignment = $workflow->get_default_assignment();
        $this->assignment->activate();
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->level1 = $this->level2 = $this->assignment = null;
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_unknown(): void {
        $user = $this->getDataGenerator()->create_user();
        $approver = assignment_approver::create($this->assignment, $this->level1, user_approver_type::TYPE_IDENTIFIER, $user->id);
        builder::table('approval_approver')->where('id', $approver->id)->update(['type' => 42]);
        $approver->refresh();
        $resolver = assignment_approver_resolver::from_user($user->id);
        try {
            $resolver->resolve([$approver]);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Unknown assignment_approver type code', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_user
     */
    public function test_resolve_user(): void {
        $user = $this->getDataGenerator()->create_user();
        $boss = $this->getDataGenerator()->create_user();
        $ja = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss->id)->id]);
        $approver1 = assignment_approver::create($this->assignment, $this->level1, user_approver_type::TYPE_IDENTIFIER, $user->id);
        $approver2 = assignment_approver::create($this->assignment, $this->level2, user_approver_type::TYPE_IDENTIFIER, $user->id);

        $result = assignment_approver_resolver::from_user($user->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([$user->id], $result);

        $result = assignment_approver_resolver::from_user($boss->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([$user->id], $result);

        $result = assignment_approver_resolver::from_user($user->id, $ja->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([$user->id], $result);

        builder::table(user::TABLE)->where('id', $user->id)->delete();
        $result = assignment_approver_resolver::from_user($boss->id)->resolve([$approver1])->keys();
        $this->assertEquals([], $result);
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_relationship
     */
    public function test_resolve_relationship_user_manager_only(): void {
        $user = $this->getDataGenerator()->create_user();
        $boss1 = $this->getDataGenerator()->create_user();
        $boss2 = $this->getDataGenerator()->create_user();
        $manja1 = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss1->id)->id]);
        $manja2 = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss2->id)->id]);
        $approver1 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('manager')->id);

        $resolver = assignment_approver_resolver::from_user($user->id);
        $result = $resolver->resolve([$approver1])->keys();
        $this->assertEqualsCanonicalizing([$boss1->id, $boss2->id], $result);

        $result = assignment_approver_resolver::from_user($boss1->id)->resolve([$approver1])->keys();
        $this->assertEquals([], $result);

        // job assignments are not cascade-deleted?
        builder::table('job_assignment')->where('userid', $user->id)->delete();
        builder::table(user::TABLE)->where('id', $user->id)->delete();
        $result = $resolver->resolve([$approver1])->keys();
        $this->assertEquals([], $result);
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_relationship
     */
    public function test_resolve_relationship_user_multiple_relationships(): void {
        // TODO TL-31105 - Requires support for other relatiohships
        $this->markTestSkipped('Test uses unimplemented relationships');
        $user = $this->getDataGenerator()->create_user();
        $boss1 = $this->getDataGenerator()->create_user();
        $boss2 = $this->getDataGenerator()->create_user();
        $manja1 = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss1->id)->id]);
        $manja2 = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss2->id)->id]);
        $arja = job_assignment::create_default($user->id, ['appraiserid' => $boss1->id]);
        $approver1 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('manager')->id);
        $approver2 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('appraiser')->id);

        $resolver = assignment_approver_resolver::from_user($user->id);
        $result = $resolver->resolve([$approver1, $approver2])->keys();
        $this->assertEqualsCanonicalizing([$boss1->id, $boss2->id], $result);

        $result = $resolver->resolve([$approver2])->keys();
        $this->assertEquals([$boss1->id], $result);

        $result = assignment_approver_resolver::from_user($boss1->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([], $result);

        // job assignments are not cascade-deleted?
        builder::table('job_assignment')->where('userid', $user->id)->delete();
        builder::table(user::TABLE)->where('id', $user->id)->delete();
        $result = $resolver->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([], $result);
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_relationship
     */
    public function test_resolve_relationship_job_assignment_manager_only(): void {
        $user = $this->getDataGenerator()->create_user();
        $boss1 = $this->getDataGenerator()->create_user();
        $boss2 = $this->getDataGenerator()->create_user();
        $tempboss = $this->getDataGenerator()->create_user();
        $manarja = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss1->id)->id]);
        $manja = job_assignment::create_default($user->id, [
            'managerjaid' => job_assignment::create_default($boss2->id)->id,
            'tempmanagerjaid' => job_assignment::create_default($tempboss->id)->id,
            'tempmanagerexpirydate' => time() + DAYSECS
        ]);
        $approver1 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('manager')->id);

        $resolver = assignment_approver_resolver::from_user($user->id, $manarja->id);
        $result = $resolver->resolve([$approver1])->keys();
        $this->assertEqualsCanonicalizing([$boss1->id], $result);

        $result = assignment_approver_resolver::from_user($user->id, $manja->id)->resolve([$approver1])->keys();
        $this->assertEquals([$boss2->id, $tempboss->id], $result);

        // Note: passing an invalid user id; $manja is meant for $user
        $result = assignment_approver_resolver::from_user($boss1->id, $manja->id)->resolve([$approver1])->keys();
        $this->assertEquals([$boss2->id, $tempboss->id], $result);

        builder::table('job_assignment')->where('id', $manarja->id)->delete();
        $result = $resolver->resolve([$approver1])->keys();
        $this->assertEquals([], $result);
    }

    /**
     * @covers ::resolve
     * @covers ::resolve_relationship
     */
    public function test_resolve_relationship_job_assignment_multiple_relationships(): void {
        // TODO TL-31105 - Requires support for other relatiohships
        $this->markTestSkipped('Test uses unimplemented relationships');
        $user = $this->getDataGenerator()->create_user();
        $boss1 = $this->getDataGenerator()->create_user();
        $boss2 = $this->getDataGenerator()->create_user();
        $boss3 = $this->getDataGenerator()->create_user();
        $manarja = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss1->id)->id, 'appraiserid' => $boss3->id]);
        $manja = job_assignment::create_default($user->id, ['managerjaid' => job_assignment::create_default($boss2->id)->id]);
        $arja = job_assignment::create_default($user->id, ['appraiserid' => $boss1->id]);
        $approver1 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('manager')->id);
        $approver2 = assignment_approver::create($this->assignment, $this->level1, relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('appraiser')->id);

        $resolver = assignment_approver_resolver::from_user($user->id, $manarja->id);
        $result = $resolver->resolve([$approver1, $approver2])->keys();
        $this->assertEqualsCanonicalizing([$boss1->id, $boss3->id], $result);

        $result = assignment_approver_resolver::from_user($user->id, $manja->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([$boss2->id], $result);

        // Note: passing an invalid user id; $arja is meant for $user
        $result = assignment_approver_resolver::from_user($boss3->id, $arja->id)->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([$boss1->id], $result);

        builder::table('job_assignment')->where('id', $manarja->id)->delete();
        $result = $resolver->resolve([$approver1, $approver2])->keys();
        $this->assertEquals([], $result);
    }
}

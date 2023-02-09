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

use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\totara_comment\comment_resolver;
use core\entity\user;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\assignment_approver;
use totara_comment\comment;
use totara_comment\entity\comment as comment_entity;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\totara_comment\comment_resolver
 */
class mod_approval_comment_resolver_testcase extends mod_approval_testcase {
    /** @var application */
    private $application;

    /** @var comment_resolver */
    private $resolver;

    /** @var user */
    private $user;

    /** @var user */
    private $approver;

    /** @var user */
    private $stranger;

    /** @var user */
    private $admin;

    public function setUp(): void {
        $this->resolver = new comment_resolver();
        $this->user = new user($this->getDataGenerator()->create_user());
        $this->approver = new user($this->getDataGenerator()->create_user());
        $this->stranger = new user($this->getDataGenerator()->create_user());
        $this->admin = new user(2);
        $this->setUser($this->user);
        $this->application = $this->create_application_for_user();
        $stage1 = $this->application->workflow_version->stages->first();
        $stage2 = $this->application->workflow_version->get_next_stage($stage1->id);
        assignment_approver::create(
            $this->application->assignment,
            $stage2->approval_levels->first(),
            user_approver_type::TYPE_IDENTIFIER,
            $this->approver->id
        );
        $this->setUser();
        parent::setUp();
    }

    public function tearDown(): void {
        $this->resolver = $this->user = $this->approver = $this->stranger = $this->admin = $this->application = null;
        parent::tearDown();
    }

    /**
     * @param integer $instance_id
     * @param string $area
     * @param integer $actor_id
     * @return array of [comment, actor_id]
     */
    private function fake_comment(int $instance_id, string $area, int $actor_id): array {
        return $this->fake_comment_by($this->user->id, $instance_id, $area, $actor_id);
    }

    /**
     * @param integer $commentator_id
     * @param integer $instance_id
     * @param string $area
     * @param integer $actor_id
     * @return array of [comment, actor_id]
     */
    private function fake_comment_by(int $commentator_id, int $instance_id, string $area, int $actor_id): array {
        $fields = [
            'id' => 42,
            'component' => 'mod_approval',
            'format' => 1,
            'instanceid' => $instance_id,
            'content' => 'baa',
            'timecreated' => 111,
            'timemodified' => 111,
            'parentid' => null,
            'timedeleted' => null,
            'reasondeleted' => null,
            'contenttext' => null
        ];
        return [
            comment::from_entity(new comment_entity(array_merge($fields, ['area' => $area, 'userid' => $commentator_id]))),
            $actor_id
        ];
    }

    public function test_on_draft(): void {
        $good_user = [$this->application->id, 'comment', $this->user->id];
        $good_approver = [$this->application->id, 'comment', $this->approver->id];
        $good_stranger = [$this->application->id, 'comment', $this->stranger->id];
        $good_admin = [$this->application->id, 'comment', $this->admin->id];
        $bad_user = [$this->application->id, 'komment', $this->user->id];
        $this->assertFalse($this->resolver->can_see_comments(...$good_user));
        $this->assertFalse($this->resolver->can_see_comments(...$good_approver));
        $this->assertFalse($this->resolver->can_see_comments(...$good_stranger));
        $this->assertFalse($this->resolver->can_see_comments(...$good_admin));
        $this->assertFalse($this->resolver->can_see_comments(...$bad_user));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_user));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_approver));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_stranger));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_admin));
        $this->assertFalse($this->resolver->is_allow_to_create(...$bad_user));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_stranger)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$bad_user)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_stranger)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$bad_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_stranger)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$bad_user)));
    }

    public function test_on_submitted(): void {
        $next_state = $this->application->current_stage->state_manager->get_next_state($this->application->current_state);
        $this->application->set_current_state($next_state);
        $good_user = [$this->application->id, 'comment', $this->user->id];
        $good_approver = [$this->application->id, 'comment', $this->approver->id];
        $good_stranger = [$this->application->id, 'comment', $this->stranger->id];
        $good_admin = [$this->application->id, 'comment', $this->admin->id];
        $bad_user = [$this->application->id, 'komment', $this->user->id];
        $this->assertTrue($this->resolver->can_see_comments(...$good_user));
        $this->assertTrue($this->resolver->can_see_comments(...$good_approver));
        $this->assertFalse($this->resolver->can_see_comments(...$good_stranger));
        $this->assertTrue($this->resolver->can_see_comments(...$good_admin));
        $this->assertFalse($this->resolver->can_see_comments(...$bad_user));
        $this->assertTrue($this->resolver->is_allow_to_create(...$good_user));
        $this->assertTrue($this->resolver->is_allow_to_create(...$good_approver));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_stranger));
        $this->assertTrue($this->resolver->is_allow_to_create(...$good_admin));
        $this->assertFalse($this->resolver->is_allow_to_create(...$bad_user));
        $this->assertTrue($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_stranger)));
        $this->assertTrue($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$bad_user)));
        $this->assertTrue($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_stranger)));
        $this->assertTrue($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$bad_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_stranger)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$bad_user)));

        $this->assertTrue($this->resolver->is_allow_to_delete(
            ...$this->fake_comment_by($this->approver->id, ...$good_approver)
        ));
        $this->assertTrue($this->resolver->is_allow_to_update(
            ...$this->fake_comment_by($this->approver->id, ...$good_approver)
        ));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(
            ...$this->fake_comment_by($this->approver->id, ...$good_user)
        ));
    }

    public function test_on_completed(): void {
        $approval_stage = $this->application->get_next_stage();
        $final_stage = $this->application->get_workflow_version()->get_next_stage($approval_stage->id);
        $this->application->set_current_state(new application_state($final_stage->id));
        $good_user = [$this->application->id, 'comment', $this->user->id];
        $good_approver = [$this->application->id, 'comment', $this->approver->id];
        $good_stranger = [$this->application->id, 'comment', $this->stranger->id];
        $good_admin = [$this->application->id, 'comment', $this->admin->id];
        $bad_user = [$this->application->id, 'komment', $this->user->id];
        $this->assertTrue($this->resolver->can_see_comments(...$good_user));
        $this->assertTrue($this->resolver->can_see_comments(...$good_approver));
        $this->assertFalse($this->resolver->can_see_comments(...$good_stranger));
        $this->assertTrue($this->resolver->can_see_comments(...$good_admin));
        $this->assertFalse($this->resolver->can_see_comments(...$bad_user));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_user));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_approver));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_stranger));
        $this->assertFalse($this->resolver->is_allow_to_create(...$good_admin));
        $this->assertFalse($this->resolver->is_allow_to_create(...$bad_user));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_stranger)));
        $this->assertTrue($this->resolver->is_allow_to_delete(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_delete(...$this->fake_comment(...$bad_user)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_stranger)));
        $this->assertTrue($this->resolver->is_allow_to_update(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->is_allow_to_update(...$this->fake_comment(...$bad_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_user)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_approver)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_stranger)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$good_admin)));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(...$this->fake_comment(...$bad_user)));

        $this->assertFalse($this->resolver->is_allow_to_delete(
            ...$this->fake_comment_by($this->approver->id, ...$good_approver)
        ));
        $this->assertFalse($this->resolver->is_allow_to_update(
            ...$this->fake_comment_by($this->approver->id, ...$good_approver)
        ));
        $this->assertFalse($this->resolver->can_create_reaction_on_comment(
            ...$this->fake_comment_by($this->approver->id, ...$good_user)
        ));
    }
}

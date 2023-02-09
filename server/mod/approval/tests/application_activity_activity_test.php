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
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\model\application\activity\activity;
use mod_approval\model\application\activity\approvals_reset;
use mod_approval\model\application\activity\comment_created;
use mod_approval\model\application\activity\comment_deleted;
use mod_approval\model\application\activity\comment_replied;
use mod_approval\model\application\activity\comment_updated;
use mod_approval\model\application\activity\creation;
use mod_approval\model\application\activity\edited;
use mod_approval\model\application\activity\finished;
use mod_approval\model\application\activity\level_approved;
use mod_approval\model\application\activity\level_ended;
use mod_approval\model\application\activity\level_rejected;
use mod_approval\model\application\activity\level_started;
use mod_approval\model\application\activity\notification_sent;
use mod_approval\model\application\activity\stage_all_approved;
use mod_approval\model\application\activity\stage_ended;
use mod_approval\model\application\activity\stage_started;
use mod_approval\model\application\activity\stage_submitted;
use mod_approval\model\application\activity\uploaded;
use mod_approval\model\application\activity\withdrawn;
use mod_approval\model\application\application_activity as application_activity_model;
use mod_approval\totara_notification\resolver\stage_base;
use mod_approval\totara_notification\recipient\applicant;
use totara_core\advanced_feature;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\activity\activity
 */
class mod_approval_application_activity_activity_testcase extends testcase {
    /**
     * @covers ::is_valid_activity_info
     * @covers mod_approval\model\application\activity\comment_created::is_valid_activity_info
     * @covers mod_approval\model\application\activity\comment_deleted::is_valid_activity_info
     * @covers mod_approval\model\application\activity\comment_replied::is_valid_activity_info
     * @covers mod_approval\model\application\activity\comment_updated::is_valid_activity_info
     * @covers mod_approval\model\application\activity\finished::is_valid_activity_info
     * @covers mod_approval\model\application\activity\creation::is_valid_activity_info
     * @covers mod_approval\model\application\activity\edited::is_valid_activity_info
     * @covers mod_approval\model\application\activity\level_approved::is_valid_activity_info
     * @covers mod_approval\model\application\activity\level_ended::is_valid_activity_info
     * @covers mod_approval\model\application\activity\level_rejected::is_valid_activity_info
     * @covers mod_approval\model\application\activity\level_started::is_valid_activity_info
     * @covers mod_approval\model\application\activity\notification_sent::is_valid_activity_info
     * @covers mod_approval\model\application\activity\stage_all_approved::is_valid_activity_info
     * @covers mod_approval\model\application\activity\stage_ended::is_valid_activity_info
     * @covers mod_approval\model\application\activity\stage_started::is_valid_activity_info
     * @covers mod_approval\model\application\activity\stage_submitted::is_valid_activity_info
     * @covers mod_approval\model\application\activity\uploaded::is_valid_activity_info
     * @covers mod_approval\model\application\activity\withdrawn::is_valid_activity_info
     * @covers mod_approval\model\application\activity\approvals_reset::is_valid_activity_info
     */
    public function test_is_valid_activity_info(): void {
        $this->assertTrue(comment_created::is_valid_activity_info([]));
        $this->assertTrue(comment_deleted::is_valid_activity_info([]));
        $this->assertTrue(comment_replied::is_valid_activity_info([]));
        $this->assertTrue(comment_updated::is_valid_activity_info([]));
        $this->assertTrue(finished::is_valid_activity_info([]));
        $this->assertTrue(creation::is_valid_activity_info([]));
        $this->assertTrue(creation::is_valid_activity_info(['source' => 42]));
        $this->assertFalse(creation::is_valid_activity_info(['source' => 'baa']));
        $this->assertTrue(edited::is_valid_activity_info([]));
        $this->assertTrue(level_approved::is_valid_activity_info([]));
        $this->assertTrue(level_ended::is_valid_activity_info([]));
        $this->assertTrue(level_rejected::is_valid_activity_info([]));
        $this->assertTrue(level_started::is_valid_activity_info([]));
        $this->assertFalse(notification_sent::is_valid_activity_info([]));
        $this->assertFalse(notification_sent::is_valid_activity_info([
            'resolver_class_name' => 'invalid resolver class',
            'recipient_class_name' => applicant::class,
        ]));
        $this->assertFalse(notification_sent::is_valid_activity_info([
            'resolver_class_name' => stage_base::class,
            'recipient_class_name' => 'invalid recipient class',
        ]));
        $this->assertTrue(notification_sent::is_valid_activity_info([
            'resolver_class_name' => stage_base::class,
            'recipient_class_name' => applicant::class,
        ]));
        $this->assertTrue(stage_all_approved::is_valid_activity_info([]));
        $this->assertTrue(stage_ended::is_valid_activity_info([]));
        $this->assertTrue(stage_started::is_valid_activity_info([]));
        $this->assertTrue(stage_submitted::is_valid_activity_info([]));
        $this->assertTrue(uploaded::is_valid_activity_info([]));
        $this->assertTrue(withdrawn::is_valid_activity_info([]));
        $this->assertTrue(approvals_reset::is_valid_activity_info([]));
    }

    /**
     * @covers ::label
     * @covers ::from_type
     * @covers mod_approval\model\application\activity\comment_created::get_label_key
     */
    public function test_label(): void {
        try {
            activity::label(-1);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Activity type -1 is not defined', $ex->getMessage());
        }
        $this->assertEquals(
            get_string('model_application_activity_type_comment_created', 'mod_approval'),
            activity::label(comment_created::get_type())->out()
        );
    }

    /**
     * @covers ::get_description
     */
    public function test_get_description(): void {
        /** @var activity */
        $class = $this->getMockBuilder(activity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rp = new ReflectionProperty(activity::class, 'description');
        $rp->setAccessible(true);
        $rp->setValue($class, 'Hooray!');
        $this->assertEquals('Hooray!', $class->get_description());
    }

    /**
     * @covers ::from_system
     */
    public function test_from_system(): void {
        /** @var activity */
        $class = $this->getMockBuilder(activity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rp = new ReflectionProperty(activity::class, 'from_system');
        $rp->setAccessible(true);
        $rp->setValue($class, false);
        $this->assertFalse($class->from_system());
        $rp->setValue($class, true);
        $this->assertTrue($class->from_system());
    }

    /**
     * @covers ::for_system
     */
    public function test_for_system(): void {
        /** @var activity */
        $class = $this->getMockBuilder(activity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rp = new ReflectionProperty(activity::class, 'to_system');
        $rp->setAccessible(true);
        $rp->setValue($class, false);
        $this->assertFalse($class->for_system());
        $rp->setValue($class, true);
        $this->assertTrue($class->for_system());
    }

    public function test_get_description_by_user(): void {
        global $CFG;
        // Engage interferes user profile visibility
        advanced_feature::disable('engage_resources');
        advanced_feature::disable('container_workspace');

        $user = new user($this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'One']));
        $approver1 = new user($this->getDataGenerator()->create_user());
        $approver2 = new user($this->getDataGenerator()->create_user());
        $context = context_system::instance();
        $role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($role->id, $approver1->id, $context);

        // Victimise completed
        $this->overrideLangString('model_application_activity_type_finished_desc', 'mod_approval', '[{$a->user}]');
        $entity = new application_activity_entity(['id' => 42, 'user_id' => $user->id, 'activity_type' => finished::get_type()]);
        $model = application_activity_model::load_by_entity($entity);

        $this->setUser($user);
        $result = activity::from_activity($model)->get_description();
        $this->assertEquals("[<a href=\"$CFG->wwwroot/user/profile.php\">User One</a>]", $result);

        $this->setUser($approver1);
        $result = activity::from_activity($model)->get_description();
        $this->assertEquals("[<a href=\"$CFG->wwwroot/user/profile.php?id=$user->id\">User One</a>]", $result);

        $this->setUser($approver2);
        $result = activity::from_activity($model)->get_description();
        $this->assertEquals("[User One]", $result);
    }
}

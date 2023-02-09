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

use core\date_format;
use core\entity\user;
use core\format;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_perform\models\activity\activity;
use mod_approval\model\application\activity\comment_created;
use mod_approval\model\application\activity\comment_deleted;
use mod_approval\model\application\activity\comment_replied;
use mod_approval\model\application\activity\comment_updated;
use mod_approval\model\application\activity\finished;
use mod_approval\model\application\activity\creation;
use mod_approval\model\application\activity\edited;
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
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\totara_notification\resolver\stage_started as stage_started_resolver;
use mod_approval\totara_notification\recipient\applicant;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\type\application_activity
 */
class mod_approval_webapi_type_application_activity_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_application_activity';

    /** @var application */
    private $application;

    /** @var context */
    private $context;

    /** @var user */
    private $actor;

    public function setUp(): void {
        parent::setUp();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user($this->getDataGenerator()->create_user(
            ['firstname' => 'Tommy', 'lastname' => 'Tom', 'middlename' => '']
        ));
        $this->actor = new user($this->getDataGenerator()->create_user(
            ['firstname' => 'Sammy', 'lastname' => "Sam", 'middlename' => '']
        ));
        $this->application = $this->create_submitted_application($workflow, $assignment, $user);
        $this->context = $this->application->get_context();
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->application = $this->context = $this->actor = null;
    }

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * Create an activity instance.
     *
     * @param string|activity $activity_class
     * @param array $info
     * @param user|null $user
     * @return application_activity
     */
    private function create_activity(string $activity_class, array $info = [], user $user = null): application_activity {
        $activity = application_activity::create(
            $this->application,
            $user ? $user->id : $this->actor->id,
            $activity_class,
            $info
        );
        // Fix timestamp for testing.
        builder::table('approval_application_activity')
            ->where('id', $activity->id)
            ->update(['timestamp' => strtotime('2012-03-04T05:06:07+0800')]);
        return application_activity::load_by_id($activity->id);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_comment_created(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(comment_created::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Comment created', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> commented on the application",
            $value
        );
        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_comment_deleted(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(comment_deleted::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Comment deleted', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> deleted a comment",
            $value
        );
        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_comment_replied(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(comment_replied::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Comment replied', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> replied to a comment",
            $value
        );
        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_comment_updated(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(comment_updated::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Comment updated', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> edited a comment",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_completed(): void {
        $context = $this->context;
        $activity = $this->create_activity(finished::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Completed', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Application completed", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_creation(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(creation::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Application created', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> created application",
            $value
        );

        $source = $activity->application_id;
        $activity = $this->create_activity(creation::class, ['source' => $source]);
        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> created application from " .
            "<a href=\"{$CFG->wwwroot}/mod/approval/application/view.php?application_id={$source}\">Testing</a>",
            $value
        );

        $activity = $this->create_activity(creation::class, ['source' => -42]);
        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> created application from (deleted application)",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_edited(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(edited::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Edited', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> edited the application",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_level_approved(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(level_approved::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Approved', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> approved the application",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_level_ended(): void {
        $context = $this->context;
        $activity = $this->create_activity(level_ended::class);
        builder::table('approval_workflow_stage')
            ->where('id', $this->application->current_state->get_stage_id())
            ->update(['name' => 'Primo passo']);
        builder::table('approval_workflow_stage_approval_level')
            ->where('id', $this->application->current_state->get_approval_level_id())
            ->update(['name' => 'Maksimum niveau']);
        $activity->refresh(true);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Level completed', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Primo passo Maksimum niveau completed", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_level_rejected(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(level_rejected::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Rejected', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> rejected the application",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_level_started(): void {
        $context = $this->context;
        $activity = $this->create_activity(level_started::class);
        builder::table('approval_workflow_stage_approval_level')
            ->where('id', $this->application->current_state->get_approval_level_id())
            ->update(['name' => 'Maksimum niveau']);
        $activity->refresh(true);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Level started', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Awaiting Maksimum niveau approval", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_notification(): void {
        $context = $this->context;
        $activity = $this->create_activity(
            notification_sent::class,
            [
                'resolver_class_name' => stage_started_resolver::class,
                'recipient_class_name' => applicant::class,
            ]
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Notification sent', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "At start of stage notification sent to Applicant",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_stage_all_approved(): void {
        $context = $this->context;
        $activity = $this->create_activity(stage_all_approved::class);
        builder::table('approval_workflow_stage')
            ->where('id', $this->application->current_state->get_stage_id())
            ->update(['name' => 'Primo passo']);
        $activity->refresh(true);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('All approvals granted', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Primo passo all approvals granted", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_stage_ended(): void {
        $context = $this->context;
        $activity = $this->create_activity(stage_ended::class);
        builder::table('approval_workflow_stage')
            ->where('id', $this->application->current_state->get_stage_id())
            ->update(['name' => 'Primo passo']);
        $activity->refresh(true);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Stage completed', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Primo passo completed", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_stage_started(): void {
        $context = $this->context;
        $activity = $this->create_activity(stage_started::class);
        builder::table('approval_workflow_stage')
            ->where('id', $this->application->current_state->get_stage_id())
            ->update(['name' => 'Primo passo']);
        $activity->refresh(true);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertNull($value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Stage started', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals("Application in Primo passo", $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_stage_submitted(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(stage_submitted::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Form submitted', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> submitted the application",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_uploaded(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(uploaded::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp',
            $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name',
            $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('File uploaded', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> uploaded a file",
            $value
        );

        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_withdrawn(): void {
        global $CFG;
        $context = $this->context;
        $activity = $this->create_activity(withdrawn::class);

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $activity, [], $context);
        $this->assertEquals($activity->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $activity, [], $context);
        $this->assertEquals('Sammy Sam', $value->fullname);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'timestamp', $activity,
            ['format' => date_format::FORMAT_ISO8601],
            $context
        );
        $this->assertEquals('2012-03-04T05:06:07+0800', $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'activity_type_name', $activity,
            ['format' => format::FORMAT_PLAIN],
            $context
        );
        $this->assertEquals('Withdrawn', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'activity_type', $activity, [], $context);
        $this->assertEquals($activity->activity_type, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'description', $activity, ['format' => format::FORMAT_RAW], $context);
        $this->assertEquals(
            "<a href=\"{$CFG->wwwroot}/user/profile.php?id={$this->actor->id}\">" .
            "Sammy Sam</a> withdrew the application",
            $value
        );
        $value = $this->resolve_graphql_type(self::TYPE, 'stage', $activity, [], $context);
        $this->assertEquals($activity->stage, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'approval_level', $activity, [], $context);
        $this->assertEquals($activity->approval_level, $value);
    }
}

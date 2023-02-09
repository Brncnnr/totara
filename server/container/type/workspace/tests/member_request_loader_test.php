<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member_request;
use container_workspace\loader\member\member_request_loader;
use container_workspace\query\member\member_request_query;
use container_workspace\query\member\member_request_status;

class container_workspace_member_request_loader_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_pending_requests(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(
            "Hippo hip hop",
            null,
            FORMAT_PLAIN,
            null,
            true
        );

        $this->assertTrue($workspace->is_private());
        $workspace_id = $workspace->get_id();

        $user_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
            member_request::create($workspace_id, $user->id);
        }

        // Load the pending requests.
        $query = new member_request_query($workspace_id);
        $offset_paginator = member_request_loader::get_member_requests($query);

        $this->assertEquals(5, $offset_paginator->get_total());

        /** @var member_request[] $requests */
        $requests = $offset_paginator->get_items()->all();

        foreach ($requests as $member_request) {
            $this->assertInstanceOf(member_request::class, $member_request);
            $this->assertFalse($member_request->is_cancelled());
            $this->assertFalse($member_request->is_declined());
            $this->assertFalse($member_request->is_accepted());

            $this->assertContainsEquals($member_request->get_user_id(), $user_ids);
        }
    }

    /**
     * @return void
     */
    public function test_fetch_accepted_request(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace("Hippo hppoo hop");

        $this->assertTrue($workspace->is_private());
        $workspace_id = $workspace->get_id();

        $user_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
            $member_request = member_request::create($workspace_id, $user->id);

            // Accept by admin
            $member_request->accept();
        }

        // Load the accepted requests.
        $query = new member_request_query($workspace_id);
        $query->set_member_request_status(member_request_status::ACCEPTED);

        $offset_paginator = member_request_loader::get_member_requests($query);
        $this->assertEquals(5, $offset_paginator->get_total());

        /** @var member_request[] $requests */
        $requests = $offset_paginator->get_items()->all();

        foreach ($requests as $member_request) {
            $this->assertInstanceOf(member_request::class, $member_request);
            $this->assertFalse($member_request->is_cancelled());
            $this->assertFalse($member_request->is_declined());
            $this->assertTrue($member_request->is_accepted());

            $this->assertContainsEquals($member_request->get_user_id(), $user_ids);
        }
    }

    /**
     * @return void
     */
    public function test_fetch_declined_requests(): void {
        $generator = $this->getDataGenerator();
        $this->setAdminUser();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace("Hippo Dono nodo");

        $this->assertTrue($workspace->is_private());
        $workspace_id = $workspace->get_id();

        $user_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;
            $member_request = member_request::create($workspace_id, $user->id);

            // Decline by admin
            $member_request->decline();
        }

        // Load the declined requests.
        $query = new member_request_query($workspace_id);
        $query->set_member_request_status(member_request_status::DECLINED);

        $offset_paginator = member_request_loader::get_member_requests($query);
        $this->assertEquals(5, $offset_paginator->get_total());

        /** @var member_request[] $requests */
        $requests = $offset_paginator->get_items()->all();

        foreach ($requests as $member_request) {
            $this->assertInstanceOf(member_request::class, $member_request);
            $this->assertFalse($member_request->is_cancelled());
            $this->assertTrue($member_request->is_declined());
            $this->assertFalse($member_request->is_accepted());

            $this->assertContainsEquals($member_request->get_user_id(), $user_ids);
        }
    }

    /**
     * @return void
     */
    public function test_fetch_cancelled_request(): void {
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setAdminUser();

        $workspace = $workspace_generator->create_private_workspace();
        $user_ids = [];

        $workspace_id = $workspace->get_id();

        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $user_ids[] = $user->id;

            $member_request = member_Request::create($workspace_id, $user->id);

            // Cancel the request right away
            $member_request->cancel($user->id);
        }

        $query = new member_request_query($workspace_id);
        $query->set_member_request_status(member_request_status::CANCELLED);

        $offset_paginator = member_request_loader::get_member_requests($query);
        $this->assertEquals(5, $offset_paginator->get_total());

        /** @var member_request[] $member_requests */
        $member_requests = $offset_paginator->get_items()->all();
        foreach ($member_requests as $member_request) {
            $this->assertFalse($member_request->is_accepted());
            $this->assertFalse($member_request->is_declined());
            $this->assertTrue($member_request->is_cancelled());

            $this->assertContainsEquals($member_request->get_user_id(), $user_ids);
        }
    }

    /**
     * @return void
     */
    public function test_load_member_request_from_course(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $query = new member_request_query($course->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expecting an instance of container_workspace from id '{$course->id}'");

        member_request_loader::get_member_requests($query);
    }
}
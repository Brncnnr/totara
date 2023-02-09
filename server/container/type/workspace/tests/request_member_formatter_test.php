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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

use container_workspace\formatter\member\member_request_formatter;
use container_workspace\member\member_request;
use totara_engage\formatter\field\date_field_formatter;
use container_workspace\interactor\workspace\interactor;
use container_workspace\testing\generator;
use core_phpunit\testcase;

/**
 * @group container_workspace
 * @group totara_engage
 */
class request_member_formatter_test extends testcase {
    /**
     * @return void
     */
    public function test_request_member_formatter(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace('private workspace');

        $request = member_request::create($workspace->get_id(), $user_two->id);
        $context = context_course::instance($workspace->get_id());
        $formatter = new member_request_formatter($request, $context);
        self::assertEquals($workspace->get_id(), $formatter->format('workspace_id'));
        self::assertEquals($request->is_declined(), $formatter->format('is_declined'));
        self::assertEquals($request->is_accepted(), $formatter->format('is_accepted'));
        self::assertEquals( $request->get_user(), $formatter->format('user'));
        self::assertEquals($request->get_id(), $formatter->format('id'));
        self::assertInstanceOf(interactor::class, $formatter->format('workspace_interactor'));
        $date_field_formatter = new date_field_formatter(null, $context);
        self::assertEquals(
            $date_field_formatter->format($request->get_time_created()),
            $formatter->format('time_description')
        );
    }
}
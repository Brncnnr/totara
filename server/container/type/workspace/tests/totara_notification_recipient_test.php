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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 * @category totara_notification
 */

use container_workspace\totara_notification\recipient\workspace_owner;
use container_workspace\workspace;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_totara_notification_recipient_testcase extends testcase {
    /**
     * @return void
     */
    public function test_workspace_owner(): void {
        self::setAdminUser();
        $workspace = $this->create_workspace();

        $owner_ids = workspace_owner::get_user_ids(['workspace_id' => $workspace->get_id()]);
        self::assertEqualsCanonicalizing([$workspace->get_user_id()], $owner_ids);

        $owner_ids = workspace_owner::get_user_ids([]);
        self::assertIsArray($owner_ids);
        self::assertEmpty($owner_ids);

        $owner_ids = workspace_owner::get_user_ids(['workspace_id' => 1234567890]);
        self::assertIsArray($owner_ids);
        self::assertEmpty($owner_ids);
    }

    /**
     * Create a workspace
     *
     * @param ...$args mixed Params to pass to create_workspace
     * @return workspace
     */
    protected function create_workspace(...$args): workspace {
        return $this->workspace_generator()->create_workspace(...$args);
    }

    /**
     * @return \container_workspace\testing\generator
     */
    protected function workspace_generator(): \container_workspace\testing\generator {
        /** @var \container_workspace\testing\generator $gen */
        $gen = self::getDataGenerator()
            ->get_plugin_generator('container_workspace');
        return $gen;
    }
}

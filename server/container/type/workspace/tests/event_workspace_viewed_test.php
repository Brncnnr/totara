<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\event\workspace_viewed;
use container_workspace\local\workspace_helper;
use ml_recommender\entity\interaction;

class container_workspace_event_workspace_viewed_testcase extends advanced_testcase {

    public function test_view_private_workspace_interaction(): void {
        global $USER;
        $this->setAdminUser();

        $workspace = workspace_helper::create_workspace(
            'Workspace private',
            $USER->id,
            null,
            null,
            null,
            null,
            true
        );
        $event = workspace_viewed::from_workspace($workspace);
        $this->assertFalse($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();

        $this->assertEmpty($interactions);
    }

    public function test_view_public_workspace_interaction(): void {
        global $USER;
        $this->setAdminUser();

        $workspace = workspace_helper::create_workspace('Workspace public', $USER->id);
        $event = workspace_viewed::from_workspace($workspace);
        $this->assertTrue($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertNotEmpty($interactions);
    }

    public function test_guest_user_workspace_interaction(): void {
        global $USER;
        // create workspace as admin user
        $this->setAdminUser();
        $workspace = workspace_helper::create_workspace('Workspace public', $USER->id);

        // view workspace as guest user
        $this->setGuestUser();
        $event = workspace_viewed::from_workspace($workspace);
        $this->assertTrue($event->is_public());
        $this->assertTrue(isguestuser($event->get_user_id()));
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertEmpty($interactions);
    }
}
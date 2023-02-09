<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\controllers\profile\base as base_controller;

/**
 * @group totara_competency
 */
class competency_controllers_profile_base_testcase extends advanced_testcase {

    /**
     * Not finding the user requested via user_id query param should result in a moodle exception being thrown,d
     * which will intern be displayed to the user with a local specific message.
     */
    public function test_attempt_to_setup_invalid_user(): void {
        self::setAdminUser();

        $_GET['user_id'] = 90000; // Non existent user id

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('invaliduser', 'error'));

        $controller = new class extends base_controller {

        };
    }


}

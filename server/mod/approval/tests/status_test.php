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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\model\status;

/**
 * @group approval_workflow
 */
class mod_approval_status_testcase extends testcase {

    public function test_get_list() {
        $status_list = status::get_list();
        $this->assertCount(4, $status_list);

        $expected = [
            [
                'label' => 'All',
                'enum' => null
            ],
            [
                'label' => get_string('model_status_draft', 'mod_approval'),
                'enum' => strtoupper(status::DRAFT_ENUM),
            ],
            [
                'label' => get_string('model_status_active', 'mod_approval'),
                'enum' => strtoupper(status::ACTIVE_ENUM),
            ],
            [
                'label' => get_string('model_status_archived', 'mod_approval'),
                'enum' => strtoupper(status::ARCHIVED_ENUM),
            ],
        ];
        $this->assertEquals($expected, $status_list);
    }
}

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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\totara_engage\link\workspace_destination;
use totara_engage\link\builder;
use totara_engage\link\empty_destination;

defined('MOODLE_INTERNAL') || die();

class container_workspace_link_generator_testcase extends advanced_testcase {
    /**
     * Validate the source generator can create links properly
     */
    public function test_source_generator() {
        $cases = [
            ['ws.1.0', '/container/type/workspace/workspace.php?id=1'],
            ['ws.1.1', '/container/type/workspace/workspace.php?id=1&tab=library'],
            ['ws.1.2', '/container/type/workspace/workspace.php?id=1&tab=members'],
        ];

        foreach ($cases as $case) {
            $generator = builder::from_source($case[0]);
            if (null !== $case[1]) {
                $this->assertSame($case[1], $generator->url()->out_as_local_url(false));
            } else {
                $this->assertInstanceOf(empty_destination::class, $generator);
            }
        }
    }

    /**
     * Validate the destination tests correctly
     */
    public function test_destination_generator() {
        /** @var workspace_destination $generator */
        $generator = builder::to('container_workspace');
        $this->assertInstanceOf(workspace_destination::class, $generator);

        $cases = [
            ['tab_library', '/container/type/workspace/workspace.php?id=1&tab=library'],
            ['tab_members', '/container/type/workspace/workspace.php?id=1&tab=members'],
            ['tab_discussions', '/container/type/workspace/workspace.php?id=1'],
        ];

        foreach ($cases as $case) {
            $generator = builder::to('container_workspace', ['id' => 1]);
            $generator = call_user_func([$generator, $case[0]]);
            $url = $generator->url()->out_as_local_url(false);
            $this->assertSame($case[1], $url);
        }
    }
}
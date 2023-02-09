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

use container_workspace\query\workspace\access;

class container_workspace_workspace_access_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_value(): void {
        $this->assertSame(access::PUBLIC, access::get_value('PUBLIC'));
        $this->assertSame(access::PRIVATE, access::get_value('PRIVATE'));
        $this->assertSame(access::HIDDEN, access::get_value('HIDDEN'));
    }

    /**
     * @return void
     */
    public function test_get_invalid_value(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No constant 'static::SOMETHING_ELSE' was found");

        access::get_value('SOMETHING_ELSE');
    }

    /**
     * @return void
     */
    public function test_get_code(): void {
        $this->assertSame('PUBLIC', access::get_code(access::PUBLIC));
        $this->assertSame('PRIVATE', access::get_code(access::PRIVATE));
        $this->assertSame('HIDDEN', access::get_code(access::HIDDEN));
    }

    /**
     * @return void
     */
    public function test_get_invalid_code(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot find code for constant value \'15\'');

        access::get_code(15);
    }
}
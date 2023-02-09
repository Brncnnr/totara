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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\pagination\offset_cursor;

class core_offset_cursor_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_set_page_zero(): void {
        $cursor = new offset_cursor();
        $cursor->set_page(0);

        $this->assertSame(0, $cursor->get_page());
    }

    /**
     * @return void
     */
    public function test_set_invalid_page(): void {
        $invalid_pages =  [-1, -2, -3, -100];

        foreach ($invalid_pages as $invalid_page) {
            try {
                $cursor = new offset_cursor();
                $cursor->set_page($invalid_page);

                $this->fail("Expecting the invalid page '{$invalid_page}' to cause the error");
            } catch (Throwable $e) {
                $this->assertInstanceOf(coding_exception::class, $e);
                $this->assertStringContainsString("Page has to be a positive integer", $e->getMessage());
            }

        }
    }

    /**
     * @return void
     */
    public function test_set_page_zero_via_construction(): void {
        $cursor = new offset_cursor(['page' => 0, 'limit' => 50]);
        $this->assertSame(0, $cursor->get_page());
    }

    /**
     * @return void
     */
    public function test_set_invalid_page_via_construction(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("You must provide a positive page number within your cursor.");

        new offset_cursor(['page' => -1, 'limit' => 50]);
    }
}
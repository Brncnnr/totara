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

use core\json_editor\node\attribute\extra_linked_file;

class core_json_editor_extra_linked_file_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_extra_linked_validate_invalid_url(): void {
        $subtitle = new extra_linked_file(
            '@@PLUGINFILE@@/file_mp3.vtt',
            'file_mp3.vtt'
        );

        self::assertFalse($subtitle->is_url_rewritten());
        try {
            $subtitle->ensure_url_rewritten();
        } catch (coding_exception $e) {
            self::assertStringContainsString(
                "The file url had not been rewritten yet",
                $e->getMessage()
            );
            return;
        }

        $this->fail("Expecting coding_exception to be thrown");
    }

    /**
     * @return void
     */
    public function test_get_file_url_with_invalid_url(): void {
        $file = new extra_linked_file(
            '@@PLUGINFILE@@/file_me.vtt',
            'file_me.vtt'
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The file url had not been rewritten yet");

        $file->get_file_url();
    }

    /**
     * @return void
     */
    public function test_get_file_url(): void {
        $file = new extra_linked_file(
            'http://example.com/file_me.vtt',
            'file_me.vtt'
        );

        $file_url = $file->get_file_url(true);

        self::assertEquals(1, $file_url->get_param('forcedownload'));
        self::assertEquals('example.com', $file_url->get_host());
        self::assertEquals('http', $file_url->get_scheme());
    }
}
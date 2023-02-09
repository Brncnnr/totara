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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use totara_webapi\endpoint_type\dev;
use totara_webapi\schema_file_loader;

defined('MOODLE_INTERNAL') || die();

class totara_webapi_schema_file_loader_test extends \advanced_testcase {

    public function test_load_files() {
        global $CFG;

        $loader = new schema_file_loader(new dev());
        $files = $loader->load();

        $this->assertIsArray($files);
        $this->assertNotEmpty($files);

        // Check existing of a specific file we can be sure is there
        $this->assertArrayHasKey($CFG->dirroot.'/totara/webapi/webapi/status.graphqls', $files);

        foreach ($files as $filename => $content) {
            $this->assertMatchesRegularExpression('/[a-z0-9_-]+\.graphqls$/', $filename);
            $this->assertFileExists($filename);
            $this->assertFileIsReadable($filename);
            $this->assertNotEmpty($content);
            $this->assertStringEqualsFile($filename, $content);
        }
    }

}
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

use totara_reportbuilder\report_helper;

defined('MOODLE_INTERNAL') || die();

class totara_reportbuilder_report_helper_testcase extends advanced_testcase {

    /**
     * @param string $source_name
     * @dataProvider sources_provider
     */
    public function test_get_sources_and_create(string $source_name): void {
        global $DB;

        self::setAdminUser();

        $report_id = report_helper::create($source_name);

        self::assertNotEmpty($report_id);
        self::assertEquals($source_name, $DB->get_field('report_builder', 'source', ['id' => $report_id]));
    }

    public function sources_provider(): array {
        $sources = report_helper::get_sources();

        if (!is_array($sources) || count($sources) === 0) {
            $this->fail('Sources must be a non empty array');
        }

        $cases = [];

        foreach ($sources as $source) {
            $cases[$source] = [$source];
        }

        return $cases;
    }

}

<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 *
 * Unit/functional tests to check Record of Learning: Objectives reports caching
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_column_option.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_column_testcase extends advanced_testcase {

    protected function setUp(): void {
        parent::setup();
    }

    /**
     * Test is searchable cases
     * @dataProvider is_searchable_provider
     * @param string $type
     * @param string $format
     * @param string $grouping
     * @param bool $iscompound
     * @param bool $expect
     */
    public function test_is_searchable($type, $format, $grouping, $iscompound, $expect) {
        $options = [
            'dbdatatype'   => $type,
            'outputformat' => $format,
            'grouping'     => $grouping,
            'iscompound'   => $iscompound,
        ];
        $column = new rb_column_option('type', 'value', 'name', 'field', $options);
        $this->assertEquals($expect, $column->is_searchable());
    }

    public function is_searchable_provider() {
        return array(
            array('char', 'text', 'none', false, true),
            array('char', 'text', 'none', true, false),
            array('text', 'text', 'none', false, true),
            array('text', 'text', 'none', true, false),
            array('char', 'text', 'yes', false, false),
            array('char', 'date', 'none', false, false),
            array('timestamp', 'text', 'none', false, false),
            array('timestamp', 'text', 'yes', false, false)
        );
    }
}

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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 * @category test
 */

use core\format;
use mod_perform\models\activity\element;
use performelement_long_text\formatter\response_formatter;

global $CFG;
require_once($CFG->dirroot . '/mod/perform/tests/weka_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_long_text_response_formatter_testcase extends mod_perform_weka_testcase {

    /**
     * @dataProvider format_provider
     * @param string $format
     * @param string $input
     * @param string $expected_output
     */
    public function test_format(string $format, string $input, string $expected_output): void {
        self::setAdminUser();

        $formatter = new class($this->getMockClass(element::class), context_system::instance()) extends response_formatter {
            public function set_format(string $format): void {
                $this->format = $format;
            }
        };
        $formatter->set_format($format);

        $actual_output = $formatter->format($input);
        $this->assertEquals($expected_output, $actual_output);
    }

    public function format_provider(): array {
        return [
            'Plain text' => [
                'format' => format::FORMAT_PLAIN,
                'input' => $this->create_weka_document_with_text(true, 'Test!'),
                'expected_output' => json_encode("Test!"),
            ],
            'HTML' => [
                'format' => format::FORMAT_HTML,
                'input' => $this->create_weka_document_with_text(true, 'Test!'),
                'expected_output' => json_encode("<div class=\"tui-rendered\"><p>Test!</p></div>"),
            ],
            'Raw' => [
                'format' => format::FORMAT_RAW,
                'input' => $this->create_weka_document_with_text(true, 'Test!'),
                'expected_output' => $this->create_weka_document_with_text(true, 'Test!'),
            ],
        ];
    }

}

<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package core
 */

use core_phpunit\testcase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

defined('MOODLE_INTERNAL') || die();

/**
 * Test the excel & ods lib class
 */
class core_spreadsheet_writer_testcase extends testcase {
    /**
     * @return void
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->libdir . '/excellib.class.php');
        require_once($CFG->libdir . '/odslib.class.php');
    }

    /**
     * @return array[]
     */
    public function cell_data_xlsx(): array {
        return [
            // Cell, Raw Data, Expected Data, Expected Type
            "Number" => [123456, 123456, DataType::TYPE_NUMERIC],
            "Number with line break" => ["12456\n", 12456, DataType::TYPE_NUMERIC],
            "Number with text" => ["123 ABC456", "123 ABC456", DataType::TYPE_STRING],
            "Number with whitespace" => ["123456  ", "123456  ", DataType::TYPE_STRING],
            "Number with mid-linebreak" => ["123\n456", "123\n456", DataType::TYPE_STRING],
            "Number with preceding-linebreak" => ["\n123456", "\n123456", DataType::TYPE_STRING],
            "Number with complicated spacing" => ["12345 E \n", "12345 E \n", DataType::TYPE_STRING],
            "Number with tricky spacing" => ["12345  \n", "12345  \n", DataType::TYPE_STRING],
            "Science string" => ["6.022E23", 6.022E+23, DataType::TYPE_NUMERIC],
            "Science number" => [6.022E23, 6.022E+23, DataType::TYPE_NUMERIC],
            "Science number 2" => [6.022E+23, 6.022E+23, DataType::TYPE_NUMERIC],
            "Science string with newline" => ["6.022E23\n", 6.022E+23, DataType::TYPE_NUMERIC],
            "Hex number" => [0x2A, 0x2A, DataType::TYPE_NUMERIC],
            "Hex number with new line" => ["0x2A\n", "0x2A\n", DataType::TYPE_STRING],
        ];
    }

    /**
     * @return array[]
     */
    public function cell_data_ods(): array {
        return [
            // Cell, Raw Data, Expected Data, Expected Type
            "Number" => [123456, "123456", "float"],
            "Number with line break" => ["12456\n", 12456, "float"],
            "Number with text" => ["123 ABC456", "123 ABC456", "string"],
            "Number with whitespace" => ["123456  ", "123456  ", "string"],
            "Number with mid-linebreak" => ["123\n456", "123\n456", "string"],
            "Number with preceding-linebreak" => ["\n123456", "\n123456", "string"],
            "Number with complicated spacing" => ["12345 E \n", "12345 E \n", "string"],
            "Number with tricky spacing" => ["12345  \n", "12345  \n", "string"],
            "Science string" => ["6.022E23", "6.022E23", "float"],
            "Science number" => [6.022E23, "6.022E+23", "float"],
            "Science number 2" => [6.022E+23, "6.022E+23", "float"],
            "Science string with newline" => ["6.022E23\n", "6.022E23", "float"],
            "Hex number" => [0x2A, 0x2A, "float"],
            "Hex number with new line" => ["0x2A\n", "0x2A\n", "string"],
        ];
    }

    /**
     * @param $raw_data
     * @param $expected_data
     * @param $expected_format
     * @return void
     * @dataProvider cell_data_xlsx
     */
    public function test_numbers_are_formatted_xlsx($raw_data, $expected_data, $expected_format): void {
        $workbook = new MoodleExcelWorkbook('testing', 'xlsx');
        $worksheet = $workbook->add_worksheet('testing');

        $worksheet->write(0, 0, $raw_data);

        $prop = new ReflectionProperty($worksheet, 'worksheet');
        $prop->setAccessible(true);

        /** @var Worksheet $sheet */
        $sheet = $prop->getValue($worksheet);

        $cell = $sheet->getCell('A1');
        self::assertSame($expected_data, $cell->getValue());
        self::assertSame($expected_format, $cell->getDataType());
    }

    /**
     * @param $raw_data
     * @param $expected_data
     * @param $expected_format
     * @return void
     * @dataProvider cell_data_ods
     */
    public function test_numbers_are_formatted_ods($raw_data, $expected_data, $expected_format): void {
        $workbook = new MoodleODSWorkbook('testing', false);
        $worksheet = $workbook->add_worksheet('testing');

        $worksheet->write(0, 0, $raw_data);
        /** @var MoodleODSCell $cell */
        $cell = $worksheet->data[0][0];

        self::assertEquals($expected_data, $cell->value);
        self::assertSame($expected_format, $cell->type);
    }
}
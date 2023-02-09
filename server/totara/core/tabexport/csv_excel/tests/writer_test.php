<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package tabexport_csv_excel
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test the writer class.
 */
class tabexport_csv_excel_writer_testcase extends \basic_testcase {

    public function test_escape_formula() {
        $method = new \ReflectionMethod('\tabexport_csv_excel\writer', 'escape_formula');
        $method->setAccessible(true);

        self::assertSame("abc", $method->invoke(null, 'abc'));
        self::assertSame("\t+abc", $method->invoke(null, '+abc'));
        self::assertSame("\t-abc", $method->invoke(null, '-abc'));
        self::assertSame("\t=abc", $method->invoke(null, '=abc'));
        self::assertSame("\t@abc", $method->invoke(null, '@abc'));
        self::assertSame("*abc", $method->invoke(null, '*abc'));
        self::assertSame("(abc", $method->invoke(null, '(abc'));
        self::assertSame("{abc", $method->invoke(null, '{abc'));
        self::assertSame("[abc", $method->invoke(null, '[abc'));
        self::assertSame(" abc", $method->invoke(null, ' abc'));
        self::assertSame("\tabc", $method->invoke(null, "\tabc"));
        self::assertSame(" =abc", $method->invoke(null, " =abc"));
        self::assertSame("\t=abc", $method->invoke(null, "\t=abc"));
        self::assertSame("\t=\tabc", $method->invoke(null, "=\tabc"));
        self::assertSame("\t=@+-abc", $method->invoke(null, "=@+-abc"));
        self::assertSame("a=@+-bc", $method->invoke(null, "a=@+-bc"));
        self::assertSame("a\nb\nc", $method->invoke(null, "a\nb\nc"));
        self::assertSame("\t=a\nb\nc", $method->invoke(null, "=a\nb\nc"));
        self::assertSame("a\n=b\nc", $method->invoke(null, "a\n=b\nc"));

        self::assertSame("123", $method->invoke(null, '123'));
        self::assertSame("\t+123", $method->invoke(null, '+123'));
        self::assertSame("\t-123", $method->invoke(null, '-123'));
        self::assertSame("\t+123.12", $method->invoke(null, '+123.12'));
        self::assertSame("\t-123.12", $method->invoke(null, '-123.12'));
        self::assertSame("~123.12", $method->invoke(null, '~123.12'));
        self::assertSame(".123", $method->invoke(null, '.123'));

        $property = new \ReflectionProperty('\tabexport_csv_excel\writer', 'formula_escape_char');
        $property->setAccessible(true);
        $property->setValue(null, "'");
        self::assertSame("abc", $method->invoke(null, 'abc'));
        self::assertSame("'+abc", $method->invoke(null, '+abc'));
        self::assertSame("'-abc", $method->invoke(null, '-abc'));
        self::assertSame("'=abc", $method->invoke(null, '=abc'));
        self::assertSame("'@abc", $method->invoke(null, '@abc'));
        self::assertSame("'abc", $method->invoke(null, '\'abc'));
        self::assertSame("'='abc", $method->invoke(null, '=\'abc'));
    }

}

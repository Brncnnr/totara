<?php
/*
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_core
 */

use core_phpunit\testcase;
use totara_core\strftime as core_strftime;

defined('MOODLE_INTERNAL') || die();

class totara_core_strftime_testcase extends testcase {
    /**
     * Base timestamp used for all our tests.
     * This date's specifically chosen as it crosses both the year & week boundaries.
     * 2005-01-03T15:32:12+13:00
     *
     * @var null|int
     */
    const TIMESTAMP = 1104719532;

    /**
     * @var bool|null
     */
    private ?bool $has_pattern_generator = null;

    /**
     * Collection of simple placeholders to check against.
     *
     * @return array
     */
    public function placeholders(): array {
        return [
            // placeholder, Pacific/Auckland, America/Los_Angeles, GMT, Locale, Timestamp
            ['%d', '03', '02', '03'],
            ['%e', ' 3', ' 2', ' 3'],
            ['%j', '003', '002', '003'],
            ['%u', '1', '7', '1'],
            ['%w', '1', '0', '1'],
            ['%V', '01', '53', '01'],
            ['%U', '01', '01', '01'],
            ['%W', '01', '00', '01'],
            ['%m', '01', '01', '01'],
            ['%C', '20', '20', '20'],
            ['%g', '05', '04', '05'],
            ['%G', '2005', '2004', '2005'],
            ['%y', '05', '05', '05'],
            ['%#y', '05', '05', '05'],
            ['%-y', '5', '5', '5'], // Remove the 0's
            ['%_y', ' 5', ' 5', ' 5'], // 0 to spaces
            ['%Y', '2005', '2005', '2005'],
            ['%H', '15', '18', '02'],
            ['%k', ' 6', ' 9', '17', null, 1104687532], // Test the space
            ['%k', '15', '18', ' 2'], // Test the space in format_gm
            ['%I', '03', '06', '02'],
            ['%l', ' 3', ' 6', ' 2'],
            ['%M', '32', '32', '32'],
            ['%p', 'PM', 'PM', 'AM'],
            ['%P', 'pm', 'pm', 'am'],
            ['%r', '03:32:12 PM', '06:32:12 PM', '02:32:12 AM'],
            ['%R', '15:32', '18:32', '02:32'],
            ['%S', '12', '12', '12'],
            ['%T', '15:32:12', '18:32:12', '02:32:12'],
            ['%z', '+1300', '-0800', '+0000'],
            ['%Z', 'NZDT', 'PST', 'GMT'],
            ['%D', '01/03/05', '01/02/05', '01/03/05'],
            ['%F', '2005-01-03', '2005-01-02', '2005-01-03'],
            ['%s', '1104719532', '1104719532', '1104719532'],
            ['%%', '%', '%', '%'],
            ['%t', "\t", "\t", "\t"],
            ['%n', "\n", "\n", "\n"],
            ['%Q', "%Q", "%Q", "%Q"], // Unknown placeholder
            ['No placeholders', "No placeholders", "No placeholders", "No placeholders"],
            ['%%s', '%s', '%s', '%s'], // Escaping %
            ['%OH', '15', '18', '02'], // Alternative syntax
            // Locale-specific testcases
            ['%A at %R during %Z', 'Monday at 15:32 during NZDT', 'Sunday at 18:32 during PST', 'Monday at 02:32 during GMT', 'en_AU.utf8'],
            ['%A at %R during %Z', 'Monday at 15:32 during NZDT', 'Sunday at 18:32 during PST', 'Monday at 02:32 during GMT', 'en_NZ.utf8'],
            ['%A', 'Monday', 'Sunday', 'Monday', 'en_NZ.utf8'],
            ['%A', 'Montag', 'Sonntag', 'Montag', 'de_DE.utf8'],
            ['%a', 'Mon', 'Sun', 'Mon', 'en_AU.utf8'],
            ['%a', 'Mo', 'So', 'Mo', 'de_DE.utf8'],
            ['%b', 'Jan', 'Jan', 'Jan', 'en_US.utf8'],
            ['%b', 'Jan', 'Jan', 'Jan', 'de_DE.utf8'],
            ['%B', 'January', 'January', 'January', 'en_AU.utf8'],
            ['%B', 'Januar', 'Januar', 'Januar', 'de_DE.utf8'],
            ['%h', 'Jan', 'Jan', 'Jan', 'en_NZ.utf8'],
            ['%h', 'Jan', 'Jan', 'Jan', 'de_DE.utf8'],
            // Locale-specific testcases, without checking values
            ['%a', true, true, true],
            ['%A', true, true, true],
            ['%b', true, true, true],
            ['%B', true, true, true],
            ['%h', true, true, true],
            ['%x', true, true, true],
            ['%X', true, true, true],
            ['%c', true, true, true],
        ];
    }

    /**
     * Collection of complicated test cases that come via the pattern generator. These are specific to the identified locale.
     * If the pattern generator does not exist, then it will instead check the placeholder was swapped out.
     *
     * @return array
     */
    public function placeholders_complicated(): array {
        return [
            // placeholder, Pacific/Auckland, America/Los_Angeles, GMT, Locale
            ['%X', '3:32:12 pm', '6:32:12 pm', '2:32:12 am', 'en_NZ.utf8'],
            ['%X', '3:32:12 PM', '6:32:12 PM', '2:32:12 AM', 'en_US.utf8'],
            ['%X', '15:32:12', '18:32:12', '02:32:12', 'en_GB.utf8'],
            ['%X', '15:32:12', '18:32:12', '02:32:12', 'de_DE.utf8'],
            ['%x', '3/01/2005', '2/01/2005', '3/01/2005', 'en_NZ.utf8'],
            ['%x', '1/3/2005', '1/2/2005', '1/3/2005', 'en_US.utf8'],
            ['%x', '03/01/2005', '02/01/2005', '03/01/2005', 'en_GB.utf8'],
            ['%x', '3.1.2005', '2.1.2005', '3.1.2005', 'de_DE.utf8'],
            ['%c', 'Mon, 3 Jan 2005, 3:32:12 pm', 'Sun, 2 Jan 2005, 6:32:12 pm', 'Mon, 3 Jan 2005, 2:32:12 am', 'en_NZ.utf8'],
            ['%c', 'Mon, Jan 3, 2005, 3:32:12 PM', 'Sun, Jan 2, 2005, 6:32:12 PM', 'Mon, Jan 3, 2005, 2:32:12 AM', 'en_US.utf8'],
            ['%c', 'Mon, 3 Jan 2005, 15:32:12', 'Sun, 2 Jan 2005, 18:32:12', 'Mon, 3 Jan 2005, 02:32:12', 'en_GB.utf8'],
            ['%c', 'Mo., 3. Jan. 2005, 15:32:12', 'So., 2. Jan. 2005, 18:32:12', 'Mo., 3. Jan. 2005, 02:32:12', 'de_DE.utf8'],
        ];
    }

    /**
     * Sanity check, make sure every placeholder we support is covered by
     * one of the tests here.
     *
     * @return void
     */
    public function test_placeholders_are_tested(): void {
        $strftime = new ReflectionMethod(core_strftime::class, 'get_map');
        $strftime->setAccessible(true);
        $map = array_keys($strftime->invoke(null));

        // Get the known placeholders
        $known_placeholders = array_merge(
            array_column($this->placeholders(), 0),
            array_column($this->placeholders_complicated(), 0),
        );

        $missing = array_diff($map, $known_placeholders);
        $this->assertEmpty($missing, sprintf('%s untested placeholders: "%s"', core_strftime::class, join('", "', $missing)));
    }

    /**
     * Test the complicated placeholders and check the results.
     * If the IntlDatePatternGenerator class does not exist, then we will just check
     * the placeholder was replaced, but the actual value will not be checked in this instance.
     *
     * @param string $placeholder
     * @param string $nzdt_result
     * @param string $pst_result
     * @param string $gmt_result
     * @param string $locale
     * @return void
     * @dataProvider placeholders_complicated
     */
    public function test_placeholders_complicated(
        string $placeholder,
        string $nzdt_result,
        string $pst_result,
        string $gmt_result,
        string $locale
    ): void {
        $timestamp ??= self::TIMESTAMP;

        // We can test locales strings if the locale itself is available
        try {
            $this->setLocale(LC_TIME, $locale);
        } catch (Exception $ex) {
            $this->markTestSkipped('Locale “' . $locale . '” was not available for testing');
        }

        $this->setTimezone('Pacific/Auckland');
        $result_nzt = core_strftime::format($placeholder, $timestamp);
        $this->setTimezone('America/Los_Angeles');
        $result_pst = core_strftime::format($placeholder, $timestamp);
        $result_gmt = core_strftime::format_gm($placeholder, $timestamp);

        if ($this->has_pattern_generator()) {
            self::assertSame($nzdt_result, $result_nzt);
            self::assertSame($pst_result, $result_pst);
            self::assertSame($gmt_result, $result_gmt);
        } else {
            self::assertNotSame($placeholder, $result_nzt);
            self::assertNotSame($placeholder, $result_pst);
            self::assertNotSame($placeholder, $result_gmt);
        }
    }

    /**
     * Tests each of the placeholders and compares them to the expected results across
     * the different timezones.
     *
     * If $*_result is true, then we test the result doesn't equal the placeholder.
     *
     * @param string $placeholder
     * @param mixed $nzdt_result
     * @param mixed $pst_result
     * @param mixed $gmt_result
     * @param null|string $locale
     * @param int|null $timestamp
     * @return void
     * @dataProvider placeholders
     */
    public function test_placeholders_simple(
        string $placeholder,
        $nzdt_result,
        $pst_result,
        $gmt_result,
        ?string $locale = null,
        ?int $timestamp = null
    ): void {
        $timestamp ??= self::TIMESTAMP;

        // We can test locales strings if the locale itself is available
        if ($locale !== null) {
            try {
                $this->setLocale(LC_TIME, $locale);
            } catch (Exception $ex) {
                $this->markTestSkipped('Locale “' . $locale . '” was not available for testing');
            }
        }

        $this->setTimezone('Pacific/Auckland');
        $result = core_strftime::format($placeholder, $timestamp);
        if ($nzdt_result === true) {
            self::assertNotSame($placeholder, $result);
        } else {
            self::assertSame($nzdt_result, $result);
        }

        $this->setTimezone('America/Los_Angeles');
        $result = core_strftime::format($placeholder, $timestamp);
        if ($pst_result === true) {
            self::assertNotSame($placeholder, $result);
        } else {
            self::assertSame($pst_result, $result);
        }

        $result = core_strftime::format_gm($placeholder, $timestamp);
        if ($gmt_result === true) {
            self::assertNotEmpty($result);
        } else {
            self::assertSame($gmt_result, $result);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->has_pattern_generator = null;
        parent::tearDown();
    }

    /**
     * @return bool
     */
    private function has_pattern_generator(): bool {
        if ($this->has_pattern_generator === null) {
            $this->has_pattern_generator = class_exists('\IntlDatePatternGenerator', false);
        }
        return $this->has_pattern_generator;
    }
}

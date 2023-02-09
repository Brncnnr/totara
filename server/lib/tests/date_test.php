<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests core_date class.
 *
 * @package   core
 * @copyright 2015 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests core_date class.
 *
 * @package   core
 * @copyright 2015 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class core_date_testcase extends advanced_testcase {
    public function test_get_default_php_timezone() {

        $origtz = core_date::get_default_php_timezone();
        $this->assertNotEmpty($origtz);

        $this->setTimezone('Pacific/Auckland', 'Europe/Prague');
        $this->assertSame('Europe/Prague', core_date::get_default_php_timezone());

        $this->setTimezone('Pacific/Auckland', 'UTC');
        $this->assertSame('UTC', core_date::get_default_php_timezone());

        $this->setTimezone('Pacific/Auckland', 'GMT');
        $this->assertSame('GMT', core_date::get_default_php_timezone());
    }

    public function test_normalise_timezone() {

        $this->setTimezone('Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('Pacific/Auckland'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('99'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(99));
        $this->assertSame('GMT', core_date::normalise_timezone('GMT'));
        $this->assertSame('UTC', core_date::normalise_timezone('UTC'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('xxxxxxxx'));
        $this->assertSame('Europe/Berlin', core_date::normalise_timezone('Central European Time'));
        $this->assertSame('Etc/GMT', core_date::normalise_timezone('0'));
        $this->assertSame('Etc/GMT', core_date::normalise_timezone('0.0'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone(2));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('2.0'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('UTC+2'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('GMT+2'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(-13));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(-14));
        $this->assertSame('Etc/GMT-12', core_date::normalise_timezone(12));
        $this->assertSame('Etc/GMT-13', core_date::normalise_timezone(13));
        $this->assertSame('Etc/GMT-14', core_date::normalise_timezone(14));

        $this->assertSame('Asia/Kabul', core_date::normalise_timezone(4.5));
        $this->assertSame('Asia/Kolkata', core_date::normalise_timezone(5.5));
        $this->assertSame('Asia/Rangoon', core_date::normalise_timezone(6.5));
        $this->assertSame('Australia/Darwin', core_date::normalise_timezone('9.5'));

        $this->setTimezone('99', 'Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('Pacific/Auckland'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('99'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(99));
        $this->assertSame('GMT', core_date::normalise_timezone('GMT'));
        $this->assertSame('UTC', core_date::normalise_timezone('UTC'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone('xxxxxxxx'));
        $this->assertSame('Europe/Berlin', core_date::normalise_timezone('Central European Time'));
        $this->assertSame('Etc/GMT', core_date::normalise_timezone('0'));
        $this->assertSame('Etc/GMT', core_date::normalise_timezone('0.0'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone(2));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('2.0'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('UTC+2'));
        $this->assertSame('Etc/GMT-2', core_date::normalise_timezone('GMT+2'));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(-13));
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone(-14));
        $this->assertSame('Etc/GMT-12', core_date::normalise_timezone(12));
        $this->assertSame('Etc/GMT-13', core_date::normalise_timezone(13));
        $this->assertSame('Etc/GMT-14', core_date::normalise_timezone(14));

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');
        $tz = new DateTimeZone('Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::normalise_timezone($tz));

        // Totara: These timezones do not exist in PHP 8.1.14 and 8.2.1
        if (!$this->has_broken_timezones()) {
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone(-2));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('-2.0'));
            $this->assertSame('Etc/GMT+4', core_date::normalise_timezone(-4));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('UTC-2'));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('GMT-2'));
            $this->assertSame('Etc/GMT+12', core_date::normalise_timezone(-12));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone(-2));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('-2.0'));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('UTC-2'));
            $this->assertSame('Etc/GMT+2', core_date::normalise_timezone('GMT-2'));
            $this->assertSame('Etc/GMT+12', core_date::normalise_timezone(-12));
        } else {
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone(-2));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('-2.0'));
            $this->assertSame('America/La_Paz', core_date::normalise_timezone(-4));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('UTC-2'));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('GMT-2'));
            $this->assertSame('Pacific/Midway', core_date::normalise_timezone(-12));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone(-2));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('-2.0'));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('UTC-2'));
            $this->assertSame('Atlantic/South_Georgia', core_date::normalise_timezone('GMT-2'));
            $this->assertSame('Pacific/Midway', core_date::normalise_timezone(-12));
        }
    }

    public function test_windows_conversion() {
        $file = __DIR__ . '/fixtures/timezonewindows.xml';

        $contents = file_get_contents($file);
        preg_match_all('/<mapZone other="([^"]+)" territory="001" type="([^"]+)"\/>/', $contents, $matches, PREG_SET_ORDER);

        $this->assertCount(104, $matches); // NOTE: If the file contents change edit the core_date class and update this.

        foreach ($matches as $match) {
            $result = core_date::normalise_timezone($match[1]);
            if ($result == $match[2]) {
                $this->assertSame($match[2], $result);
            } else {

                // Totara: PHP 8.1.14 & 8.2.1 cannot process GMT+ timezones, skip this test
                if (str_starts_with($match[2], 'Etc/GMT+') && $this->has_broken_timezones()) {
                    continue;
                }

                $data = new DateTime('now', new DateTimeZone($match[2]));
                $expectedoffset = $data->getOffset();
                $data = new DateTime('now', new DateTimeZone($result));
                $resultoffset = $data->getOffset();
                $this->assertSame($expectedoffset, $resultoffset, "$match[1] is expected to be converted to $match[2] not $result");
            }
        }
    }

    /**
     * Sanity test for PHP stuff.
     */
    public function test_php_gmt_offsets() {

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');

        for ($i = -12; $i < 0; $i++) {
            // Totara: GMT-12 is not available on PHP 8.1.14+
            if ($i == -12 && $this->has_broken_timezones()) {
                continue;
            }
            $date = new DateTime('now', new DateTimeZone("Etc/GMT{$i}"));
            $this->assertSame(- $i * 60 * 60, $date->getOffset());
            $date = new DateTime('now', new DateTimeZone(core_date::normalise_timezone("GMT{$i}")));
            $this->assertSame($i * 60 * 60, $date->getOffset());
            $date = new DateTime('now', new DateTimeZone(core_date::normalise_timezone("UTC{$i}")));
            $this->assertSame($i * 60 * 60, $date->getOffset());
        }

        $date = new DateTime('now', new DateTimeZone('Etc/GMT'));
        $this->assertSame(0, $date->getOffset());

        for ($i = 1; $i <= 12; $i++) {
            if (!$this->has_broken_timezones()) {
                $date = new DateTime('now', new DateTimeZone("Etc/GMT+{$i}"));
                $this->assertSame(-$i * 60 * 60, $date->getOffset());
            }
            $date = new DateTime('now', new DateTimeZone(core_date::normalise_timezone("GMT+{$i}")));
            $this->assertSame($i * 60 * 60, $date->getOffset());
            $date = new DateTime('now', new DateTimeZone(core_date::normalise_timezone("UTC+{$i}")));
            $this->assertSame($i * 60 * 60, $date->getOffset());
        }
    }

    public function test_get_localised_timezone() {

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');

        $result = core_date::get_localised_timezone('Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', $result);

        $result = core_date::get_localised_timezone('99');
        $this->assertSame('Server timezone (Pacific/Auckland)', $result);

        $result = core_date::get_localised_timezone(99);
        $this->assertSame('Server timezone (Pacific/Auckland)', $result);

        $result = core_date::get_localised_timezone('Etc/GMT-1');
        $this->assertSame('UTC+1', $result);

        $result = core_date::get_localised_timezone('Etc/GMT+2');
        $this->assertSame('UTC-2', $result);

        $result = core_date::get_localised_timezone('GMT');
        $this->assertSame('UTC', $result);

        $result = core_date::get_localised_timezone('Etc/GMT');
        $this->assertSame('UTC', $result);
    }

    public function test_get_list_of_timezones() {

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');

        $phpzones = DateTimeZone::listIdentifiers();

        $zones = core_date::get_list_of_timezones();
        $this->assertSame(count($phpzones), count($zones));
        foreach ($zones as $zone => $zonename) {
            $this->assertSame($zone, $zonename); // The same in English!
            $this->assertContains($zone, $phpzones); // No extras expected.
        }

        $this->assertSame($zones, core_date::get_list_of_timezones(null, false));

        $nnzones = core_date::get_list_of_timezones(null, true);
        $last = $nnzones['99'];
        $this->assertSame('Server timezone (Pacific/Auckland)', $last);
        unset($nnzones['99']);
        $this->assertSame($zones, $nnzones);

        $nnzones = core_date::get_list_of_timezones('99', false);
        $last = $nnzones['99'];
        $this->assertSame('Server timezone (Pacific/Auckland)', $last);
        unset($nnzones['99']);
        $this->assertSame($zones, $nnzones);

        $xxzones = core_date::get_list_of_timezones('xx', false);
        $xx = $xxzones['xx'];
        $this->assertSame('Invalid timezone "xx"', $xx);
        unset($xxzones['xx']);
        $this->assertSame($zones, $xxzones);

        $xxzones = core_date::get_list_of_timezones('1', false);
        $xx = $xxzones['1'];
        $this->assertSame('Invalid timezone "UTC+1.0"', $xx);
        unset($xxzones['1']);
        $this->assertSame($zones, $xxzones);

        $xxzones = core_date::get_list_of_timezones('-1.5', false);
        $xx = $xxzones['-1.5'];
        $this->assertSame('Invalid timezone "UTC-1.5"', $xx);
        unset($xxzones['-1.5']);
        $this->assertSame($zones, $xxzones);

    }

    public function test_get_server_timezone() {
        global $CFG;

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::get_server_timezone());

        $this->setTimezone('Pacific/Auckland', 'Europe/Prague');
        $this->assertSame('Pacific/Auckland', core_date::get_server_timezone());

        $this->setTimezone('99', 'Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::get_server_timezone());

        $this->setTimezone(99, 'Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::get_server_timezone());

        $this->setTimezone('Pacific/Auckland', 'Pacific/Auckland');
        unset($CFG->timezone);
        $this->assertSame('Pacific/Auckland', core_date::get_server_timezone());

        // Admin should fix the settings.
        $this->setTimezone('xxx/zzzz', 'Europe/Prague');
        $this->assertSame('Europe/Prague', core_date::get_server_timezone());
    }

    public function test_get_server_timezone_object() {

        $zones = core_date::get_list_of_timezones();
        foreach ($zones as $zone) {
            $this->setTimezone($zone, 'Pacific/Auckland');
            $tz = core_date::get_server_timezone_object();
            $this->assertInstanceOf('DateTimeZone', $tz);
            $this->assertSame($zone, $tz->getName());
        }
    }

    public function test_set_default_server_timezone() {
        global $CFG;

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        unset($CFG->timezone);
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());

        $this->setTimezone('', 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());

        $this->setTimezone('99', 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());

        $this->setTimezone(99, 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Pacific/Auckland', date_default_timezone_get());

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $CFG->timezone = 'UTC';
        core_date::set_default_server_timezone();
        $this->assertSame('UTC', date_default_timezone_get());

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $CFG->timezone = 'Australia/Perth';
        core_date::set_default_server_timezone();
        $this->assertSame('Australia/Perth', date_default_timezone_get());

        $this->setTimezone('0', 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Etc/GMT', date_default_timezone_get());

        $this->setTimezone('1', 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Etc/GMT-1', date_default_timezone_get());

        $this->setTimezone(1, 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Etc/GMT-1', date_default_timezone_get());

        $this->setTimezone('1.0', 'Pacific/Auckland');
        date_default_timezone_set('UTC');
        core_date::set_default_server_timezone();
        $this->assertSame('Etc/GMT-1', date_default_timezone_get());
    }

    public function legacyUserTimezoneProvider() {
        return [
            ['', 'Australia/Perth'],            // Fallback on default timezone.
            ['-13.0', 'Australia/Perth'],       // Fallback on default timezone.
            ['-12.5', 'Etc/GMT+12'],
            ['-12.0', 'Etc/GMT+12'],
            ['-11.5', 'Etc/GMT+11'],
            ['-11.0', 'Etc/GMT+11'],
            ['-10.5', 'Etc/GMT+10'],
            ['-10.0', 'Etc/GMT+10'],
            ['-9.5', 'Etc/GMT+9'],
            ['-9.0', 'Etc/GMT+9'],
            ['-8.5', 'Etc/GMT+8'],
            ['-8.0', 'Etc/GMT+8'],
            ['-7.5', 'Etc/GMT+7'],
            ['-7.0', 'Etc/GMT+7'],
            ['-6.5', 'Etc/GMT+6'],
            ['-6.0', 'Etc/GMT+6'],
            ['-5.5', 'Etc/GMT+5'],
            ['-5.0', 'Etc/GMT+5'],
            ['-4.5', 'Etc/GMT+4'],
            ['-4.0', 'Etc/GMT+4'],
            ['-3.5', 'Etc/GMT+3'],
            ['-3.0', 'Etc/GMT+3'],
            ['-2.5', 'Etc/GMT+2'],
            ['-2.0', 'Etc/GMT+2'],
            ['-1.5', 'Etc/GMT+1'],
            ['-1.0', 'Etc/GMT+1'],
            ['-0.5', 'Etc/GMT'],
            ['0', 'Etc/GMT'],
            ['0.0', 'Etc/GMT'],
            ['0.5', 'Etc/GMT'],
            ['1.0', 'Etc/GMT-1'],
            ['1.5', 'Etc/GMT-1'],
            ['2.0', 'Etc/GMT-2'],
            ['2.5', 'Etc/GMT-2'],
            ['3.0', 'Etc/GMT-3'],
            ['3.5', 'Etc/GMT-3'],
            ['4.0', 'Etc/GMT-4'],
            ['4.5', 'Asia/Kabul'],
            ['5.0', 'Etc/GMT-5'],
            ['5.5', 'Asia/Kolkata'],
            ['6.0', 'Etc/GMT-6'],
            ['6.5', 'Asia/Rangoon'],
            ['7.0', 'Etc/GMT-7'],
            ['7.5', 'Etc/GMT-7'],
            ['8.0', 'Etc/GMT-8'],
            ['8.5', 'Etc/GMT-8'],
            ['9.0', 'Etc/GMT-9'],
            ['9.5', 'Australia/Darwin'],
            ['10.0', 'Etc/GMT-10'],
            ['10.5', 'Etc/GMT-10'],
            ['11.0', 'Etc/GMT-11'],
            ['11.5', 'Etc/GMT-11'],
            ['12.0', 'Etc/GMT-12'],
            ['12.5', 'Etc/GMT-12'],
            ['13.0', 'Etc/GMT-13'],
        ];
    }

    /**
     * @dataProvider legacyUserTimezoneProvider
     * @param string $tz The legacy timezone.
     * @param string $expected The expected converted timezone.
     */
    public function test_get_legacy_user_timezone($tz, $expected) {
        // Totara: Don't test the broken timezones, they're covered below
        if ($this->has_broken_timezones() && stristr($expected, '+') !== false) {
            $this->markTestSkipped();
        }
        $this->setTimezone('Australia/Perth', 'Australia/Perth');
        $this->assertEquals($expected, core_date::get_user_timezone($tz));
    }

    public function test_get_user_timezone() {
        global $CFG, $USER;

        // Null parameter.

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone(null));
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone());

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = 'Europe/Berlin';
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone(null));
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone());

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = 'xxx/yyy';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone(null));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone());

        $this->setTimezone('Europe/Prague', 'Pacific/Auckland');
        $USER->timezone = 'abc/def';
        $CFG->forcetimezone = '99';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone(null));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone());

        $this->setTimezone('xxx/yyy', 'Europe/London');
        $USER->timezone = 'abc/def';
        $CFG->forcetimezone = 'Europe/Berlin';
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone(null));
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone());

        $this->setTimezone('xxx/yyy', 'Europe/London');
        $USER->timezone = 'abc/def';
        $CFG->forcetimezone = 99;
        $this->assertSame('Europe/London', core_date::get_user_timezone(null));
        $this->assertSame('Europe/London', core_date::get_user_timezone());

        // User object parameter.
        $admin = get_admin();

        $this->setTimezone('Europe/London');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        $admin->timezone = 'Australia/Perth';
        $this->assertSame('Australia/Perth', core_date::get_user_timezone($admin));

        $this->setTimezone('Europe/Prague');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        $admin->timezone = '99';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone($admin));

        $this->setTimezone('99', 'Europe/London');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        $admin->timezone = '99';
        $this->assertSame('Europe/London', core_date::get_user_timezone($admin));

        $this->setTimezone('Europe/Prague');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        $admin->timezone = 'xx/zz';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone($admin));

        $this->setTimezone('Europe/Prague');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = '99';
        unset($admin->timezone);
        $this->assertSame('Europe/Prague', core_date::get_user_timezone($admin));

        $this->setTimezone('Europe/Prague');
        $USER->timezone = 'Pacific/Auckland';
        $CFG->forcetimezone = 'Europe/Berlin';
        $admin->timezone = 'Australia/Perth';
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone($admin));

        // Other scalar parameter.

        $this->setTimezone('Europe/Prague');
        $CFG->forcetimezone = '99';

        $USER->timezone = 'Pacific/Auckland';
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone('99'));
        $this->assertSame('Etc/GMT-1', core_date::get_user_timezone('1'));
        $expected = !$this->has_broken_timezones() ? 'Etc/GMT+1' : 'Atlantic/Cape_Verde';
        $this->assertSame($expected, core_date::get_user_timezone(-1));
        $this->assertSame('Europe/London', core_date::get_user_timezone('Europe/London'));
        $USER->timezone = '99';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('99'));
        $this->assertSame('Europe/London', core_date::get_user_timezone('Europe/London'));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('xxx/zzz'));
        $USER->timezone = 'xxz/zzz';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('99'));

        $this->setTimezone('99', 'Europe/Prague');
        $CFG->forcetimezone = '99';

        $USER->timezone = 'Pacific/Auckland';
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone('99'));
        $this->assertSame('Europe/London', core_date::get_user_timezone('Europe/London'));
        $USER->timezone = '99';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('99'));
        $this->assertSame('Europe/London', core_date::get_user_timezone('Europe/London'));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('xxx/zzz'));
        $USER->timezone = 99;
        $this->assertSame('Europe/London', core_date::get_user_timezone('Europe/London'));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('xxx/zzz'));
        $USER->timezone = 'xxz/zzz';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('99'));

        $this->setTimezone('xxx', 'Europe/Prague');
        $CFG->forcetimezone = '99';
        $USER->timezone = 'xxx';
        $this->assertSame('Europe/Prague', core_date::get_user_timezone('99'));
        $this->assertSame('Europe/Prague', core_date::get_user_timezone(99));
        $this->assertSame('Etc/GMT-1', core_date::get_user_timezone(1));

        $this->setTimezone('Europe/Prague');
        $CFG->forcetimezone = 'Pacific/Auckland';
        $USER->timezone = 'Europe/London';
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone(99));
        $this->assertSame('Europe/Berlin', core_date::get_user_timezone('Europe/Berlin'));

        // TZ object param.

        $this->setTimezone('UTC');
        $USER->timezone = 'Europe/London';
        $CFG->forcetimezone = 99;
        $tz = new DateTimeZone('Pacific/Auckland');
        $this->assertSame('Pacific/Auckland', core_date::get_user_timezone($tz));
    }

    public function test_get_user_timezone_object() {
        global $CFG, $USER;

        $this->setTimezone('Pacific/Auckland');
        $CFG->forcetimezone = '99';

        $zones = core_date::get_list_of_timezones();
        foreach ($zones as $zone) {
            $USER->timezone = $zone;
            $tz = core_date::get_user_timezone_object();
            $this->assertInstanceOf('DateTimeZone', $tz);
            $this->assertSame($zone, $tz->getName());
        }
    }

    /**
     * Data provider for the replace_broken_zones test.
     * @return array[]
     */
    public function test_broken_zones_dataprovider(): array {
        return [
            // Zone to test, Expected for broken, Expected otherwise
            ['Etc/GMT+1', 'Atlantic/Cape_Verde', 'Etc/GMT+1',],
            ['Etc/GMT+2', 'Atlantic/South_Georgia', 'Etc/GMT+2',],
            ['Etc/GMT+3', 'America/Argentina/Buenos_Aires', 'Etc/GMT+3',],
            ['Etc/GMT+4', 'America/La_Paz', 'Etc/GMT+4',],
            ['Etc/GMT+5', 'America/Guayaquil', 'Etc/GMT+5',],
            ['Etc/GMT+6', 'Pacific/Galapagos', 'Etc/GMT+6',],
            ['Etc/GMT+7', 'America/Phoenix', 'Etc/GMT+7',],
            ['Etc/GMT+8', 'Pacific/Pitcairn', 'Etc/GMT+8',],
            ['Etc/GMT+9', 'Pacific/Gambier', 'Etc/GMT+9',],
            ['Etc/GMT+10', 'Pacific/Tahiti', 'Etc/GMT+10',],
            ['Etc/GMT+11', 'Pacific/Midway', 'Etc/GMT+11',],
            ['Etc/GMT+12', 'Pacific/Midway', 'Etc/GMT+12',],
            ['-12.5', 'Pacific/Midway', 'Etc/GMT+12'],
            ['-12.0', 'Pacific/Midway', 'Etc/GMT+12'],
            ['-11.5', 'Pacific/Midway', 'Etc/GMT+11'],
            ['-11.0', 'Pacific/Midway', 'Etc/GMT+11'],
            ['-10.5', 'Pacific/Tahiti', 'Etc/GMT+10'],
            ['-10.0', 'Pacific/Tahiti', 'Etc/GMT+10'],
            ['-9.5', 'Pacific/Gambier', 'Etc/GMT+9'],
            ['-9.0', 'Pacific/Gambier', 'Etc/GMT+9'],
            ['-8.5', 'Pacific/Pitcairn', 'Etc/GMT+8'],
            ['-8.0', 'Pacific/Pitcairn', 'Etc/GMT+8'],
            ['-7.5', 'America/Phoenix', 'Etc/GMT+7'],
            ['-7.0', 'America/Phoenix', 'Etc/GMT+7'],
            ['-6.5', 'Pacific/Galapagos', 'Etc/GMT+6'],
            ['-6.0', 'Pacific/Galapagos', 'Etc/GMT+6'],
            ['-5.5', 'America/Guayaquil', 'Etc/GMT+5'],
            ['-5.0', 'America/Guayaquil', 'Etc/GMT+5'],
            ['-4.5', 'America/La_Paz', 'Etc/GMT+4'],
            ['-4.0', 'America/La_Paz', 'Etc/GMT+4'],
            ['-3.5', 'America/Argentina/Buenos_Aires', 'Etc/GMT+3'],
            ['-3.0', 'America/Argentina/Buenos_Aires', 'Etc/GMT+3'],
            ['-2.5', 'Atlantic/South_Georgia', 'Etc/GMT+2'],
            ['-2.0', 'Atlantic/South_Georgia', 'Etc/GMT+2'],
            ['-1.5', 'Atlantic/Cape_Verde', 'Etc/GMT+1'],
            ['-1.0', 'Atlantic/Cape_Verde', 'Etc/GMT+1'],
        ];
    }

    /**
     * Assert that the specified timezones are normalised back to the correct valid timezone.
     * This includes the replacement timezones when the original timezone is invalid.
     *
     * @param string $zone
     * @param string $broken_replacement
     * @param string $regular_replacement
     * @return void
     * @dataProvider test_broken_zones_dataprovider
     */
    public function test_replace_broken_zones(string $zone, string $broken_replacement, string $regular_replacement): void {
        // Make sure we see what we want to see
        $expected_zone = $this->has_broken_timezones() ? $broken_replacement : $regular_replacement;
        $result = core_date::normalise_timezone($zone);
        $this->assertSame($expected_zone, $result);
    }

    /**
     * Data provider for replacement_offsets tests.
     *
     * @return array
     */
    public function replacement_offsets_dataprovider(): array {
        return [
            // Timezone, unix timestamp, Expected result, Alternative Result
            ['Etc/GMT', 1423953022, '2015-02-14T22:30:22+00:00'], // GMT Default, Sat Feb 14 2015 22:30:22 GMT+0000
            ['Etc/GMT+1', 1423953022, '2015-02-14T21:30:22-01:00'],
            ['Etc/GMT+2', 1423953022, '2015-02-14T20:30:22-02:00'],
            ['Etc/GMT+3', 1423953022, '2015-02-14T19:30:22-03:00'],
            ['Etc/GMT+4', 1423953022, '2015-02-14T18:30:22-04:00'],
            ['Etc/GMT+5', 1423953022, '2015-02-14T17:30:22-05:00'],
            ['Etc/GMT+6', 1423953022, '2015-02-14T16:30:22-06:00'],
            ['Etc/GMT+7', 1423953022, '2015-02-14T15:30:22-07:00'],
            ['Etc/GMT+8', 1423953022, '2015-02-14T14:30:22-08:00'],
            ['Etc/GMT+9', 1423953022, '2015-02-14T13:30:22-09:00'],
            ['Etc/GMT+10', 1423953022, '2015-02-14T12:30:22-10:00'],
            ['Etc/GMT+11', 1423953022, '2015-02-14T11:30:22-11:00'],
            ['Etc/GMT+12', 1423953022, '2015-02-14T10:30:22-12:00', '2015-02-14T11:30:22-11:00'], // GMT-12 falls back to -11 for PHP 8.1.14 & 8.2.1

            // Using a different time of year to catch any DST shifts
            ['Etc/GMT', 1439826395, '2015-08-17T15:46:35+00:00'], // GMT Default, Mon Aug 17 2015 15:46:35 GMT+0000
            ['Etc/GMT+1', 1439826395, '2015-08-17T14:46:35-01:00'],
            ['Etc/GMT+2', 1439826395, '2015-08-17T13:46:35-02:00'],
            ['Etc/GMT+3', 1439826395, '2015-08-17T12:46:35-03:00'],
            ['Etc/GMT+4', 1439826395, '2015-08-17T11:46:35-04:00'],
            ['Etc/GMT+5', 1439826395, '2015-08-17T10:46:35-05:00'],
            ['Etc/GMT+6', 1439826395, '2015-08-17T09:46:35-06:00'],
            ['Etc/GMT+7', 1439826395, '2015-08-17T08:46:35-07:00'],
            ['Etc/GMT+8', 1439826395, '2015-08-17T07:46:35-08:00'],
            ['Etc/GMT+9', 1439826395, '2015-08-17T06:46:35-09:00'],
            ['Etc/GMT+10', 1439826395, '2015-08-17T05:46:35-10:00'],
            ['Etc/GMT+11', 1439826395, '2015-08-17T04:46:35-11:00'],
            ['Etc/GMT+12', 1439826395, '2015-08-17T03:46:35-12:00', '2015-08-17T04:46:35-11:00'], // GMT-12 falls back to -11 for PHP 8.1.14 & 8.2.1
        ];
    }

    /**
     * Assert that the chosen replacements for the invalid timezones resolve to expected dates/times.
     *
     * @param string $timezone The timezone to test against
     * @param int $timestamp The timestamp for the test
     * @param string $expected What the timestamp should translate to.
     * @param string|null $alternate_expected If set, this is what the timestamp should translate to if has_broken_timezones() is true.
     * @return void
     * @dataProvider replacement_offsets_dataprovider
     */
    public function test_replacement_offsets(string $timezone, int $timestamp, string $expected, ?string $alternate_expected = null): void {
        $this->setTimezone('UTC');
        $timezone = core_date::normalise_timezone($timezone);
        $date = new DateTime('@' . $timestamp);
        $tz = new DateTimeZone($timezone);
        $date->setTimeZone($tz);
        $result = $date->format('c');

        if ($alternate_expected && $this->has_broken_timezones()) {
            $expected = $alternate_expected;
        }
        $this->assertSame($expected, $result);
    }

    /**
     * Returns true if the current PHP version includes the broken Etc/GMT+?? timezones.
     *
     * @see https://github.com/php/php-src/issues/10218
     * @return bool
     */
    private function has_broken_timezones(): bool {
        return version_compare(phpversion(), '8.1.14', '==') || version_compare(phpversion(), '8.2.1', '==');
    }
}

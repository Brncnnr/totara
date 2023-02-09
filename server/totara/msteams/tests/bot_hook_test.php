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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use totara_msteams\botfw\exception\unexpected_exception;
use totara_msteams\my\bot_hook;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/fixtures/lib.php');

class totara_msteams_bot_hook_testcase extends advanced_testcase {
    /** @var integer */
    private $userid;

    /** @var bot_hook */
    private $hook;

    /** @var ReflectionProperty */
    private $rp;

    public function setUp(): void {
        $this->userid = $this->getDataGenerator()->create_user()->id;
        $this->hook = new bot_hook();
        $this->rp = new ReflectionProperty($this->hook, 'forcelang');
        $this->rp->setAccessible(true);
    }

    public function tearDown(): void {
        $this->userid = null;
        $this->hook = null;
        $this->rp = null;
    }

    public function data_open(): array {
        return [
            // Passing an empty string sets the session language to an empty string.
            ['', 'xo_ox', ''],
            // Passing a non-existent language does not update the session language.
            ['xy_yx', 'xo_ox', 'xo_ox'],
            // Passing a valid language *does* update the session language.
            ['en', 'xo_ox', 'en'],
        ];
    }

    public function data_close(): array {
        return [
            [''],
            ['xy_yx'],
            ['en'],
        ];
    }

    /**
     * @param string $lang
     * @param string $forcelangprop
     * @param string $forcelangsess
     * @dataProvider data_open
     */
    public function test_open(string $lang, string $forcelangprop, string $forcelangsess) {
        global $SESSION;
        $SESSION->forcelang = 'xo_ox';
        $this->hook->open($lang);
        $this->assertSame($forcelangprop, $this->rp->getValue($this->hook));
        $this->assertSame($forcelangsess, $SESSION->forcelang);
    }

    /**
     * @param string $lang
     * @param string $forcelangprop
     * @param string $forcelangsess
     * @dataProvider data_close
     */
    public function test_close(string $lang) {
        global $SESSION;
        $SESSION->forcelang = '';
        $this->hook->open($lang);
        $this->hook->close();
        $this->assertSame('', $SESSION->forcelang);

        $SESSION->forcelang = 'en';
        $this->hook->open($lang);
        $this->hook->close();
        $this->assertSame('en', $SESSION->forcelang);
    }

    public function test_set_user() {
        global $DB, $CFG, $USER;
        /** @var moodle_database $DB */

        $this->setGuestUser();
        $this->hook->set_user($this->userid);
        $this->assertEquals($this->userid, $USER->id);

        $CFG->maintenance_enabled = 1;
        try {
            $this->hook->set_user($this->userid);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }
        $CFG->maintenance_enabled = 0;

        try {
            $DB->set_fields('user', ['deleted' => 0, 'suspended' => 1], ['id' => $this->userid]);
            $this->hook->set_user($this->userid);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        try {
            $DB->set_fields('user', ['deleted' => 1, 'suspended' => 0], ['id' => $this->userid]);
            $this->hook->set_user($this->userid);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        try {
            $DB->set_fields('user', ['deleted' => 0, 'suspended' => 0, 'confirmed' => 0], ['id' => $this->userid]);
            $this->hook->set_user($this->userid);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        try {
            $this->hook->set_user(0);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }

        try {
            $this->hook->set_user(1);
            $this->fail('unexpected_exception expected');
        } catch (unexpected_exception $ex) {
        }
    }
}

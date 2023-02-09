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

use totara_msteams\botfw\resolver\resolver;
use totara_msteams\botfw\resolver\v3_resolver;

class totara_msteams_botfw_resolver_v3_testcase extends advanced_testcase {
    /** @var resolver */
    private $resolver;

    public function setUp(): void {
        $this->resolver = new v3_resolver();
    }

    public function tearDown(): void {
        $this->resolver = null;
    }

    public function test_start_converstaion_url() {
        $url = $this->resolver->start_converstaion_url('https://bot.example.com/api');
        $this->assertEquals('https://bot.example.com/api/v3/conversations', $url);
        $url = $this->resolver->start_converstaion_url('https://bot.example.com/api/');
        $this->assertEquals('https://bot.example.com/api/v3/conversations', $url);
    }

    public function test_conversation_url_success() {
        $url = $this->resolver->conversation_url('https://bot.example.com/api', 'a:KiA0Ra-_-K0uToU', 'members', null);
        $this->assertEquals('https://bot.example.com/api/v3/conversations/a%3AKiA0Ra-_-K0uToU/members', $url);
        $url = $this->resolver->conversation_url('https://bot.example.com/api', 'a:KiA0Ra-_-K0uToU', 'members', '29:L0reM_iPSum');
        $this->assertEquals('https://bot.example.com/api/v3/conversations/a%3AKiA0Ra-_-K0uToU/members/29%3AL0reM_iPSum', $url);
    }

    public function test_conversation_url_failure() {
        $this->expectException(\coding_exception::class);
        $this->resolver->conversation_url('https://bot.example.com/api', 'a:KiA0Ra-_-K0uToU', 'Q&A', null);
    }
}

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

use totara_msteams\botfw\entity\user_state;
use totara_msteams\botfw\mini_output;

class totara_msteams_botfw_mini_output_testcase extends advanced_testcase {
    /** @var mini_output */
    private $renderer;

    public function setUp(): void {
        global $PAGE;
        $PAGE->set_context(context_system::instance());
        $this->renderer = new mini_output($PAGE);
    }

    public function tearDown(): void {
        $this->renderer = null;
    }

    public function test_constructor() {
        global $PAGE;
        $rp = new ReflectionProperty($this->renderer, 'title');
        $rp->setAccessible(true);
        $this->assertEquals(get_string('botfw:output_title', 'totara_msteams'), $rp->getValue($this->renderer));
        $renderer = new mini_output($PAGE, 'custom title');
        $this->assertEquals('custom title', $rp->getValue($renderer));
    }

    public function test_header() {
        $html = $this->renderer->header();
        $this->assertSame(0, strpos($html, '<!DOCTYPE'));

        try {
            $this->renderer->header();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    public function test_footer() {
        try {
            $this->renderer->footer();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }

        $this->renderer->header();
        $html = $this->renderer->footer();
        $this->assertStringContainsString('</html>', $html);

        try {
            $this->renderer->footer();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }
    }

    public function test_render_sso_login() {
        $url = '/totara/msteams/kia/ora/koutou.php';
        try {
            $this->renderer->render_sso_login(new moodle_url($url), false, false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }

        $this->renderer->header();
        $html = $this->renderer->render_sso_login(new moodle_url($url), false, false);
        $this->assertStringContainsString('"returnUrl":"'.$url.'"', $html);
    }

    public function test_render_post_process() {
        $code = 'k1a-0Ra-KoUtOu_kIA-KaHA';
        $state = new user_state();
        $state->verify_code = $code;

        try {
            $this->renderer->render_post_process($state);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }

        $this->renderer->header();
        $html = $this->renderer->render_post_process($state);
        $this->assertStringContainsString('"code":"'.$code.'"', $html);
    }

    public function test_render_redirector() {
        global $CFG;
        $url = '/totara/msteams/kia/kaha.php';
        try {
            $this->renderer->render_redirector(new moodle_url($url), false, false);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
        }

        $this->renderer->header();
        $html = $this->renderer->render_redirector(new moodle_url($url), false, false);
        $this->assertStringContainsString('"wwwroot":"'.$CFG->wwwroot.'"', $html);
        $this->assertStringContainsString('"redirectUrl":"'.$CFG->wwwroot.$url.'"', $html);
    }
}

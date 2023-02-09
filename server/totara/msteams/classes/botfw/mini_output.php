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

namespace totara_msteams\botfw;

use coding_exception;
use core_useragent;
use html_writer;
use moodle_page;
use moodle_url;
use renderer_base;
use totara_msteams\botfw\entity\user_state;
use totara_msteams\output\banner;
use totara_msteams\output\spinner;

/**
 * A tiny renderer to output minimum HTML.
 * NOTE: This class heavily depends on theme_msteams.
 */
class mini_output extends renderer_base {
    private const INIT = 0;
    private const BODY = 1;
    private const DONE = 2;
    private const AUTH_JS_CACHE_KEY = 'js_totara_msteams_auth';

    /** @var integer */
    private $stage = self::INIT;

    /** @var string */
    private $title;

    /**
     * Constructor.
     *
     * @param moodle_page $page
     * @param string $title
     */
    public function __construct(moodle_page $page, string $title = '') {
        parent::__construct($page, '');
        $this->title = $title ?: get_string('botfw:output_title', 'totara_msteams');
    }

    /**
     * Return <script> containing auth.js.
     *
     * @return string
     */
    private static function include_auth_js(): string {
        // Borrow the cache in theme_msteams.
        return \theme_msteams\loader::load_script_internal(self::AUTH_JS_CACHE_KEY, '/totara/msteams/js/auth.js');
    }

    /**
     * Render the HTML header.
     *
     * @return string
     */
    public function header(): string {
        if ($this->stage > self::INIT) {
            throw new coding_exception('The page header has already been rendered.');
        }

        // Minimum HTML header.
        $html = "<!DOCTYPE html>".
                "<html>".
                "<head>".
                "<meta charset=\"utf-8\">".
                "<title>{$this->title}</title>".
                // Add theme CSS.
                \theme_msteams\output\core_renderer::include_theme_css().
                "</head>".
                "<body>".
                // Add Microsoft Teams SDK. (What if theme_msteams does not exist?)
                \theme_msteams\output\core_renderer::include_msteams_sdk().
                \theme_msteams\output\core_renderer::include_helper_js().
                '';

        // Add IE11 polyfills except fetch().
        if (core_useragent::is_ie()) {
            $html .= html_writer::script('', $this->page->requires->get_js_url('/lib/javascript_polyfill/build/es6-promise.auto.min.js'));
            $html .= html_writer::script('', $this->page->requires->get_js_url('/lib/javascript_polyfill/build/other_ie11.min.js'));
        }

        $this->stage = self::BODY;
        return $html;
    }

    /**
     * Render the content to perform single sign-on.
     *
     * @param moodle_url $returnurl
     * @param boolean $popup
     * @param boolean $debug
     * @return string
     */
    public function render_sso_login(moodle_url $returnurl, bool $popup, bool $debug = false): string {
        if ($this->stage !== self::BODY) {
            throw new coding_exception('Cannot render the body content at this stage.');
        }

        // Now that add ours.
        $html = self::include_auth_js();

        // And the spinner.
        $html .= $this->render(spinner::create_signingin(true, 'sso-spinner'));

        // And the banner.
        $html .= $this->render(banner::create(
            'sso-banner',
            get_string('error:sso_failure_title', 'totara_msteams'),
            get_string('error:sso_failure_desc', 'totara_msteams'),
            true));

        $jsconfig = [
            'oidcLoginUrl' => (new moodle_url('/totara/msteams/oidc_login.php'))->out(false),
            'redirectUrl' => (new moodle_url('/totara/msteams/index.php'))->out(false),
            'returnUrl' => $returnurl->out_as_local_url(false), // this must be relative
            'sesskey' => sesskey(),
            'popup' => $popup,
            'debug' => $debug,
        ];

        $html .= html_writer::script('totara_msteams_auth.sso_login('.json_encode($jsconfig, JSON_UNESCAPED_SLASHES).')');

        return $html;
    }

    /**
     * Render the content to proceed sign-in workflow.
     *
     * @param user_state $userstate
     * @return string
     */
    public function render_post_process(user_state $userstate): string {
        if ($this->stage !== self::BODY) {
            throw new coding_exception('Cannot render the body content at this stage.');
        }

        // Now that add ours.
        $html = self::include_auth_js();

        // And the spinner.
        $html .= $this->render(spinner::create_signingin());

        $jsconfig = [
            'code' => $userstate->verify_code,
        ];

        $html .= html_writer::script('totara_msteams_auth.complete_login('.json_encode($jsconfig, JSON_UNESCAPED_SLASHES).')');

        return $html;
    }

    /**
     * Render the content to redirect a deep link if provided.
     *
     * @param moodle_url $returnurl
     * @return string
     */
    public function render_redirector(moodle_url $returnurl): string {
        global $CFG;
        if ($this->stage !== self::BODY) {
            throw new coding_exception('Cannot render the body content at this stage.');
        }

        // Now that add ours.
        $html = self::include_auth_js();

        // And the spinner.
        $html .= $this->render(spinner::create_loading());

        $jsconfig = [
            'wwwroot' => $CFG->wwwroot,
            'redirectUrl' => $returnurl->out(false),
        ];

        $html .= html_writer::script('totara_msteams_auth.redirect_deeplink('.json_encode($jsconfig, JSON_UNESCAPED_SLASHES).')');

        return $html;
    }

    /**
     * Render the HTML footer.
     *
     * @return string
     */
    public function footer(): string {
        if ($this->stage !== self::BODY) {
            throw new coding_exception('Cannot render the page footer at this stage.');
        }
        $this->stage = self::DONE;
        return "</body></html>";
    }
}

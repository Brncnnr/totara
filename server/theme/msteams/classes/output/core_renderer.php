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
 * @package theme_msteams
 */

namespace theme_msteams\output;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_user;
use html_writer;
use moodle_url;
use single_button;
use theme_msteams\hook\get_page_navigation_hook;
use theme_msteams\loader;
use theme_msteams\session;
use totara_tui\output\component;

/**
 * Override core_renderer.
 */
class core_renderer extends \core_renderer {
    /**
     * The URL to the Microsoft Teams SDK.
     */
    const MSTEAMS_SDK_URL = 'https://statics.teams.cdn.office.net/sdk/v1.8.0/js/MicrosoftTeams.min.js';
    const PARENT_THEME = 'ventura';

    private const PARENT_CSS_CACHE_KEY = 'theme_custom_css';
    private const THEME_CSS_CACHE_KEY = 'css_theme_custom';
    private const MSSTUB_JS_CACHE_KEY = 'js_theme_msteams_stub';
    private const HELPER_JS_CACHE_KEY = 'js_theme_teams';
    private const IFRAME_JS_CACHE_KEY = 'js_theme_iframe';

    /**
     * @inheritDoc
     */
    public function standard_head_html() {
        $out = parent::standard_head_html();
        $out .= static::load_parent_categories_css($this->page);
        // Inject JavaScript code into the <head> element.
        $payload = self::include_iframe_js();
        return $out.$payload;
    }

    /**
     * Return <script> pointing to the Microsoft Teams SDK.
     *
     * @return string
     */
    public static function include_msteams_sdk(): string {
        // Do not load the real SDK on behat tests.
        if (defined('BEHAT_UTIL') || defined('BEHAT_TEST') || defined('BEHAT_SITE_RUNNING')) {
            return loader::load_script_internal(self::MSSTUB_JS_CACHE_KEY, '/theme/msteams/script/sdk_stub.js');
        }
        return html_writer::script('', (new moodle_url(self::MSTEAMS_SDK_URL))->out(false));
    }

    /**
     * Return <script> containing the minified helper JavaScript code.
     *
     * @return string
     */
    public static function include_helper_js(): string {
        return loader::load_script_internal(self::HELPER_JS_CACHE_KEY, '/theme/msteams/script/teams.js');
    }

    /**
     * Return <script> containing the minified iframe checker JavaScript code.
     *
     * @return string
     */
    public static function include_iframe_js(): string {
        return loader::load_script_internal(self::IFRAME_JS_CACHE_KEY, '/theme/msteams/script/iframe.js');
    }

    /**
     * Return <style> containing minified theme CSS code.
     *
     * @return string
     */
    public static function include_theme_css(): string {
        // Always serve the LTR version. The layout of the minimal HTML should be language neutral.
        return loader::load_css_internal(self::THEME_CSS_CACHE_KEY, '/theme/msteams/style/custom.css');
    }

    /**
     * Return string containing customised CSS code from parent theme.
     *
     * @param $page
     * @return string
     */
    private static function load_parent_categories_css($page): string {
        return loader::load_parent_css(self::PARENT_CSS_CACHE_KEY, $page);
    }

    /**
     * Load a script and return its minified version.
     *
     * @param string $key cache key
     * @param string $relpath relative path from $CFG->dirroot, starting with '/'
     * @return string <script> containing minified code
     * @internal Do not use this function
     * @deprecated since Totara 13.2
     */
    public static function load_script_internal(string $key, string $relpath): string {
        debugging(__METHOD__ . '() is deprecated, and is not to be consumed by external code in the first place.', DEBUG_DEVELOPER);
        return loader::load_script_internal($key, $relpath);
    }

    /**
     * Load a css and return its minified version.
     *
     * @param string $key cache key
     * @param string $relpath relative path from $CFG->dirroot, starting with '/'
     * @return string <style> containing minified css
     * @internal Do not use this function
     * @deprecated since Totara 13.2
     */
    public static function load_css_internal(string $key, string $relpath): string {
        debugging(__METHOD__ . '() is deprecated, and is not to be consumed by external code in the first place.', DEBUG_DEVELOPER);
        return loader::load_css_internal($key, $relpath);
    }

    /**
     * @inheritDoc
     */
    public function standard_top_of_body_html() {
        global $USER;

        $out = parent::standard_top_of_body_html();

        $out .= self::include_msteams_sdk();
        $out .= self::include_helper_js();

        $data = session::get_tab_data();
        $hook = new get_page_navigation_hook($this->page->context ?? context_system::instance(), $this->page->url, $data);
        $hook->execute();

        $nav = '';
        $alert = '';
        $template_data = [];
        if ($hook->navigation !== false) {
            // Convert array to template data.
            $links = array_map(function ($obj) {
                $obj = (object)$obj;
                $link = [
                    'href' => (new moodle_url($obj->href))->out(false),
                    'text' => $obj->text ?? get_string('goback', 'theme_msteams'),
                    'icon' => [],
                    'attributes' => []
                ];
                if (!empty($obj->icon)) {
                    $link['icon'] = [
                        'template' => $obj->icon->get_template(),
                        'context' => $obj->icon->export_for_template($this),
                    ];
                }
                if (!empty($obj->attributes)) {
                    foreach ($obj->attributes as $name => $value) {
                        $link['attributes'][] = ['name' => $name, 'value' => $value];
                    }
                }
                return $link;
            }, $hook->navigation);

            // Add 'open in new window' link.
            $icon = new \core\output\flex_icon('theme_msteams|open-externally');
            $links[] = [
                'href' => $this->page->url->out(false),
                'text' => get_string('openexternally', 'theme_msteams'),
                'icon' => [
                    'template' => $icon->get_template(),
                    'context' => $icon->export_for_template($this),
                ],
                'attributes' => [
                    [
                        'name' => 'rel',
                        'value' => 'noopener noreferrer'
                    ],
                    [
                        'name' => 'target',
                        'value' => '_blank'
                    ]
                ],
                'marginauto' => $hook->has_sign_out ? true : false,
            ];

            $template_data = ['links' => $links];
        }

        if ($hook->has_sign_out && !empty($USER->id)) {
            $template_data['logout'] = [
                'logouttitle' => get_string('loggedinasuser', 'theme_msteams', fullname($USER)),
                'logouttext' => get_string('botfw:msg_signout_button', 'totara_msteams'),
                'logouthref' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey(), 'redirecturl' => (new moodle_url($data['url']))->out(false)]))->out(false)
            ];

            // Add 'open in new window' link, if it's custom tab.
            if ($hook->is_custom_tab()) {
                if ($hook->get_tab_url()->compare($hook->get_page_url(), URL_MATCH_BASE)) {
                    $icon = new \core\output\flex_icon('theme_msteams|open-externally');
                    $links = [
                        'href' => $this->page->url->out(false),
                        'text' => get_string('openexternally', 'theme_msteams'),
                        'icon' => [
                            'template' => $icon->get_template(),
                            'context' => $icon->export_for_template($this),
                        ],
                        'attributes' => [
                            [
                                'name' => 'rel',
                                'value' => 'noopener noreferrer'
                            ],
                            [
                                'name' => 'target',
                                'value' => '_blank'
                            ]
                        ],
                        'marginauto' => true
                    ];
                    $template_data['links'] = $links;
                }
            }
        }

        // Render navigation if template data is not empty.
        if (!empty($template_data)) {
            $nav = $this->render(new navigation($template_data));
        }

        if ((string)$hook->alert !== '') {
            $alert = html_writer::div($hook->alert, 'totara_msteams__alert');
        }

        $tui = new component('totara_msteams/components/modal/ExternalUrlModal');
        return $out.$nav.$alert.$tui->out_html();
    }

    /**
     * @inheritDoc
     */
    public function continue_button($url) {
        if (!($url instanceof moodle_url)) {
            $url = new moodle_url($url);
        }

        $button = new single_button($url, get_string('continue'), 'get', true);
        $button->class = 'continuebutton';

        // Attach a custom CSS class to the continue button when you can't enrol in a course pinned to a teams channel.
        if ($url->compare(new moodle_url('/index.php'))) {
            $button->class .= ' theme_msteams--continue--back-to-index';
        }

        return $this->render($button);
    }
}

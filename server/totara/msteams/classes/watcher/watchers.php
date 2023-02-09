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

namespace totara_msteams\watcher;

use context_module;
use core\orm\query\builder;
use core\output\flex_icon;
use engage_article\totara_engage\resource\article;
use stdClass;
use theme_msteams\hook\get_page_navigation_hook;
use totara_playlist\totara_engage\link\nav_helper;

/**
 * Watchers.
 */
final class watchers {
    /** Back to the landing page */
    private const REWIND = 0;
    /** Hide page navigation */
    private const HIDDEN = 1;
    /** Back to course */
    private const BACK = 2;

    /** @var array */
    private const LANDING_PAGE_URL_LIST = [
        // Any page in the totara/msteams/tabs directory.
        '/totara/msteams/tabs/' => self::HIDDEN,
        // Login page.
        '/login/' => self::HIDDEN,
        // Find learning pages.
        '/totara/catalog/index.php' => self::HIDDEN,
        // Library page.
        '/totara/engage/your_resources.php' => self::HIDDEN,
        // Course page.
        '/course/view.php' => self::REWIND,
        // Enrol page.
        '/enrol/index.php' => self::REWIND,
        // Engage resource page.
        '/totara/engage/resources/article/index.php' => 'on_engage_resource',
        // Any activity page.
        '/mod/' => self::BACK,
    ];

    /** @var array */
    private const LANDING_PAGE_URL_LIST_CUSTOM = [
        // Enrol page.
        '/enrol/index.php' => self::HIDDEN,
        // Seminar resource detail pages.
        '/mod/facetoface/reports/' => self::REWIND,
    ];

    /** @var string[] */
    private const MODULES_BREAKAWAY = [
        'url',
        'lti',
        'wiki',
    ];

    /**
     * @var array
     */
    private const SIGN_OUT_BLACKLIST = [
        '/login/index.php',
        '/totara/msteams/tabs/config.php',
        '/totara/msteams/tabs/help.php'
    ];

    /**
     * Fill in the page navigation content.
     *
     * @param get_page_navigation_hook $hook
     * @param integer|string $action
     */
    private static function create_navigation(get_page_navigation_hook $hook, $action): void {
        if (is_string($action)) {
            self::{$action}($hook);
            return;
        }
        if ($action === self::HIDDEN) {
            return;
        }
        if ($action === self::REWIND && $hook->get_tab_url() !== null) {
            $url = $hook->get_tab_url();
            $url->param('rewind', 1);
            $hook->navigation = array_merge($hook->navigation ?: [], [[
                'href' => $url,
                'text' => $hook->get_tab_name(),
                'icon' => new flex_icon('theme_msteams|navigation-back')
            ]]);
            return;
        }
        // Back to course if the current page is an activity.
        $context = $hook->get_context();
        if ($context instanceof context_module) {
            $coursecontext = $context->get_course_context(false);
            $cm = builder::table('course_modules', 'cm')->join(['modules', 'm'], 'module', 'id')->where('cm.id', $context->instanceid)->select_raw('cm.instance, m.name AS modname')->order_by('cm.id')->first();
            /** @var stdClass|null $cm */
            if ($coursecontext) {
                $hook->navigation = array_merge($hook->navigation ?: [], [[
                    'href' => $coursecontext->get_url(),
                    'text' => $coursecontext->get_context_name(false, false),
                    'icon' => new flex_icon('theme_msteams|navigation-back')
                ]]);
            }
            if (!empty($cm) && (in_array($cm->modname, self::MODULES_BREAKAWAY) || self::is_incompatible_scorm($cm))) {
                if (empty($hook->alert)) {
                    $hook->alert = get_string('alert:opennew', 'totara_msteams');
                }
            }
        }
    }

    /**
     * Return true if the current module is a scorm with any 'new window' setting.
     *
     * @param stdClass $cm
     * @return boolean
     */
    private static function is_incompatible_scorm(stdClass $cm): bool {
        if ($cm->modname != 'scorm') {
            return false;
        }
        $scorm = builder::table('scorm')->where('id', $cm->instance)->select('popup')->one();
        /** @var stdClass|null $scorm */
        if (!$scorm) {
            return false;
        }
        // All 'new window' options are not very compatible at the moment.
        return !empty($scorm->popup);
    }

    /**
     * Look after the article page.
     *
     * @param get_page_navigation_hook $hook
     */
    private static function on_engage_resource(get_page_navigation_hook $hook): void {
        // Hack for extracting parameters on the engage article page.
        // Any parameter change to totara/engage/resources/article/index.php must apply here as well.
        $id = required_param('id', PARAM_INT);
        $source = optional_param('source', null, PARAM_TEXT);
        $resource = article::from_resource_id($id);
        [$back_button,] = nav_helper::build_resource_nav_buttons($resource->get_id(), $resource->get_userid(), $source);
        $hook->navigation = array_merge($hook->navigation ?: [], [[
            'href' => $back_button['url'],
            'text' => $back_button['label'],
            'icon' => new flex_icon('theme_msteams|navigation-back')
        ]]);
    }

    /**
     * Look up a known URL.
     *
     * @param string $localurl
     * @param integer|null $defaultaction
     * @param array $urllist
     * @return integer|string|null
     */
    private static function look_up_local_url(string $localurl, ?int $defaultaction, array $urllist) {
        foreach ($urllist as $url => $action) {
            if (strpos($localurl, $url) === 0) {
                return $action;
            }
        }
        return $defaultaction;
    }

    /**
     * Watch the get_page_navigation_hook.
     *
     * @param get_page_navigation_hook $hook
     */
    public static function watch_page_navigation_hook(get_page_navigation_hook $hook): void {
        $localurl = $hook->get_page_url()->out_as_local_url(false);
        $desired_action = null;
        if ($hook->is_custom_tab()) {
            if ($hook->get_tab_url()->compare($hook->get_page_url(), URL_MATCH_BASE)) {
                // This is the landing page of a configurable tab.
                $desired_action = self::HIDDEN;
            }
            // Check the context specific to a custom tab.
            $desired_action = self::look_up_local_url($localurl, $desired_action, self::LANDING_PAGE_URL_LIST_CUSTOM);
        }
        if ($desired_action === null) {
            // The default is rewind.
            $desired_action = self::look_up_local_url($localurl, self::REWIND, self::LANDING_PAGE_URL_LIST);
        }

        if (!in_array($localurl,self::SIGN_OUT_BLACKLIST)) {
            $hook->has_sign_out = true;
        }

        if (!empty(get_config('totara_msteams', 'oauth2_issuer'))) {
            $hook->has_sign_out = false;
        }

        self::create_navigation($hook, $desired_action);
    }
}

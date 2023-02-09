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

namespace totara_msteams;

defined('MOODLE_INTERNAL') || die;

use coding_exception;
use context_system;
use context_tenant;
use context_user;
use core\orm\query\builder;
use core\orm\query\order;
use core_plugin_manager;
use core_user;
use html_writer;
use moodle_url;
use stdClass;
use theme_msteams\session;
use totara_core\advanced_feature;
use totara_core\util\base64url;
use totara_msteams\botfw\mini_output;
use totara_msteams\output\input;
use totara_msteams\output\listbox;
use totara_msteams\output\spinner;

/**
 * Helper functions for inline pages inside the MS Teams app.
 */
final class page_helper {
    /**
     * Generate the html for the config page.
     * This function modifies $USER and $SESSION, and **never returns**.
     *
     * @codeCoverageIgnore
     */
    public static function config_page(): void {
        global $PAGE, $USER, $OUTPUT, $DB, $SESSION;
        /** @var \moodle_page $PAGE */
        /** @var \core_renderer $OUTPUT */
        /** @var \moodle_database $DB */

        \totara_core\advanced_feature::require('totara_msteams');

        $configurl = new moodle_url('/totara/msteams/tabs/config.php');

        $debug = optional_param('debug', false, PARAM_BOOL);

        // Turn off editing.
        $USER->editing = false;

        if (empty($USER->id)) {
            self::require_sso_login($configurl, $debug);
            return; // Never reached.
        }

        $SESSION->theme = 'msteams';

        // No navigation and block.
        $PAGE->set_url($configurl);
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('popup');
        $PAGE->set_title(get_string('customtab_title', 'totara_msteams'));

        echo $OUTPUT->header();

        $id = html_writer::random_id('totara_msteams__page-');
        $uniqueid = function () {
            return html_writer::random_id('msteams-');
        };
        $jsconfig = [
            'id' => $id,
            'debug' => $debug,
            'context' => [
                'name' => input::create_required($uniqueid(), 'name', get_string('customtab_name_label', 'totara_msteams'), get_string('customtab_name_error', 'totara_msteams'), get_string('customtab_name_placeholder', 'totara_msteams'))->get_template_data(),
                'search' => input::create_search($uniqueid(), 'search', get_string('customtab_search_label', 'totara_msteams'), get_string('customtab_search_placeholder', 'totara_msteams'), true)->get_template_data(),
                'list' => listbox::create($uniqueid(), $uniqueid())->get_template_data(),
            ],
        ];

        // If SSO login is not enabled, we add sign out link to the config tab.
        if (empty(get_config('totara_msteams', 'oauth2_issuer'))) {
            $jsconfig['context']['nav'] = [
                'logouttitle' => get_string('loggedinasuser', 'theme_msteams', fullname($USER)),
                'logouttext' => get_string('botfw:msg_signout_button', 'totara_msteams'),
                'logouthref' => (new moodle_url('/login/logout.php', ['sesskey' => sesskey(), 'redirecturl' => (new moodle_url($configurl))->out(false)]))->out(false)
            ];
        }

        $PAGE->requires->js_call_amd('totara_msteams/config_tab', 'init', array($jsconfig));
        $spinner = $OUTPUT->render(spinner::create_loading());
        echo html_writer::div($spinner, '', ['id' => $id]);

        echo $OUTPUT->footer();
        exit;
    }

    /**
     * Generate the html for the tab pages.
     * This function modifies $USER and $SESSION, and **never returns**.
     *
     * @param string $id
     * @codeCoverageIgnore
     */
    public static function tab_page(string $id): void {
        global $USER, $SESSION, $CFG;
        require($CFG->dirroot . '/totara/msteams/db/tabs.php');

        \totara_core\advanced_feature::require('totara_msteams');

        if (empty($tabs[$id])) {
            throw new coding_exception("Tab ID '{$id}' is not defined");
        }

        $SESSION->theme = 'msteams';
        session::set_tab_data($tabs[$id]);

        $logout = optional_param('logout', 0, PARAM_INT);
        if ($logout) {
            require_logout();
        }

        // Turn off editing.
        $USER->editing = false;

        $redirecturl = new moodle_url($tabs[$id]['redirectUrl']);
        self::require_sso_login($redirecturl);
    }

    /**
     * Redirect to the URL in the configurable tab.
     *
     * @codeCoverageIgnore
     */
    public static function custom_tab_page(): void {
        global $USER, $SESSION;

        \totara_core\advanced_feature::require('totara_msteams');

        $redirecturl = required_param('url', PARAM_LOCALURL);
        $pageurl = new moodle_url('/totara/msteams/tabs/customtab.php', ['url' => $redirecturl]);

        $debug = optional_param('debug', false, PARAM_BOOL);

        $SESSION->theme = 'msteams';
        session::set_tab_data([
            'name' => null,
            'url' => $pageurl,
            'redirectUrl' => $redirecturl,
            'customTab' => true,
        ]);

        // Turn off editing.
        $USER->editing = false;

        self::require_sso_login(new moodle_url($redirecturl), $debug);
    }

    /**
     * Deal with single sign on. This function **never returns**.
     *
     * @param moodle_url $returnurl
     * @param boolean $debug
     * @codeCoverageIgnore
     */
    private static function require_sso_login(moodle_url $returnurl, bool $debug = false): void {
        global $USER, $PAGE, $SESSION;
        /** @var \moodle_page $PAGE */

        if (empty($USER->id)) {
            $SESSION->wantsurl = $returnurl->out(false);

            // Single Sign-On over OAuth2.
            $issuer = auth_helper::get_oauth2_issuer();
            if ($issuer === null) {
                require_login();
                exit; // Never reached.
            }

            // Render as minimum HTML as possible to speed up the SSO process.
            $PAGE->set_context(context_system::instance());
            $renderer = new mini_output($PAGE);
            echo $renderer->header();
            echo $renderer->render_sso_login($returnurl, false, $debug);
            echo $renderer->footer();

            exit;
        }

        self::override_language();
        redirect($returnurl);
    }

    /**
     * Get the information of all available tabs.
     *
     * @return array
     */
    public static function get_available_tabs(): array {
        global $CFG;
        require($CFG->dirroot . '/totara/msteams/db/tabs.php');

        return array_filter($tabs, function($tab) {
            // Check for feature flags controlling the tabs
            $features = $tab['features'] ?? [];
            if (!empty($features)) {
                foreach ($features as $feature) {
                    if (advanced_feature::is_disabled($feature)) {
                        return false;
                    }
                }
            }

            $dependencies = $tab['dependencies'] ?? [];
            if (empty($dependencies)) {
                // No dependencies.
                return true;
            }
            if (!core_plugin_manager::instance()->are_dependencies_satisfied($dependencies)) {
                return false;
            }
            foreach ($dependencies as $pluginname => $version) {
                $info = core_plugin_manager::instance()->get_plugin_info($pluginname);
                if (!$info || !$info->is_enabled()) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Return true if the tab is available to the current installation.
     *
     * @param string $pageurl The page URL that must match one of $tabs.
     * @return boolean
     */
    public static function is_tab_available(string $pageurl): bool {
        foreach (self::get_available_tabs() as $tab) {
            if ($tab['url'] === $pageurl) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create a deep link from the URL.
     *
     * @param string $url must starts with $CFG->wwwroot for the deep linking to work
     * @param string|null $label optional, not really used
     * @return moodle_url
     */
    public static function create_deep_link(string $url, string $label = null): moodle_url {
        global $CFG;
        if (strpos($url, $CFG->wwwroot) !== 0) {
            // An external URL must be opened externally.
            return new moodle_url($url);
        }
        $entityid = 'catalog';
        $tabs = self::get_available_tabs();
        if (empty($tabs[$entityid])) {
            debugging("The {$entityid} tab is not available.");
            return new moodle_url($url);
        }
        $appid = get_config('totara_msteams', 'manifest_app_id');
        $params = [];
        if (!empty($label)) {
            $params['label'] = $label;
        }
        $context = [
            // Pass encoded state through subEntityId.
            'subEntityId' => base64url::encode(json_encode([
                'type' => 'openUrl',
                'value' => $url
            ], JSON_UNESCAPED_SLASHES)),
        ];
        $params['context'] = json_encode($context, JSON_UNESCAPED_SLASHES);
        return new moodle_url("https://teams.microsoft.com/l/entity/{$appid}/{$entityid}", $params);
    }

    /**
     * Override the language setting of the current session.
     *
     * @param string|null $lang set null to use the current user's preference language
     */
    public static function override_language(?string $lang = null): void {
        global $USER;
        if ($lang === null) {
            // Reload the preferred language from the database as $USER->lang is not always up to date in the MS Teams session.
            $user = core_user::get_user($USER->id, 'lang');
            if ($user) {
                $lang = $user->lang;
            }
        }
        force_current_language($lang);
    }

    /**
     * Look up the most appropriate block setting for a user.
     *
     * @param string $blockname the name of the block
     * @param stdClass|null $user the user record or null on the current user
     * @return stdClass|null
     */
    public static function find_block_instance(string $blockname, stdClass $user = null): ?stdClass {
        global $USER;
        $user = $user ?? $USER;

        /** @var integer[] */
        $contextids = [];

        $contextids[] = context_user::instance($user->id)->id;
        if (!empty($user->tenantid)) {
            $contextids[] = context_tenant::instance($user->tenantid)->id;
        }
        $contextids[] = context_system::instance()->id;

        /** @var stdClass|null */
        $instance = builder::table('block_instances', 'b')
            ->join(['context', 'c'], 'parentcontextid', 'id')
            ->where_in('b.parentcontextid', $contextids)
            ->where('b.blockname', $blockname)
            // Sort instances in the order of user, tenant and system context.
            ->order_by('c.contextlevel', order::DIRECTION_DESC)
            // If both are in the same context, pick the newer one.
            ->order_by('b.timemodified', order::DIRECTION_DESC)
            ->select('b.*')
            ->first();
        if (empty($instance)) {
            return null;
        }

        return $instance;
    }
}

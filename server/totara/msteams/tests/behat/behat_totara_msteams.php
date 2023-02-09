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

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use core\orm\query\builder;
use totara_msteams\page_helper;
use totara_playlist\entity\playlist as playlist_entity;

/**
 * Contains functions used by behat to test functionality.
 *
 * @package    totara_msteams
 * @category   test
 */
class behat_totara_msteams extends behat_base {
    /**
     * Go to a page in Microsoft Teams.
     *
     * @Given /^I am on Microsoft Teams "(?P<entityid_string>(?:[^"]|\\")*)" page$/
     * @param string $entityid
     */
    public function i_am_on_msteams_page(string $entityid): void {
        behat_hooks::set_step_readonly(false);
        if ($entityid === 'config') {
            $url = new moodle_url('/totara/msteams/tabs/config.php', ['debug' => 1]);
        } else {
            $tabs = page_helper::get_available_tabs();
            if (!isset($tabs[$entityid])) {
                throw new coding_exception('Unknown entity id: '.$entityid);
            }
            $url = new moodle_url($tabs[$entityid]['url'], ['debug' => 1]);
        }
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * Go to a custom tab page in Microsoft Teams.
     *
     * @Given /^I am on "(?P<fullname_string>(?:[^"]|\\")*)" "(?P<itemtype_string>(?:[^"]|\\")*)" custom tab in Microsoft Teams$/
     * @param string $fullname
     * @param string $itemtype
     */
    public function i_am_on_msteams_custom_tab(string $fullname, string $itemtype): void {
        behat_hooks::set_step_readonly(false);
        if ($itemtype === 'course') {
            $record = builder::table('course')->where('fullname', $fullname)->order_by('timecreated')->one();
            if (!$record) {
                throw new ExpectationException('Cannot find a course with name: '.$fullname, $this->getSession()->getDriver());
            }
            $redirecturl = course_get_url($record);
        } else if ($itemtype === 'program' || $itemtype === 'certification') {
            $record = builder::table('prog')->where('fullname', $fullname)->order_by('timecreated')->select('id')->one();
            if (!$record) {
                throw new ExpectationException('Cannot find a program with name: '.$fullname, $this->getSession()->getDriver());
            }
            $redirecturl = new moodle_url('/totara/program/view.php', ['id' => $record->id]);
        } else if ($itemtype === 'article') {
            $record = builder::table('engage_resource')->where('name', $fullname)->where('resourcetype', 'engage_article')->order_by('timecreated')->select('id')->one();
            if (!$record) {
                throw new ExpectationException('Cannot find an article with name: '.$fullname, $this->getSession()->getDriver());
            }
            $redirecturl = new \moodle_url('/totara/engage/resources/article/index.php', ['id' => $record->id]);
        } else if ($itemtype === 'playlist') {
            $record = builder::table(playlist_entity::TABLE)->where('name', $fullname)->order_by('timecreated')->select('id')->one();
            if (!$record) {
                throw new ExpectationException('Cannot find a playlist with name: '.$fullname, $this->getSession()->getDriver());
            }
            // Dang, we can't use playlist::get_url() because it converts the URL to an escaped string.
            $redirecturl = new \moodle_url('/totara/playlist/index.php', ['id' => $record->id]);
        } else {
            throw new coding_exception('Unknown item type: '.$itemtype);
        }
        $url = new moodle_url('/totara/msteams/tabs/customtab.php', ['url' => $redirecturl, 'debug' => 1]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
        $this->wait_for_pending_js();
    }

    /**
     * Terminate the current Microsoft Teams session.
     *
     * @Given /^I log out Microsoft Teams$/
     */
    public function i_log_out_microsoft_teams() {
        // Nothing we can do other than logging out of the current session.
        $this->getSession()->visit($this->locate_path('login/index.php'));
        $this->wait_for_pending_js();
        $this->execute('behat_general::i_click_on', ['Log out', 'button']);
    }
}

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

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Contains functions used by behat to test functionality.
 * This class requires that theme/msteams/script/sdk_stub.js be loaded on site.
 *
 * @package    theme_msteams
 * @category   test
 */
class behat_theme_msteams extends behat_base {
    /**
     * Check the state of the save button.
     *
     * @Given /^the save button should be "(?P<buttonstate_string>(?:[^"]|\\")*)" on Microsoft Teams "(?P<entityid_string>(?:[^"]|\\")*)" page$/
     * @param string $buttonstate enabled or disabled
     * @param string $entityid
     */
    public function the_save_button_should_be(string $buttonstate, string $entityid): void {
        if ($entityid !== 'config') {
            throw new coding_exception('Unknown entity id: '.$entityid);
        }
        $state = $this->getSession()->evaluateScript('return window.microsoftTeams._getState();');
        if ($buttonstate === 'enabled' && empty($state['canSave'])) {
            throw new ExpectationException('The save button should be enabled but it is disabled', $this->getSession()->getDriver());
        } else if ($buttonstate === 'disabled' && !empty($state['canSave'])) {
            throw new ExpectationException('The save button should be disabled but it is enabled', $this->getSession()->getDriver());
        }
    }

    /**
     * Click the save button.
     *
     * @Given /^I click the save button on Microsoft Teams "(?P<entityid_string>(?:[^"]|\\")*)" page$/
     * @param string $entityid
     */
    public function i_click_the_save_button(string $entityid): void {
        if ($entityid !== 'config') {
            throw new coding_exception('Unknown entity id: '.$entityid);
        }
        $this->getSession()->executeScript('window.microsoftTeams._save();');
        $this->wait_for_pending_js();
    }

    /**
     * Check the property value of the settings instance.
     *
     * @Given /^the "(?P<propname_string>(?:[^"]|\\")*)" of Microsoft Teams settings matches value "(?P<value_string>(?:[^"]|\\")*)"$/
     * @param string $propname
     * @param string $value
     * @link https://docs.microsoft.com/en-us/javascript/api/@microsoft/teams-js/microsoftteams.settings.settings
     */
    public function the_name_of_settings_matches_value(string $propname, string $value): void {
        $state = $this->getSession()->evaluateScript('return window.microsoftTeams._getState();');
        if (!isset($state['settings'][$propname])) {
            throw new ExpectationException("The property {$propname} is not found", $this->getSession()->getDriver());
        }
        $propvalue = $state['settings'][$propname];
        if ($propvalue !== $value) {
            throw new ExpectationException("The property {$propname} is {$propvalue}, {$value} expected", $this->getSession()->getDriver());
        }
    }

    /**
     * Check the notify state.
     *
     * @Given /^"(?P<notifystate_string>(?:[^"]|\\")*)" state should be notified to Microsoft Teams$/
     * @param string $notifystate success or failure
     */
    public function state_should_be_notified(string $notifystate): void {
        if ($notifystate !== 'success' && $notifystate !== 'failure') {
            throw new ExpectationException('The state name must be success or failure', $this->getSession()->getDriver());
        }
        $state = $this->getSession()->evaluateScript('return window.microsoftTeams._getState();');
        if (!isset($state['notifyState'][$notifystate])) {
            throw new ExpectationException("The notify state is not {$notifystate}", $this->getSession()->getDriver());
        }
    }
}

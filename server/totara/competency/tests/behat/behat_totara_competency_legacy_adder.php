<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

class behat_totara_competency_legacy_adder extends behat_base {

    /**
     * @Given /^I toggle the legacy adder list entry "([^"]*)" in "([^"]*)"$/
     *
     * @param string $value
     * @param string $title
     */
    public function i_toggle_legacy_adder_list_entry(string $value, string $title) {
        \behat_hooks::set_step_readonly(false);

        $checkbox_node = $this->find_list_entry($value, $title);
        if ($checkbox_node !== null) {
            $checkbox_node->click();
        }
    }

    /**
     * @Given /^the legacy adder list entry "([^"]*)" in "([^"]*)" (should|should not) be enabled$/
     *
     * @param string $value
     * @param string $title
     */
    public function the_legacy_adder_list_entry_should_be_enabled(string $value, string $title, string $not) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $checkbox_node = $this->find_list_entry($value, $title);
        $enabled = !$checkbox_node->hasAttribute('disabled');

        if ($expected  != $enabled) {
            $msg = 'The adder list entry "' . $value . '" in "' . $title . '" is ' . ($expected ? 'not enabled' : 'enabled');
            throw new ExpectationException($msg, $this->getSession());
        }
    }


    /**
     * @Given /^I save my legacy selections and close the "([^"]*)" adder$/
     *
     * @param string $title
     */
    public function i_save_my_legacy_selections_and_close_the_adder(string $title): void {
        behat_hooks::set_step_readonly(false);

        $xpath = '//div[@data-region="modal-container" and @aria-hidden="false" and contains(.//h3, "' . $title . '")]' .
            '//div[@class="modal-footer"]/button[@data-action="save"]';

        $button_node = $this->find('xpath', $xpath);
        if ($button_node) {
            $button_node->click();
        }
    }

    /**
     * @param string $value
     * @param string $title
     * @return NodeElement
     */
    private function find_list_entry(string $value, string $title): NodeElement {
        // The legacy adder now adds a space at the end of the aria label
        $xpath = '//div[@data-region="modal-container" and @aria-hidden="false" and contains(.//h3, "' . $title . '")]' .
            '//input[@type="checkbox" and @aria-label="Select ' . $value . ' "]';
        return $this->find('xpath', $xpath);
    }
}

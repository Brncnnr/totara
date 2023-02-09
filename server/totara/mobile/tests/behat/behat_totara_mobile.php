<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Class step definitions for Totara mobile plugin.
 *
 */
class behat_totara_mobile extends behat_base {

    /**
     * Opens the mobile device emulator
     *
     * @Given /^I am using the mobile emulator$/
     */
    public function i_am_using_modile_emulator() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('/totara/mobile/device_emulator.php'));
        $this->wait_for_pending_js();
    }

    /**
     * Tries to detect the mobile app installer page at https://mobile.totaralearning.com/register/
     *
     * @Given /^I am at the totara mobile app installer$/
     */
    public function i_am_at_the_totara_mobile_app_installer() {
        $this->assertSession()->addressEquals('https://mobile.totaralearning.com/register/');
    }

    /**
     * A custom step to check on the mobile file response, the bytes cannot be completely
     * static since different servers can compress them differently
     *
     * @Then /^I (should|should not) see the mobile file response on line "([^"]*)"$/
     * @param string|int $linenumber
     * @throws Exception
     */
    public function i_should_see_the_mobile_file_response_on_line(string $not, $linenumber) {
        \behat_hooks::set_step_readonly(true);

        $expected = ($not === 'should');
        $response = "/{$linenumber}\) File response [1-9]{1}[0-9]{2,5} bytes/"; // Range of 100-999999 bytes seems resonable.

        $output_node = null;
        $fnd = true;
        try {
            $xpath = "//div[@id = 'Output']//p[@id = 'message{$linenumber}']";
            $output_node = $this->find('xpath', $xpath);
        } catch (Exception $e) {
            $fnd = false;
        }

        if ($fnd) {
            try {
                $this->ensure_node_is_visible($output_node);
            } catch (Exception $e) {
                $fnd = false;
            }
        }

        if (!preg_match($response, $output_node->getText())) {
            $msg = '"' . $response . '"' . ($fnd ? '' : ' not') . ' found in mobile query output';
            throw new ExpectationException($msg, $this->getSession());
        }
    }


}

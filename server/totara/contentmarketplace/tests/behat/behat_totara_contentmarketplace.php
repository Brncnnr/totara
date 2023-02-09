<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */

use core\testing\component_generator;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\config_setup_generator;

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');


class behat_totara_contentmarketplace extends behat_base {

    /**
     * Enable/Disable a content marketplace plugin.
     *
     * @Given /^the "([^"]*)" content marketplace plugin is (enabled|disabled)$/
     * @param string $plugin_name
     * @param string $enable_or_disable
     */
    public function enable_contentmarketplace_plugin(string $plugin_name, string $enable_or_disable): void {
        behat_hooks::set_step_readonly(false);

        $plugin = contentmarketplace::plugin($plugin_name);
        if ($plugin === null) {
            throw new coding_exception("Invalid content marketplace plugin specified: $plugin_name");
        }

        if ($enable_or_disable === 'enabled') {
            $plugin->enable();
        } else {
            $plugin->disable();
        }
    }

    /**
     * Set up the content marketplace plugin by enabling it and setting some default config values if there are any.
     *
     * @Given /^I set up the "([^"]*)" content marketplace plugin$/
     */
    public function set_up_configuration(string $plugin_name): void {
        behat_hooks::set_step_readonly(false);

        $this->enable_contentmarketplace_plugin($plugin_name, 'enabled');

        $generator_class = "\\contentmarketplace_{$plugin_name}\\testing\\generator";
        if (!class_exists($generator_class)) {
            return;
        }

        /** @var component_generator $generator */
        $generator = $generator_class::instance();
        if ($generator instanceof config_setup_generator) {
            $generator->set_up_configuration();
        }
    }

    /**
     * @When /^I navigate to the catalog import page for the "([^"]*)" content marketplace/
     * @param string $plugin_name
     */
    public function i_navigate_to_the_content_marketplace_catalog_import_page(string $plugin_name): void {
        behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/contentmarketplace/explorer.php', ['marketplace' => $plugin_name]);
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

    /**
     * Navigates directly to the Totara content markerplace test filters.
     *
     * This page is only used for acceptance testing and does not appear in the navigation.
     * For that reason we must navigate directly to it.
     *
     * @Given /^I navigate to the content marketplace test filters$/
     */
    public function i_navigate_to_the_content_marketplace_test_filters() {
        behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/contentmarketplace/tests/fixtures/test_filters.php');
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

}

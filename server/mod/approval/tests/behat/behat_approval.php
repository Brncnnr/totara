<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once (__DIR__ . '/../../classes/testing/generator_behat.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ExpectationException;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\workflow_stage_formview;
use Moodle\BehatExtension\Driver\MoodleSelenium2Driver;
use mod_approval\testing\generator_behat;

class behat_approval extends behat_base {

    use generator_behat;

    /**
     * @Given /^I visit the Applications Dashboard/
     */
    public function visit_applications_dashboard() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('mod/approval/application/index.php'));
        $this->wait_for_pending_js();
    }

    /**
     * @Given /^I set the "(?P<field_name_string>(?:[^"]|\\")*)" approval form date selector to "(?P<date_value_string>(?:[^"]|\\")*)" in the "(?P<timezone_string>(?:[^"]|\\")*)" timezone$/
     * @param string $field_name
     * @param string $date_value day month year; 26 June 2020
     * @param string $timezone "server", "user" or a valid timezone string e.g. "Pacific/Auckland"
     */
    public function i_set_the_approval_form_date_selector_to_using_timezone(
        string $field_name,
        string $date_value,
        string $timezone
    ): void {
        behat_hooks::set_step_readonly(false);
        if ($timezone === 'server') {
            $timezone = core_date::get_server_timezone();
        } else if ($timezone === 'user') {
            $timezone = core_date::get_user_timezone();
        }
        $time = new DateTime($date_value, new DateTimeZone($timezone));
        $this->execute('behat_totara_tui::i_set_the_tui_date_selector_to', [$field_name, '@'.$time->getTimestamp()]);
    }

    /**
     * @Then /^the following approval form fields match these values:$/
     * @param TableNode $table
     */
    public function view_following_approval_form_fields_match_these_values(TableNode $table): void {
        behat_hooks::set_step_readonly(true);
        if (!$data = $table->getRowsHash()) {
            return;
        }
        foreach ($data as $label => $value) {
            $exception = new ExpectationException("The '{$label}' is not found", $this->getSession()->getDriver());
            $field_xpath =
                "//*[contains(concat(' ',normalize-space(@class),' '),' tui-mod_approval-applicationView__label ')".
                " and contains(.," . behat_context_helper::escape($label) . ")]" .
                "/parent::*[contains(concat(' ',normalize-space(@class),' '),' tui-mod_approval-applicationView__labelContainer ')]" .
                "/following-sibling::*[contains(concat(' ',normalize-space(@class), ' '),' tui-mod_approval-schemaView__value ')]";
            $field_element = $this->find('xpath', $field_xpath, $exception);
            $field_value = trim($field_element->getText());
            if ($field_value !== $value) {
                throw new ExpectationException(
                    "The '{$label}' value is '{$field_value}', '{$value}' expected",
                    $this->getSession()
                );
            }
        }
    }

    /**
     * Simulate typing in the field
     *
     * Note that the characters will actually be typed in the input text field
     *
     * @Given /^I type "(?P<value_string>(?:[^"]|\\")*)" in the text field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $value
     * @param string $field
     */
    public function i_type_in_the_text_field(string $value, string $field) {
        behat_hooks::set_step_readonly(false);
        if (!$this->running_javascript()) {
            throw new DriverException('Typing simulation requires JavaScript');
        }
        $driver = $this->getSession()->getDriver();
        if (!($driver instanceof MoodleSelenium2Driver)) {
            throw new DriverException('Typing simulation requires our selenium driver');
        }
        // Our syn.js drops some characters when typing more than one character.
        $script = 'Syn.type(' . json_encode($value) . ', {{ELEMENT}})';
        $fieldnode = $this->find_field($field);
        $driver->triggerSynScript($fieldnode->getXpath(), $script);
    }

    /**
     * Publish a workflow.
     *
     * @Given /^I publish the "(?P<id_number>(?:[^"]|\\")*)" workflow$/
     * @param string $id_number
     */
    public function i_publish_the_workflow(string $id_number) {
        $workflow = $this->resolve_workflow(['workflow' => $id_number]);
        $workflow->publish($workflow->get_latest_version());
    }

    /**
     * Archive a workflow.
     *
     * @Given /^I archive the "(?P<id_number>(?:[^"]|\\")*)" workflow$/
     * @param string $id_number
     */
    public function i_archive_the_workflow(string $id_number) {
        $workflow = $this->resolve_workflow(['workflow' => $id_number]);
        $workflow->archive();
    }

    /**
     * Delete default approval level.
     *
     * @Given /^I delete default approval level for stage "(?P<name>(?:[^"]|\\")*)"$/
     * @param string $name
     */
    public function i_delete_default_approval_level(string $name) {
        $workflow_stage = $this->resolve_workflow_stage(['workflow_stage' => $name]);
        $workflow_stage->get_approval_levels()->first()->delete();
    }

    /**
     * Delete default forviews.
     *
     * @Given /^I delete default formviews for stages "(?P<name>(?:[^"]|\\")*)"$/
     * @param string $name
     */
    public function i_delete_default_formviews(string $name) {
        $workflow = $this->resolve_workflow(['workflow' => $name]);
        $workflow_version = $workflow->latest_version;
        $stages = $workflow_version->stages;
        foreach ($stages as $stage) {
            $default_formviews = $stage->formviews->all();
            /** @var workflow_stage_formview $formview */
            foreach ($default_formviews as $formview) {
                $stage->configure_formview([['field_key' => $formview->field_key, 'visibility' => formviews::HIDDEN]]);
            }
        }
    }
}

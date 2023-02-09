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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

class behat_totara_evidence extends behat_base {

    private const ITEM_FIELD_ROW = '.tw-evidence__fields_view_row';
    private const METADATA_ROW = '.tw-evidence__metadata_row';
    private const ROW_LABEL = '.tf_element_title';
    private const ROW_CONTENT = '.tf_element_input';

    private const TYPE_SELECTOR = '.tw-evidence__select_type_selector_element';
    private const TYPE_SELECTOR_OPTIONS = 'ul.form-autocomplete-suggestions';
    private const TYPE_SELECTOR_OPTION = 'li';
    private const TYPE_SELECTOR_OPEN_OPTIONS = '.form-autocomplete-downarrow';

    /**
     * Go to the current user's evidence bank.
     *
     * @When /^I navigate to my evidence bank$/
     */
    public function i_navigate_to_my_evidence_bank(): void {
        behat_hooks::set_step_readonly(false);

        $this->getSession()->visit($this->locate_path(
            (new moodle_url('/totara/evidence/index.php'))->out_as_local_url(false)
        ));
    }

    /**
     * Go to the evidence bank for a user.
     *
     * @When /^I navigate to the evidence bank for user "([^"]*)"$/
     * @param string $username
     */
    public function i_navigate_to_the_evidence_bank_for(string $username): void {
        global $DB;
        behat_hooks::set_step_readonly(false);

        $this->getSession()->visit($this->locate_path(
            (new moodle_url('/totara/evidence/index.php', [
                'user_id' => $DB->get_field('user', 'id', ['username' => $username]),
            ]))->out_as_local_url(false)
        ));
    }

    /**
     * Get a key-value array of the fields that are on the page.
     *
     * @param string $row_selector
     * @return array
     */
    private function get_field_rows(string $row_selector): array {
        /** @var NodeElement[] $evidence_field_rows */
        $evidence_field_rows = $this->find_all('css', $row_selector);

        $fields = [];
        foreach ($evidence_field_rows as $field_row) {
            $field_label = $field_row->find('css', self::ROW_LABEL);
            $field_content = $field_row->find('css', self::ROW_CONTENT);

            if ($field_label !== null && $field_content !== null) {
                $fields[$field_label->getText()] = $field_content->getText();
            }
        }

        return $fields;
    }

    /**
     * Check that a field has the value.
     *
     * @param array $field_rows
     * @param string $field_label
     * @param string $expected_text
     * @throws ExpectationException
     */
    private function i_should_see_in_the_field(array $field_rows, string $field_label, string $expected_text): void {
        if (!isset($field_rows[$field_label])) {
            throw new ExpectationException("Could not find the \"{$field_label}\" evidence field", $this->getSession());
        }

        if (strpos($field_rows[$field_label], $expected_text) === false) {
            throw new ExpectationException(
                "Text \"{$expected_text}\" not found in the \"{$field_label}\" evidence field", $this->getSession()
            );
        }
    }

    /**
     * Check the text content of a evidence item field.
     *
     * @Then /^I should see "([^"]*)" in the "([^"]*)" evidence item field$/
     * @param string $expected_text
     * @param string $field_label
     * @throws ExpectationException
     */
    public function i_should_see_in_the_evidence_field(string $expected_text, string $field_label): void {
        $this->i_should_see_in_the_field(
            $this->get_field_rows(self::ITEM_FIELD_ROW),
            $field_label,
            $expected_text
        );
    }

    /**
     * Check the text content of a evidence metadata field.
     *
     * @Then /^I should see "([^"]*)" in the "([^"]*)" evidence metadata field$/
     * @param string $expected_text
     * @param string $field_label
     * @throws ExpectationException
     */
    public function i_should_see_in_the_evidence_metadata_field(string $expected_text, string $field_label): void {
        $this->i_should_see_in_the_field(
            $this->get_field_rows(self::METADATA_ROW),
            $expected_text,
            $field_label
        );
    }

    /**
     * Check the contents of evidence fields on the page.
     *
     * @Then /^I should see the evidence item fields contain:$/
     * @param TableNode $expected
     * @throws ExpectationException
     */
    public function i_should_see_the_evidence_item_fields_contain(TableNode $expected): void {
        behat_hooks::set_step_readonly(true);

        $expected_values = $expected->getRowsHash();
        $actual_values = $this->get_field_rows(self::ITEM_FIELD_ROW);
        if ($expected_values !== $actual_values) {
            throw new ExpectationException('Expected and actual evidence fields do not match', $this->getSession());
        }
    }

    /**
     * Check the contents of evidence metadata on the page.
     *
     * @Then /^I should see the evidence metadata contains:$/
     * @param TableNode $expected
     * @throws ExpectationException
     */
    public function i_should_see_the_evidence_metadata_contains(TableNode $expected): void {
        behat_hooks::set_step_readonly(true);

        $expected_values = $expected->getRowsHash();
        $actual_values = $this->get_field_rows(self::METADATA_ROW);
        foreach ($expected_values as $expected_value_key => $expected_value) {
            if (!isset($actual_values[$expected_value_key])
                || strpos($actual_values[$expected_value_key], $expected_value) === false) {
                throw new ExpectationException('Expected and actual evidence metadata do not match', $this->getSession());
            }
        }
    }

    /**
     * Get the type selector if it is visible.
     *
     * @return NodeElement
     * @throws ExpectationException
     */
    private function find_type_selector(): NodeElement {
        $selector = $this->find('css', self::TYPE_SELECTOR);
        if ($selector === null || !$selector->isVisible()) {
            throw new ExpectationException('Evidence type selector not visible', $this->getSession());
        }
        return $selector;
    }

    /**
     * Get the selector options if they are visible.
     *
     * @return NodeElement[]
     * @throws ExpectationException
     */
    private function find_type_selector_options(): array {
        $selector_options = $this->find_type_selector()->find('css', self::TYPE_SELECTOR_OPTIONS);
        if ($selector_options === null || !$selector_options->isVisible()) {
            throw new ExpectationException('Evidence type selector options not visible', $this->getSession());
        }
        return $selector_options->findAll('css', self::TYPE_SELECTOR_OPTION);
    }

    /**
     * Expand the autocomplete dropdown list.
     *
     * @When /^I expand the evidence type selector$/
     * @return void
     * @throws ExpectationException
     */
    public function i_expand_the_evidence_type_selector(): void {
        $this->wait_for_pending_js();
        $this->find_type_selector()->find('css', self::TYPE_SELECTOR_OPEN_OPTIONS)->click();
        $this->wait_for_pending_js();
    }

    /**
     * Type text into the autocomplete selector.
     *
     * @When /^I search for "([^"]*)" in the evidence type selector$/
     * @param string $text
     * @throws ExpectationException
     */
    public function i_search_for_in_the_evidence_type_selector(string $text): void {
        $this->find_type_selector()->find('css', 'input')->setValue($text);
        $this->wait_for_pending_js();
    }

    /**
     * Select an evidence type from the autocomplete dropdown list.
     *
     * @When /^I select type "([^"]*)" from the evidence type selector$/
     * @return void
     * @throws ExpectationException
     */
    public function i_select_type_from_the_evidence_type_selector(string $type_name): void {
        foreach ($this->find_type_selector_options() as $option) {
            if ($option->getText() == $type_name) {
                $option->click();
                $this->wait_for_pending_js();
                return;
            }
        }
        throw new ExpectationException('Evidence type "' . $type_name . '" not found in the type selector', $this->getSession());
    }

    /**
     * Select an evidence type from the autocomplete dropdown list.
     *
     * @Then /^I should see the evidence type selector contains:$/
     * @param TableNode $expected
     * @throws ExpectationException
     */
    public function i_should_see_the_evidence_type_selector_contains(TableNode $expected): void {
        behat_hooks::set_step_readonly(true);

        $expected_values = array_keys($expected->getRowsHash());

        $this->wait_for_pending_js();
        $actual_values = array_map(static function (NodeElement $option) {
            return $option->getText();
        }, $this->find_type_selector_options());

        if ($expected_values !== $actual_values) {
            throw new ExpectationException('Expected and actual evidence types do not match', $this->getSession());
        }
    }

}

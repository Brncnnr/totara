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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_core
 */

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

/**
 * Helper step definitions for the tui adder (core/tui/components/adder).
 */
class behat_totara_tui_adder extends behat_base {
    /**
     * Saves selections and closes the adder.
     *
     * @Given /^I save my selections and close the adder$/
     */
    public function i_save_my_selections_and_close_the_adder(): void {
        behat_hooks::set_step_readonly(false);
        $this->find('css', '.tui-adder__actions .tui-formBtn--prim')->click();
    }

    /**
     * Closes the adder without saving any selections.
     *
     * @Given /^I discard my selections and close the adder$/
     */
    public function i_discard_my_selections_and_close_the_adder(): void {
        behat_hooks::set_step_readonly(false);
        $this->execute("behat_general::i_click_on_in_the", ['Cancel', 'button', '.tui-adder__actions', 'css_element']);
    }

    /**
     * Checks that the adder picker table has the specified entries that can be
     * selected.
     *
     * @Given /^I should see the following unselected adder picker entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each picker column.
     */
    public function i_should_see_the_following_unselected_adder_picker_entries(
        TableNode $listing
    ): void {
        behat_hooks::set_step_readonly(true);

        $picker = $this->picker();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($picker, $expected);
            $selected = $entry
                && $this->entry_is_selected($entry)
                && !$this->entry_is_disabled($entry);

            if ($selected) {
                $values = $this->as_string($expected);
                $this->fail("No unselected picker entry: $values");
            }
        }
    }

    /**
     * Checks that the adder picker table has the specified entries that can be
     * unselected.
     *
     * @Given /^I should see the following selected adder picker entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each picker column.
     */
    public function i_should_see_the_following_selected_adder_picker_entries(
        TableNode $listing
    ): void {
        behat_hooks::set_step_readonly(true);

        $picker = $this->picker();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($picker, $expected);
            $selected = $entry
                && $this->entry_is_selected($entry)
                && !$this->entry_is_disabled($entry);

            if (!$selected) {
                $values = $this->as_string($expected);
                $this->fail("No selected picker entry: $values");
            }
        }
    }

    /**
     * Checks that the adder picker table has the specified entries that are
     * disabled.
     *
     * @Given /^I should see the following disabled adder picker entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each picker column.
     */
    public function i_should_see_the_following_disabled_adder_picker_entries(
        TableNode $listing
    ): void {
        behat_hooks::set_step_readonly(true);

        $picker = $this->picker();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($picker, $expected);
            $disabled = $entry && $this->entry_is_disabled($entry);

            if (!$disabled) {
                $values = $this->as_string($expected);
                $this->fail("No disabled picker entry: $values");
            }
        }
    }

    /**
     * Checks that the adder picker table does not have the specified entries.
     *
     * @Given /^I should not see the following adder picker entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each picker column.
     */
    public function i_should_not_see_the_following_adder_picker_entries(TableNode $listing): void {
        behat_hooks::set_step_readonly(true);

        $picker = $this->picker();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($picker, $expected);
            $visible = $entry && $entry->isVisible();

            if ($visible) {
                $values = $this->as_string($expected);
                $this->fail("Picker entry is visible: $values");
            }
        }
    }

    /**
     * Selects/deselects an entry in the adder picker table by a column value.
     *
     * @Given /^I toggle the adder picker entry with "([^"]*)" for "([^"]*)"$/
     *
     * @param string $value the targetted value in the picker table.
     * @param string $column the targetted column in the picker table.
     */
    public function i_toggle_the_adder_picker_entry_with_for(string $value, string $column): void {
        behat_hooks::set_step_readonly(false);
        $this->toggle_entry_with_value($this->picker(), $value, $column);
    }

    /**
     * Checks that the adder basket table has the specified selected entries.
     *
     * @Given /^I should see the following selected adder basket entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each basket column.
     */
    public function i_should_see_the_following_selected_adder_basket_entries(
        TableNode $listing
    ): void {
        behat_hooks::set_step_readonly(true);

        $basket = $this->basket();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($basket, $expected);
            $selected = $entry && $this->entry_is_selected($entry);

            if (!$selected) {
                $values = $this->as_string($expected);
                $this->fail("No selected basket entry: $values");
            }
        }
    }

    /**
     * Checks that the adder basket table does not have the specified entries.
     *
     * @Given /^I should not see the following adder basket entries:$/
     *
     * @param TableNode $listing a table with a header row and the expected
     *        values for each basket column.
     */
    public function i_should_not_see_the_following_adder_basket_entries(TableNode $listing): void {
        behat_hooks::set_step_readonly(true);

        $basket = $this->basket();

        foreach ($listing->getHash() as $expected) {
            $entry = $this->find_entry($basket, $expected);

            if ($entry) {
                $values = $this->as_string($expected);
                $this->fail("Basket entry present: $values");
            }
        }
    }

    /**
     * Selects/deselects an entry in the adder basket table by a column value.
     *
     * @Given /^I toggle the adder basket entry with "([^"]*)" for "([^"]*)"$/
     *
     * @param string $value the targetted value in the basket table.
     * @param string $column the targetted column in the basket table.
     */
    public function i_toggle_the_adder_basket_entry_with_for(string $value, string $column): void {
        behat_hooks::set_step_readonly(false);
        $this->toggle_entry_with_value($this->basket(), $value, $column);
    }

    /**
     * Selects/deselects an entry in the target table by a column value.
     *
     * @param NodeElement $table the targetted table.
     * @param string $value the targetted value in the basket table.
     * @param string $column the targetted column in the basket table.
     */
    private function toggle_entry_with_value(
        NodeElement $table,
        string $value,
        string $column
    ): void {
        $entry = $this->find_entry($table, [$column => $value]);
        if (!$entry) {
            $this->fail("No such entry: '$column' = '$value'");
        }

        $this->entry_toggle_selection($entry);
    }

    /**
     * Indicates the first entry in the given table which matches all specified values.
     *
     * @param NodeElement $table the target table in which to find entries.
     * @param array $column_values mapping of column names to expected values.
     *
     * @return NodeElement the matching entry or null if it did not exist.
     */
    private function find_entry(NodeElement $table, array $column_values): ?NodeElement {
        $headings = $this->heading_indexes($table, array_keys($column_values));

        foreach ($this->entries($table) as $entry) {
            $values = $this->entry_column_values($entry, $headings);
            if ($values === $column_values) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Returns column index for the specified table columns.
     *
     * @param NodeElement $table the target table from which to find headings.
     * @param string[] $columns column names to look up.
     *
     * @return array a mapping of indexes to column names.
     */
    private function heading_indexes(NodeElement $table, array $columns): array {
        $headings = $table->findAll('css', '.tui-dataTableHeaderCell');

        $indexes = [];
        foreach ($headings as $index => $heading) {
            $label = trim($heading->getText());

            if (in_array($label, $columns)) {
                $indexes[$index] = $label;
            }
        }

        return $indexes;
    }

    /**
     * Returns the specified target column values for the given entry.
     *
     * @param NodeElement $entry entry to look up.
     * @param array $columns a mapping of column indexes to names.
     *
     * @return array a mapping of column names to values.
     */
    private function entry_column_values(NodeElement $entry, array $columns): array {
        $cells = $this->entry_values($entry);

        $values = [];
        foreach ($columns as $index => $column) {
            $values[$column] = $cells[$index]->getText() ?? null;
        }

        return $values;
    }

    /**
     * Indicates whether given entry has already been selected.
     *
     * @param NodeElement $entry entry to check.
     *
     * @return bool true if the entry has been selected.
     */
    private function entry_is_selected(NodeElement $entry): bool {
        // Note entry_toggle_selection() targets a different element!
        $selector = $entry->find('css', '.tui-checkbox__input');
        if (!$selector) {
            $this->fail("entry has no selector");
        }

        return $selector->isSelected();
    }

    /**
     * Indicates whether given entry is disabled.
     *
     * @param NodeElement $entry entry to check.
     *
     * @return bool true if the entry has been disabled.
     */
    private function entry_is_disabled(NodeElement $entry): bool {
        $selector = $entry->find('css', '.tui-checkbox__input');
        if (!$selector) {
            $this->fail("entry has no selector");
        }

        return $selector->hasAttribute('disabled');
    }

    /**
     * Toggles the specified entry selection.
     *
     * @param NodeElement $entry the targetted entry.
     */
    private function entry_toggle_selection(NodeElement $entry): void {
        // Weirdly enough, it is not possible to "click" the actual HTML input
        // element; Behat/Selenium will complain about intercepted clicks. Have
        // to click on the <div> wrapping the input element instead.
        $selector = $entry->find('css', '.tui-dataTableSelectRowCell .tui-checkbox');
        if (!$selector) {
            $this->fail("entry has no selector");
        }

        $selector->click();
    }

    /**
     * Returns the adder picker table.
     *
     * @return NodeElement the picker table.
     */
    private function picker(): NodeElement {
        return $this->find('css', '.tui-adder__list .tui-dataSelectTable .tui-dataTable');
    }

    /**
     * Returns the adder basket table.
     *
     * @return NodeElement the basket table.
     */
    private function basket(): NodeElement {
        $locator = '.tui-adder__listBasket .tui-dataSelectTable .tui-dataTable';
        return $this->find('css', $locator);
    }

    /**
     * Returns the non heading rows in the table element.
     *
     * @param NodeElement $table the target element in which to find entries.
     *
     * @return NodeElement[] the non heading rows in the target table. Each entry
     *         here is an array of cells making up that entry.
     */
    private function entries(NodeElement $table): array {
        return $table->findAll('css', '.tui-dataTableRow');
    }
    /**
     * Returns the value cells for the given row.
     *
     * @param NodeElement $row the row from which to extract values.
     *
     * @return NodeElement[] the value cells.
     */
    private function entry_values(NodeElement $entry): array {
        return $entry->findAll('css', '.tui-dataTableCell__content');
    }

    /**
     * Convenience method to fail from an ExpectationException.
     *
     * @param string $error error message.
     */
    private function fail(string $error): void {
        throw new ExpectationException($error, $this->getSession());
    }

    /**
     * Convenience method to stringify an associative array. You cannot use the
     * implode() method for this because that ignores the array keys.
     *
     * @param array $mapping the associative array to stringify.
     *
     * @return string the stringified array.
     */
    private function as_string(array $mapping): string {
        $stringified = '';

        foreach ($mapping as $key => $value) {
            $kvp = "'$key'='$value'";
            $stringified = $stringified ? "$stringified, $kvp" : $kvp;
        }

        return $stringified;
    }
}
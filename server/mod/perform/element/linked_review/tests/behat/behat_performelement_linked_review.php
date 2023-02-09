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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_linked_review
 */

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;

class behat_performelement_linked_review extends behat_base {

    /**
     * @param string $element_title
     * @param int $content_item_number
     * @param bool $is_print
     * @return NodeElement
     * @throws ExpectationException
     */
    private function find_linked_review_selected_content(
        string $element_title,
        int $content_item_number,
        bool $is_print = false
    ): NodeElement {
        /** @var behat_mod_perform $behat_mod_perform */
        $behat_mod_perform = behat_context_helper::get('behat_mod_perform');

        $selected_content_element = $behat_mod_perform
            ->find_question_from_text($element_title, $is_print)
            ->find('css', ".tui-linkedReviewParticipantForm__item:nth-child({$content_item_number})");

        if ($selected_content_element === null) {
            throw new ExpectationException(
                "Couldn't locate select content #{$content_item_number} for element {$element_title}", $this->getSession()
            );
        }
        return $selected_content_element;
    }

    /**
     * @Then /^I should(| not) see "([^"]*)" in the ([0-9]+)(st|nd|rd|th) selected content item for the "([^"]*)" linked review(| print) element$/
     * @param string $should_not
     * @param string $expected_text
     * @param string $item_number
     * @param string $unused
     * @param string $element_title
     * @param string $is_print
     */
    public function i_should_see_in_selected_content(
        string $should_not,
        string $expected_text,
        string $item_number,
        string $unused,
        string $element_title,
        string $is_print
    ): void {
        behat_hooks::set_step_readonly(false);

        $element = $this->find_linked_review_selected_content($element_title, $item_number, (bool) $is_print);
        $text_in_element = strpos($element->getText(), $expected_text) !== false;
        if ($should_not && $text_in_element) {
            throw new ExpectationException("Found text {$expected_text} when shouldn't have", $this->getSession());
        } else if (!$should_not && !$text_in_element) {
            throw new ExpectationException("No text matching {$expected_text} found.", $this->getSession());
        }
    }

    /**
     * @Then /^I should(| not) see "([^"]*)" in the "([^"]*)" "([^"]*)" for the ([0-9]+)(st|nd|rd|th) selected content item in the "([^"]*)" linked review(| print) element$/
     * @param string $should_not
     * @param string $expected_text
     * @param string $sub_element_locator
     * @param string $sub_element_selector
     * @param string $item_number
     * @param string $unused
     * @param string $element_title
     * @param string $is_print
     */
    public function i_should_see_in_selected_content_in_css(
        string $should_not,
        string $expected_text,
        string $sub_element_locator,
        string $sub_element_selector,
        string $item_number,
        string $unused,
        string $element_title,
        string $is_print
    ): void {
        behat_hooks::set_step_readonly(false);

        $element = $this->find_linked_review_selected_content($element_title, $item_number, (bool) $is_print);
        if ($sub_element_locator && $sub_element_selector) {
            $element = $element
                ->find(...$this->transform_selector($sub_element_selector, $sub_element_locator));
            if ($element === null) {
                throw new ExpectationException(
                    "Couldn't locate sub element {$sub_element_locator} in the linked review element content", $this->getSession()
                );
            }
        }

        $text_in_element = strpos($element->getText(), $expected_text) !== false;
        if ($should_not && $text_in_element) {
            throw new ExpectationException("Found text {$expected_text} when shouldn't have", $this->getSession());
        } else if (!$should_not && !$text_in_element) {
            throw new ExpectationException("No text matching {$expected_text} found.", $this->getSession());
        }
    }

    /**
     * @When /^I click on "([^"]*)" "([^"]*)" in the ([0-9]+)(st|nd|rd|th) selected content item for the "([^"]*)" linked review element$/
     * @param string $click_text
     * @param string $click_type
     * @param string $item_number
     * @param string $unused
     * @param string $element_title
     */
    public function i_click_on_in_selected_content(
        string $click_text,
        string $click_type,
        string $item_number,
        string $unused,
        string $element_title
    ): void {
        behat_hooks::set_step_readonly(false);

        $element = $this->find_linked_review_selected_content($element_title, $item_number);
        $thing_to_click = $element->find(...$this->transform_selector($click_type, $click_text));
        if ($thing_to_click === null) {
            throw new ExpectationException(
                "Couldn't locate {$click_type} with text {$click_text} in the content item",
                $this->getSession()
            );
        }
        $thing_to_click->click();
    }

}

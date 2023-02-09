<?php
/*
 * This file is part of Totara Perform
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package performelement_multi_choice_multi
 */

use Behat\Mink\Exception\ExpectationException;

class behat_performelement_multi_choice_multi extends behat_base {

    public const EDIT_ELEMENT_LOCATOR = '.tui-multiChoiceMultiAdminEdit';
    public const ADD_OPTION_LOCATOR   = '.tui-multiChoiceMultiAdminEdit__addOption';
    public const QUESTION_DISPLAY_OPTIONS_LOCATOR = '.tui-checkbox__label';

    /**
     * @When /^I click multiple answers question add new option$/
     */
    public function i_click_multiple_answers_question_add_new_option(): void {
        behat_hooks::set_step_readonly(false);

        $edit_element = $this->find('css', self::EDIT_ELEMENT_LOCATOR);
        $add_button = $edit_element->find('css', self::ADD_OPTION_LOCATOR);
        if (!$add_button) {
            throw new ExpectationException('Multiple answers add new option not found!', $this->getSession());
        }
        $add_button->click();
    }

    /**
     * @Given /^I should see perform multiple answers question "([^"]*)" is saved with options "([^"]*)"$/
     * @param $question_text
     * @param $question_options
     *
     * @throws ExpectationException
     */
    public function i_should_see_multiple_answers_question_is_saved_with_options(
        string $question_text,
        string $question_options
    ): void {
        /** @var behat_mod_perform $behat_mod_perform */
        $behat_mod_perform = behat_context_helper::get('behat_mod_perform');
        $question = $behat_mod_perform->find_admin_question_from_text($question_text);
        $options = $question->findAll('css', self::QUESTION_DISPLAY_OPTIONS_LOCATOR);
        $expected_options = explode(",", $question_options);
        $actual_options = [];
        foreach ($options as $option) {
            $actual_options[] = trim($option->getText());
        }
        if ($expected_options != $actual_options) {
            throw new ExpectationException("Question {$question_text} not found with options {$question_options}", $this->getSession());
        }
    }

    /**
     * @Given /^I delete multiple answers question option$/
     *
     * @throws ExpectationException
     */
    public function i_delete_multiple_answers_question_option(): void {
        behat_hooks::set_step_readonly(false);

        $delete_button = $this->find('css', '.tui-multiChoiceMultiAdminEdit .tui-repeater__delete:not([disabled])');
        $delete_button->click();
    }
}

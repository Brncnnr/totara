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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use core\entity\user;
use core\orm\entity\entity;
use mod_perform\controllers\activity\edit_activity;
use mod_perform\controllers\activity\manage_activities;
use mod_perform\controllers\activity\manage_participation;
use mod_perform\controllers\activity\print_user_activity;
use mod_perform\controllers\activity\user_activities;
use mod_perform\controllers\activity\view_external_participant_activity;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\controllers\perform_controller;
use mod_perform\controllers\reporting\performance\view_only_user_activity;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\entity\activity\subject_instance;
use mod_perform\notification\factory;
use mod_perform\task\check_notification_trigger_task;
use performelement_redisplay\redisplay;

class behat_mod_perform extends behat_base {

    public const PERFORM_ELEMENT_VALIDATION_ERROR_LOCATOR = '.tui-formFieldError';
    public const PERFORM_ELEMENT_LOCATOR = '.tui-participantContent__sectionItem';
    public const PERFORM_ELEMENT_PRINT_LOCATOR = '.tui-participantContentPrint__sectionItem';
    public const PERFORM_ELEMENT_QUESTION_TEXT_LOCATOR = '.tui-performElementParticipantHeader__title';
    public const PERFORM_ELEMENT_QUESTION_TEXT_PRINT_LOCATOR = '.tui-performElementParticipantHeader__title';
    public const PERFORM_ELEMENT_QUESTION_OPTIONAL_LOCATOR = '.tui-performRequiredOptionalIndicator--optional';
    public const PERFORM_ELEMENT_QUESTION_REQUIRED_LOCATOR = '.tui-performRequiredOptionalIndicator--required';
    public const SHORT_TEXT_RESPONSE_LOCATOR = 'input';
    public const LONG_TEXT_RESPONSE_LOCATOR = '.tui-weka';
    public const DATE_PICKER_RESPONSE_LOCATOR = '.tui-dateSelector';
    public const MULTI_CHOICE_MULTI_RESPONSE_LOCATOR = '.tui-checkboxGroup';
    public const MULTI_CHOICE_SINGLE_RESPONSE_LOCATOR = '.tui-radioGroup';
    public const CUSTOM_RATING_SCALE_RESPONSE_LOCATOR = '.tui-radioGroup';
    public const NUMERIC_RATING_SCALE_RANGE_RESPONSE_LOCATOR = 'input[type=range]';
    public const NUMERIC_RATING_SCALE_NUMBER_RESPONSE_LOCATOR = 'input[type=number]';
    public const PERFORM_ELEMENT_RESPONSE_CONTAINER_LOCATOR = '.tui-performElementParticipantFormContent__response-output, .tui-performElementParticipantResponse';
    public const PERFORM_ELEMENT_OTHER_RESPONSE_CONTAINER_LOCATOR = '.tui-otherParticipantResponses';
    public const PERFORM_ELEMENT_OTHER_RESPONSE_RELATION_LOCATOR = '.tui-otherParticipantResponses .tui-formLabel';
    public const TUI_OTHER_PARTICIPANT_RESPONSES_ANONYMOUS_RESPONSE_PARTICIPANT_LOCATOR = '.tui-otherParticipantResponses__response-participant';
    public const PARTICIPANT_FORM_RESPONSE_DISPLAY_LOCATOR = '.tui-participantFormResponseDisplay';
    public const PARTICIPANT_FORM_NO_PARTICIPANT = '.tui-otherParticipantResponses__noParticipant';
    public const PARTICIPANT_FORM_HTML_VIEW_ONLY_RESPONSE_LOCATOR = '.tui-participantFormHtmlResponseDisplay';
    public const PERFORM_ACTIVITY_PRINT_SECTION_LOCATOR = '.tui-participantContentPrint__section';
    public const PERFORM_ACTIVITY_YOUR_RELATIONSHIP_VALUE_EXTERNAL = '.tui-participantContent__user-relationshipValue';
    public const PERFORM_ACTIVITY_GENERAL_INFORMATION_RELATIONSHIP_LOCATOR = '.tui-participantGeneralInformation__relationship-heading';
    public const PERFORM_SHOW_OTHERS_RESPONSES_LOCATOR = '.tui-participantContent__sectionHeading-otherResponseSwitch button';
    public const MANAGE_CONTENT_PARTICIPANT_NAME_LOCATOR = '.tui-performActivitySectionRelationship__item-name';
    public const MANAGE_CONTENT_ADD_RESPONDING_PARTICIPANTS_BUTTON_LABEL = '.tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(%d) [aria-label=\'Add participants\']';
    public const MANAGE_CONTENT_ADD_VIEW_ONLY_PARTICIPANTS_BUTTON_LABEL = '.tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(%d) [aria-label=\'Add view-only participants\']';
    public const MANAGE_CONTENT_ACTIVITY_SECTION = '.tui-performManageActivityContent__items .tui-performActivitySection:nth-of-type(%d)';
    public const MANAGE_CONTENT_ACTIVITY_SECTION_CONTENT_SUMMARY = '.tui-grid-item:nth-of-type(%d) .tui-performActivitySectionElementSummary__count';
    public const INSTANCE_INFO_CARD_LABEL_LOCATOR = '.tui-instanceInfoCard__info-label';
    public const INSTANCE_INFO_CARD_VALUE_LOCATOR = '.tui-instanceInfoCard__info-value';

    public const TUI_USER_ANSWER_ERROR_LOCATOR = '.tui-formFieldError';
    public const USER_QUESTION_TEXT_LOCATOR = '.tui-collapsible__header-text';
    public const TUI_TAB_ELEMENT = '.tui-tabs__tabs';
    public const SCHEDULE_SAVE_LOCATOR = '.tui-performAssignmentSchedule__form-buttons .tui-formBtn--prim';

    public const TUI_TRASH_ICON_BUTTON = "button[aria-label='Delete %s']";

    public const QUESTION_DISPLAY_LOCATOR = '.tui-performAdminCustomElement';
    public const EDIT_QUESTION_DISPLAY_TITLE_LOCATOR = '.tui-performAdminCustomElement__content-titleText';
    public const QUESTION_DRAG_ITEM_LOCATOR = '.tui-performSectionContent__draggableItem';
    public const QUESTION_DRAG_MOVE_ICON_LOCATOR = '.tui-performAdminCustomElement__moveIcon';
    public const RESPONSE_VISIBILITY_DESCRIPTION_LOCATOR = '.tui-participantContent__sectionHeadingOtherResponsesDescription';

    public const ADMIN_FORM_RESPONSE_REQUIRED = 'input[name="responseRequired"]';
    public const ADMIN_FORM_TITLE_INPUT = 'input[name=rawTitle]';
    public const ADMIN_FORM_DONE_BUTTON = '.tui-performAdminCustomElementEdit__submit';
    public const ADMIN_FORM = '.tui-performAdminCustomElement';
    public const ADMIN_FORM_STATIC_CONTENT_WEKA = '.tui-staticContentAdminEdit .tui-weka';
    public const FORM_BUILDER_ADD_ELEMENT_BUTTONS = '.tui-performSectionContent__add .tui-dropdownButton'; // Top level only.
    public const FORM_BUILDER_RAW_TITLE_NAME = 'rawTitle';
    public const TUI_NOTEPAD_LINES = '.tui-notepadLines';
    public const TUI_PARTICIPANT_CONTENT_PRINT_PRINTED_TODO = '.tui-performElementParticipantHeader__printedTodo';
    public const ADMIN_CUSTOM_ELEMENT_SUMMARY_SECTION_TITLE = '.tui-performAdminCustomElementSummary__section-title';
    public const ADMIN_CUSTOM_ELEMENT_SUMMARY_SECTION_VALUE = '.tui-performAdminCustomElementSummary__section-value';

    /**
     * Navigate to the specified page and wait for JS.
     *
     * @param moodle_url $page_url
     */
    private function navigate_to_page(moodle_url $page_url): void {
        behat_hooks::set_step_readonly(false);

        $this->getSession()->visit($this->locate_path($page_url->out(false)));
    }

    /**
     * @When /^I navigate to the outstanding perform activities list page$/
     * @throws Exception
     */
    public function i_navigate_to_the_outstanding_perform_activities_page(): void {
        $this->navigate_to_page(user_activities::get_url());
    }

    /**
     * @When /^I navigate to the user activity page for id "([^"]*)"$/
     * @param int $participant_instance_id
     * @throws Exception
     */
    public function i_navigate_to_the_user_activity_profile_details_page_for_id(int $participant_instance_id): void {
        $this->navigate_to_page(view_user_activity::get_url(['participant_instance_id' => $participant_instance_id]));
    }

    /**
     * @When /^I navigate to the manage perform activities page$/
     * @throws Exception
     */
    public function i_navigate_to_the_manage_perform_activities_page(): void {
        $this->navigate_to_page(new moodle_url(manage_activities::URL));
    }

    /**
     * @When /^I navigate to the edit perform activities page for activity "([^"]*)"$/
     * @param string $activity_name
     */
    public function i_navigate_to_the_edit_perform_activities_page_for(string $activity_name): void {
        $activity = $this->get_activity_by_name($activity_name);
        $this->navigate_to_page(edit_activity::get_url(['activity_id' => $activity->id]));
    }

    /**
     * @When /^I navigate to the external participants form for user "([^"]*)"$/
     * @param string|null $user_fullname
     */
    public function i_navigate_to_the_external_participant_form_for_user(string $user_fullname = null): void {
        /** @var external_participant $external_participant */
        $external_participant = external_participant::repository()
            ->where('name', $user_fullname)
            ->one();

        if (!$external_participant) {
            $this->fail("External participant with name '{$user_fullname}' not found.");
        }

        $this->navigate_to_page(view_external_participant_activity::get_url(['token' => $external_participant->token]));
    }

    /**
     * @When /^I navigate to the external participants form with the wrong token$/
     */
    public function i_navigate_to_the_external_participant_form_wrong_token(): void {
        $this->navigate_to_page(view_external_participant_activity::get_url(['token' => 'idontexist']));
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is unanswered$/
     * @param string $element_type
     * @param string $question_text
     * @throws ExpectationException
     */
    public function i_should_see_perform_question_is_unanswered(string $element_type, string $question_text): void {
        $this->i_should_see_perform_question_is_answered_with($element_type, $question_text, '');
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is unanswered in print view$/
     * @param string $element_type
     * @param string $question_text
     */
    public function i_should_see_perform_question_is_unanswered_print(string $element_type, string $question_text): void {
        $this->wait_for_pending_js();

        $question = $this->find_question_from_text($question_text, true);
        $is_inline_element_type = in_array($element_type, ['short text', 'long text', 'date picker']);
        if ($is_inline_element_type) {
            $notepad_lines = $question->find('css', self::TUI_NOTEPAD_LINES);
            if ($notepad_lines === null) {
                $this->fail('Question was not unanswered, notepad lines were not present');
            }
        }

        $printed_todo_icon = $question->find('css', self::TUI_PARTICIPANT_CONTENT_PRINT_PRINTED_TODO);
        if ($printed_todo_icon === null) {
            $this->fail('Question was not unanswered, printed todo icon was not present');
        }

        if ($is_inline_element_type) {
            $response_locator = $this->get_response_element_response_locator($element_type);
            $form_response = $question->find('css', $response_locator);
            if ($form_response !== null) {
                $this->fail('Question was not unanswered, it had a response');
            }
        }
    }

    /**

     */

    /**
     * @Then /^activity section "(?P<section_number>\d+)" should exist$/
     * @param int $section_number
     */
    public function section_exists(int $section_number): void {
        $section_selector = sprintf(self::MANAGE_CONTENT_ACTIVITY_SECTION, $section_number);
        $this->execute('behat_general::should_exist', [$section_selector, 'css_element']);
    }

    /**
     * @Then /^activity section "(?P<section_number>\d+)" should not exist$/
     * @param int $section_number
     */
    public function section_not_exists(int $section_number): void {
        $section_selector = sprintf(self::MANAGE_CONTENT_ACTIVITY_SECTION, $section_number);
        $this->execute('behat_general::should_not_exist', [$section_selector, 'css_element']);
    }

    /**
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the activity section should exist$/
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<section_number>\d+)" activity section should exist$/
     *
     * @param string $element
     * @param string $selector_type
     * @param int $section_number
     * @return void
     */
    public function element_in_section_exist(string $element, string $selector_type, int $section_number = 1): void {
        $section_node = $this->get_section_node($section_number);
        $this->find_element_in_container($section_node, $element, $selector_type, true);
    }

    /**
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the activity section should not exist$/
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<section_number>\d+)" activity section should not exist$/
     *
     * @param string $element
     * @param string $selector_type
     * @param int $section_number
     * @return void
     */
    public function element_in_section_no_exist(string $element, string $selector_type, int $section_number = 1): void {
        $section_node = $this->get_section_node($section_number);
        $element_found = $this->find_element_in_container($section_node, $element, $selector_type, false);
        if ($element_found !== null) {
            throw new ExpectationException(
                'The element "' . $element_found . '" in the section '.$section_number.' should not exist but was found.',
                $this->getSession()
            );
        }
    }

    /**
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the activity section$/
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<section_number>\d+)" activity section$/
     *
     * @param string $text
     * @param int $section_number
     * @return void
     */
    public function i_should_see_in_section(string $text, int $section_number = 1): void {
        $section_selector = sprintf(self::MANAGE_CONTENT_ACTIVITY_SECTION, $section_number);
        $this->execute('behat_general::assert_element_contains_text',
            [$text, $section_selector, 'css_element']
        );
    }

    /**
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in the activity section$/
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<section_number>\d+)" activity section$/
     *
     * @param string $text
     * @param int $section_number
     * @return void
     */
    public function i_should_not_see_in_section(string $text, int $section_number = 1): void {
        $section_selector = sprintf(self::MANAGE_CONTENT_ACTIVITY_SECTION, $section_number);
        $this->execute('behat_general::assert_element_not_contains_text',
            [$text, $section_selector, 'css_element']
        );
    }

    /**
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the activity section$/
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<section_number>\d+)" activity section$/
     *
     * @param string $element
     * @param string $selector_type
     * @param int $section_number
     * @return void
     */
    public function i_click_on_css_element_in_section(string $element, string $selector_type, int $section_number = 1): void {
        behat_hooks::set_step_readonly(false);

        $section_node = $this->get_section_node($section_number);
        $element_found = $this->find_element_in_container($section_node, $element, $selector_type);
        $this->click_node($element_found);
    }

    /**
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<container_element_string>(?:[^"]|\\")*)" "(?P<container_selector_string>(?:[^"]|\\")*)" of the activity section$/
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" in the "(?P<container_element_string>(?:[^"]|\\")*)" "(?P<container_selector_string>(?:[^"]|\\")*)" of the "(?P<section_number>\d+)" activity section$/
     *
     * @param string $element
     * @param string $selector_type
     * @param string $container_element
     * @param string $container_selector
     * @param int $section_number
     * @return void
     */
    public function i_click_on_the_in_the_section(
        string $element,
        string $selector_type,
        string $container_element,
        string $container_selector,
        int $section_number = 1
    ): void {
        behat_hooks::set_step_readonly(false);

        $section_node = $this->get_section_node($section_number);
        $container_found = $this->find_element_in_container($section_node, $container_element, $container_selector);
        $element_found = $this->find_element_in_container($container_found, $element, $selector_type);
        $this->click_node($element_found);
    }

    /**
     * @When /^I set the title of activity section "(?P<section_number>\d+)" to "([^"]*)"$/
     * @When /^I set the title of activity section "(?P<section_number>\d+)" to '([^']*)'$/
     *
     * @param int $section_number
     * @param string $section_title
     * @return void
     */
    public function i_set_the_title_of_section_to(int $section_number, string $section_title): void {
        behat_hooks::set_step_readonly(false);

        $section_node = $this->get_section_node($section_number, true);

        $editing_node = $section_node->find('css', '.tui-performActivitySection--editing');
        if ($editing_node === null) {
            throw new ExpectationException("Section {$section_number} is not in edit mode", $this->getSession());
        }

        $editing_node->find('css', 'input')->setValue($section_title);
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is saved with options "([^"]*)"$/
     * @param string $type
     * @param string $question_text
     * @param string $question_options
     */
    public function i_should_see_multiple_answers_question_is_saved_with_options(
        string $type,
        string $question_text,
        string $question_options
    ): void {
        /** @var behat_mod_perform $behat_mod_perform */
        $locator = $type === 'checkbox' ? '.tui-checkbox__label' : '.tui-radio__label';
        $question = $this->find_admin_question_from_text($question_text);
        $options = $question->findAll('css', $locator);
        $expected_options = explode(',', $question_options);
        $expected_options = array_map('trim', $expected_options);

        $actual_options = [];
        foreach ($options as $option) {
            $actual_options[] = trim($option->getText());
        }
        if ($expected_options !== $actual_options) {
            throw new ExpectationException("Question {$question_text} not found with options {$question_options}", $this->getSession());
        }
    }

    /**
     * Get the node for the given activity section.
     *
     * @param int $section_number
     * @param bool $required
     * @return NodeElement|null
     */
    private function get_section_node(int $section_number, bool $required = true): ?NodeElement {
        $section_selector = sprintf(self::MANAGE_CONTENT_ACTIVITY_SECTION, $section_number);

        // Transforming from steps definitions selector/locator format to Mink format and getting the NodeElement.
        $section_node = $this->get_selected_node('css_element', $section_selector);

        if ($required && $section_node === null) {
            throw new ExpectationException(
                'The element "' . $section_node . '" container element could not be found',
                $this->getSession()
            );
        }

        return $section_node;
    }

    /**
     * Find element in given container
     *
     * @param NodeElement $container
     * @param string $element
     * @param string $selector_type
     * @param bool $required
     * @return NodeElement|null
     */
    private function find_element_in_container(
        NodeElement $container,
        string $element,
        string $selector_type,
        bool $required = true
    ): ?NodeElement {
        [$element_selector, $element_locator] = $this->transform_selector($selector_type, $element);

        $element_found = $container->find($element_selector, $element_locator);

        if ($required && $element_found === null) {
            throw new ExpectationException(
                'The element "' . $element . '" in the type '.$selector_type.' could not be found.',
                $this->getSession()
            );
        }

        return $element_found;
    }

    /**
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<summary_item>(?:[^"]|\\")*)" element summary of the activity section$/
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<summary_item>(?:[^"]|\\")*)" element summary of "(?P<section_number>\d+)" activity section$/
     *
     * @param int $count
     * @param string $summary_item
     * @param int $section_number
     * @return void
     */
    public function i_should_see_in_section_summary(int $count, string $summary_item, int $section_number = 1): void {
        $summary_map = [
            'required' => 1,
            'optional' => 2,
            'other' => 3,
        ];
        $css_selector = sprintf(
            self::MANAGE_CONTENT_ACTIVITY_SECTION . ' ' . self::MANAGE_CONTENT_ACTIVITY_SECTION_CONTENT_SUMMARY,
            $section_number, $summary_map[$summary_item]
        );
        $this->execute('behat_general::assert_element_contains_text',
            [$count, $css_selector, 'css_element']
        );
    }

    /**
     * @Then /^I should see perform "([^"]*)" question is "([^"]*)"$/
     * @param string $question_text
     * @param string $required
     *
     * @throws ExpectationException
     */
    public function i_should_see_perform_question_is_required(string $question_text, string $required): void {
        $is_required = $required == 'required';
        $this->find_required_question_from_text($question_text, $is_required);
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is answered with "([^"]*)"(| in print view)$/
     * @param string $element_type
     * @param string $question_text
     * @param string $expected_answer_text
     * @param string $in_print_view
     */
    public function i_should_see_perform_question_is_answered_with(
        string $element_type,
        string $question_text,
        string $expected_answer_text,
        string $in_print_view = ''
    ): void {
        $this->wait_for_pending_js();

        $response = $this->find_question_response($element_type, $question_text, $in_print_view === ' in print view');

        switch ($element_type) {
            case 'multi choice multi':
            case 'multi choice single':
            case 'custom rating scale':
                $label_to_check = null;
                foreach ($response->findAll('css', 'input') as $input) {
                    if ($input->isChecked()) {
                        $label_to_check = $input->getParent()->find('css', 'label');
                        break;
                    }
                }

                if ($label_to_check !== null) {
                    $actual_answer_text = $label_to_check->getText();
                } else {
                    $this->fail("No selected option found for '{$element_type}' question");
                }
                break;
            case 'numeric rating scale':
                $actual_answer_text = $response->getValue();
                break;
            default:
                $actual_answer_text = $response->getText();
        }

        if ($expected_answer_text !== trim($actual_answer_text)) {
            throw new ExpectationException(
                "Expected answer to be \"{$expected_answer_text}\"  found \"{$actual_answer_text}\"",
                $this->getSession()
            );
        }
    }

    /**
     * @Then /^I should (|not )see "([^"]*)" in the perform activity question "([^"]*)"$/
     * @param string $should_or_should_not
     * @param string $expected_text
     * @param string $question_text
     */
    public function i_should_see_in_the_perform_activity_question(
        string $should_or_should_not,
        string $expected_text,
        string $question_text
    ): void {
        $should_contain_text = $should_or_should_not !== 'not ';
        $question = $this->find_question_from_text($question_text);

        if (!$should_contain_text && strpos($question->getText(), $expected_text) !== false) {
            throw new ExpectationException("Text '$expected_text' was found in the question", $this->getSession());
        }

        if ($should_contain_text && strpos($question->getText(), $expected_text) === false) {
            throw new ExpectationException("Text '$expected_text' was not found in the question", $this->getSession());
        }
    }

    /**
     * @Then /^I should (|not )see validation error "([^"]*)" in the perform activity question "([^"]*)"$/
     * @param string $should_or_should_not
     * @param string $expected_text
     * @param string $question_text
     */
    public function i_should_see_validation_error_in_the_perform_activity_question(
        string $should_or_should_not,
        string $expected_text,
        string $question_text
    ): void {
        $should_contain_text = $should_or_should_not !== 'not ';
        $question = $this->find_question_from_text($question_text);

        $errors = $question->findAll('css', '.tui-formFieldError');

        $found = false;
        foreach ($errors as $error) {
            if (strpos($error->getText(), $expected_text) !== false) {
                $found = true;
                break;
            }
        }

        if ($should_contain_text && !$found) {
            new ExpectationException("Validation error '$expected_text' was found in the question", $this->getSession());
        }

        if (!$should_contain_text && $found) {
            new ExpectationException("Validation error '$expected_text' was not found in the question", $this->getSession());
        }
    }

    /**
     * @Then /^I should see perform activity relationship to user "([^"]*)" as an "([(internal)|(external)]*)" participant$/
     * @Then /^I should see perform activity relationship to user "([^"]*)"$/
     *
     * @param string $expected_relation
     * @param string $participant_source
     */
    public function i_should_see_perform_activity_relationship_to_user(string $expected_relation, string $participant_source = 'internal'): void {
        $locator = $participant_source === 'internal'
            ? self::PERFORM_ACTIVITY_GENERAL_INFORMATION_RELATIONSHIP_LOCATOR
            : self::PERFORM_ACTIVITY_YOUR_RELATIONSHIP_VALUE_EXTERNAL;

        $this->execute('behat_general::assert_element_contains_text',
            [$expected_relation, $locator, 'css_element']
        );
    }

    /**
     * @Then /^I should (|not )see "([^"]*)" in perform activity print section "([0-9]*)"$/
     *
     * @param string $should_or_should_not
     * @param string $expected_text
     * @param int $section_number
     */
    public function i_should_see_in_print_section(string $should_or_should_not, string $expected_text, int $section_number): void {
        $should_contain_text = $should_or_should_not !== 'not ';

        /** @var NodeElement $node */
        $node = $this->find_all('css', self::PERFORM_ACTIVITY_PRINT_SECTION_LOCATOR)[$section_number - 1];

        if (!$should_contain_text && strpos($node->getText(), $expected_text) !== false) {
            throw new ExpectationException("Text '$expected_text' was found in the section", $this->getSession());
        }

        if ($should_contain_text && strpos($node->getText(), $expected_text) === false) {
            throw new ExpectationException("Text '$expected_text' was not found in the section", $this->getSession());
        }
    }

    /**
     * @When /^I click show others responses$/
     * @readonly
     */
    public function i_click_show_others_responses(): void {
        behat_hooks::set_step_readonly(false);

        $this->find('css', self::PERFORM_SHOW_OTHERS_RESPONSES_LOCATOR)->click();
    }

    /**
     * @Then /^I should see that show others responses is toggled "([(on)|(off)]*)"$/
     * @param string|bool $expected_state
     * @throws ExpectationException
     */
    public function i_should_see_that_show_others_responses_is(string $expected_state): void {
        if ($expected_state == 'on') {
            $checked = $this->find('css', self::PERFORM_SHOW_OTHERS_RESPONSES_LOCATOR)->hasAttribute('aria-pressed');

            if (!$checked) {
                throw new ExpectationException('Others responses toggle was not on', $this->getSession());
            }
        }
    }

    /**
     * @Then /^I should not see the show others responses toggle$/
     */
    public function i_should_not_see_the_show_others_responses_toggle(): void {
        try {
            $found = $this->find('css', self::PERFORM_SHOW_OTHERS_RESPONSES_LOCATOR, false, false, 0.1);

            if ($found !== null) {
                $this->fail('Show other responses toggle was found');
            }
        } catch (ElementNotFoundException $e) {
            // Element was not found.
        }
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is unanswered by "([^"]*)"$/
     * @param $element_type
     * @param $question_text
     * @param $expected_relation
     */
    public function i_should_see_perform_question_is_unanswered_by(
        string $element_type,
        string $question_text,
        string $expected_relation
    ): void {
        $this->i_should_see_perform_question_is_answered_by_with(
            $element_type,
            $question_text,
            $expected_relation,
            null
        );
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is answered by the current user with "([^"]*)"$/
     * @param $element_type
     * @param $question_text
     * @param $expected_answer_text
     */
    public function i_should_see_perform_question_is_answered_by_the_current_user_with(
        string $element_type,
        string $question_text,
        ?string $expected_answer_text
    ): void {
        $question = $this->find_question_from_text($question_text);
        $response_container = $question->find('css', self::PERFORM_ELEMENT_RESPONSE_CONTAINER_LOCATOR);

        $responses = $this->find_question_other_responses_by_element($element_type, $response_container)[0];

        if ($expected_answer_text === trim($responses->getText())) {
            return;
        }

        throw new ExpectationException(
            "Could not find expected other response by the current user with {$expected_answer_text}",
            $this->getSession()
        );
    }

    /**
     * @Then /^I should see perform "([^"]*)" question "([^"]*)" is answered by "([^"]*)" with "([^"]*)"$/
     * @param $element_type
     * @param $question_text
     * @param $expected_relation
     * @param $expected_answer_text
     */
    public function i_should_see_perform_question_is_answered_by_with(
        string $element_type,
        string $question_text,
        string $expected_relation,
        ?string $expected_answer_text
    ): void {
        $question = $this->find_question_from_text($question_text);
        $relations = $question->findAll('css', self::PERFORM_ELEMENT_OTHER_RESPONSE_RELATION_LOCATOR);

        $found_relationship_answer = null;
        foreach ($relations as $relation) {
            // Don't work with substrings here to avoid "Manager" matching "Manager's manager".
            if (trim($relation->getText()) === $expected_relation . ' response') {
                $found_relationship_answer = $relation;
                break;
            }
        }

        if ($expected_answer_text === null && $found_relationship_answer !== null) {
            return;
        }

        $relationship_container = $found_relationship_answer->getParent()->getParent();

        $other_responses = $this->find_question_other_responses_by_element($element_type, $relationship_container);
        foreach ($other_responses as $other_response) {
            if ($expected_answer_text === trim($other_response->getText())) {
                return;
            }
        }

        throw new ExpectationException(
            "Could not find expected other response by {$expected_relation} with {$expected_answer_text}",
            $this->getSession()
        );
    }

    /**
     * @Then /^I should see "([^"]*)" has the validation error "([^"]*)"$/
     * @param string $question_text
     * @param string $expected_validation_error
     * @throws ExpectationException
     */
    public function i_should_see_perform_question_has_the_validation_error(
        string $question_text,
        string $expected_validation_error
    ): void {
        $question = $this->find_question_from_text($question_text);

        $validation_errors = $question->findAll('css', self::PERFORM_ELEMENT_VALIDATION_ERROR_LOCATOR);

        foreach ($validation_errors as $error) {
            if ($expected_validation_error === trim($error->getText())) {
                return;
            }
        }

        throw new ExpectationException("Could not find validation error {$expected_validation_error}", $this->getSession());
    }

    /**
     * @Then /^I should see "([^"]*)" has no validation errors$/
     * @param string $question_text
     * @throws ExpectationException
     */
    public function i_should_see_perform_question_has_no_validation_errors(string $question_text): void {
        $question = $this->find_question_from_text($question_text);

        $validation_errors = $question->findAll('css', self::PERFORM_ELEMENT_VALIDATION_ERROR_LOCATOR);

        if (count($validation_errors) > 0) {
            throw new ExpectationException(
                "Expected to not find any validation errors, found  {$validation_errors[0]->getText()}",
                $this->getSession()
            );
        }
    }

    /**
     * @When /^I answer "([^"]*)" question "([^"]*)" with "([^"]*)"$/
     * @param string $element_type
     * @param string $question_text
     * @param string $new_answer
     */
    public function i_answer_question_with(
        string $element_type,
        string $question_text,
        string $new_answer
    ): void {
        behat_hooks::set_step_readonly(false);

        $this->wait_for_pending_js();

        // Please note setting the value on the actual range (using setValue()), does not work.
        if ($element_type === 'numeric rating scale') {
            $element_type .= ':number';
        }

        $response = $this->find_question_response($element_type, $question_text);

        /** @var behat_totara_tui $behat_totara_tui */
        $behat_totara_tui = behat_context_helper::get('behat_totara_tui');

        switch ($element_type) {
            case 'date picker':
                $name = $response->getAttribute('name');
                $behat_totara_tui->i_set_the_tui_date_selector_to($name, $new_answer);
                break;
            case 'multi choice single':
            case 'custom rating scale':
            case 'multi choice multi':
                $element_to_click = null;
                foreach ($response->findAll('css', 'label') as $label) {
                    if (trim($label->getText()) === $new_answer) {
                        $element_to_click = $label;
                        break;
                    }
                }

                if ($element_to_click !== null) {
                    $element_to_click->click();
                } else {
                    $this->fail("No '{$new_answer}' option found for '{$element_type}' question");
                }
                break;
            case 'long text':
                $this->execute(
                    'behat_weka::i_set_the_weka_editor_with_css_to',
                    [self::LONG_TEXT_RESPONSE_LOCATOR, $new_answer]
                );
                break;
            default:
                $response->setValue($new_answer);
        }
    }

    /**
     * @Given /^I should see "([^"]*)" as answer "([0-9]*|any)" in the anonymous responses group for question "([^"]*)"$/
     * @param string $expected_answer
     * @param string $response_number
     * @param string $question_text
     */
    public function i_should_see_as_answer_in_the_anonymous_responses_group_for_question(
        string $expected_answer,
        string $response_number,
        string $question_text
    ): void {
        $question = $this->find_question_from_text($question_text);

        $anonymous_responses = $question->findAll('css', self::TUI_OTHER_PARTICIPANT_RESPONSES_ANONYMOUS_RESPONSE_PARTICIPANT_LOCATOR);

        if (is_number($response_number)) {
            $response_number = (int)$response_number;
            if ($response_number < 1) {
                $this->fail("Invalid response number {$response_number}. Expected 1 or above");
            }

            $actual_response = $anonymous_responses[(int)$response_number - 1];
            $actual_answer_text = trim($actual_response->getText());

            if ($actual_answer_text !== $expected_answer) {
                $this->fail("Expected response \"{$response_number}\" to be \"{$expected_answer}\", but found \"{$actual_answer_text}\"");
            }
        } else if ($response_number === 'any') {
            $fnd = false;
            foreach ($anonymous_responses as $response) {
                $actual_answer_text = trim($response->getText());
                if ($expected_answer === $actual_answer_text) {
                    $fnd = true;
                    break;
                }
            }

            if (!$fnd) {
                $this->fail("Expected response \"{$expected_answer}\" not found in question \"{$question_text}\"");
            }
        } else {
            $this->fail("Invalid response number \"{$response_number}\". Expected a number or \"any\"");
        }
    }

    /**
     * @When /^I answer "([^"]*)" question "([^"]*)" with "([^"]*)" characters$/
     * @param string $element_type
     * @param string $question_text
     * @param int $character_count
     */
    public function i_answer_question_with_characters(
        string $element_type,
        string $question_text,
        int $character_count
    ): void {
        behat_hooks::set_step_readonly(false);

        $response = $this->find_question_response($element_type, $question_text);

        $new_answer = random_string($character_count);
        $response->setValue($new_answer);
    }

    /**
     * @When /^I add a "([^"]*)" activity content element$/
     * @param string $element_name
     */
    public function i_add_a_custom_element(string $element_name): void {
        behat_hooks::set_step_readonly(false);

        /** @var behat_general $behat_general */
        $behat_general = behat_context_helper::get('behat_general');
        $behat_general->i_click_on("Add element","button");

        /** @var behat_totara_tui $behat_totara_tui */
        $behat_totara_tui = behat_context_helper::get('behat_totara_tui');
        $behat_totara_tui->i_click_on_dropdown_option($element_name);
    }

    /**
     * @When /^I add a "([^"]*)" element from dropdown list$/
     * @param string $element_name
     */
    public function i_add_an_element_from_dropdown_list(string $element_name): void {
        behat_hooks::set_step_readonly(false);

        /** @var behat_totara_tui $behat_totara_tui */
        $behat_totara_tui = behat_context_helper::get('behat_totara_tui');
        $behat_totara_tui->i_click_on_dropdown_option($element_name);
    }

    /**
     * @When /^I (save|cancel saving) the activity content element$/
     * @param string $is_saving
     */
    public function i_save_the_custom_element_settings(string $is_saving): void {
        behat_hooks::set_step_readonly(false);

        $button_text = $is_saving === 'save' ? 'Save' : 'Cancel';

        /** @var behat_general $behat_general */
        $behat_general = behat_context_helper::get('behat_general');
        $behat_general->i_click_on_in_the($button_text, 'button', '.tui-performSectionContent__form', 'css_element');
    }

    /**
     * @When /^I (save|cancel saving) the activity sub element$/
     * @param string $is_saving
     */
    public function i_save_the_sub_element_settings(string $is_saving): void {
        behat_hooks::set_step_readonly(false);

        $button_text = $is_saving === 'save' ? 'Save' : 'Cancel';

        /** @var behat_general $behat_general */
        $behat_general = behat_context_helper::get('behat_general');
        $behat_general->i_click_on_in_the($button_text, 'button', '.tui-performAdminChildElements', 'css_element');
    }

    /**
     * @Then /^I should see "([^"]*)" in the "([^"]*)" "([^"]*)" of perform admin element "([^"]*)"$/
     * @param string $expected_text
     * @param string $selector
     * @param string $locator
     * @param string $element_title
     */
    public function i_should_see_in_the_of_perform_admin_element(
        string $expected_text,
        string $locator,
        string $selector,
        string $element_title
    ): void {
        $question = $this->find_admin_question_from_text($element_title);

        $this->i_should_see_in_element($expected_text, $locator, $selector, $element_title, $question);
    }

    /**
     * @Then /^I should see "([^"]*)" in the "([^"]*)" "([^"]*)" of perform element participant form for "([^"]*)"$/
     * @param string $expected_text
     * @param string $selector
     * @param string $locator
     * @param string $element_title
     */
    public function i_should_see_in_the_of_perform_element_participation_form_for(
        string $expected_text,
        string $locator,
        string $selector,
        string $element_title
    ): void {
        $question = $this->find_question_from_text($element_title);

        $this->i_should_see_in_element($expected_text, $locator, $selector, $element_title, $question);
    }

    private function i_should_see_in_element(
        string $expected_text,
        string $locator,
        string $selector,
        string $element_title,
        NodeElement $question
    ): void {
        /** @var behat_general $behat_general */
        $behat_general = behat_context_helper::get('behat_general');

        [$selector, $locator] = $behat_general->transform_selector($selector, $locator);

        $element = $question->find($selector, $locator);

        if ($element === null) {
            $this->fail(sprintf("\"%s\" element \"%s\" not found in perform element: \"%s\"", $selector, $locator, $element_title));
        }

        if (strpos($element->getText(), $expected_text) === false) {
            $this->fail(sprintf("\"%s\" did not contain the text: \"%s\"", $element_title, $expected_text));
        }
    }

    /**
     * @param string $action_type
     * @param string $question_text
     * @return NodeElement|null
     */
    private function find_action_for_question(string $action_type, string $question_text): ?NodeElement {
        $question = $this->find_admin_question_from_text($question_text);

        if ($question === null) {
            return null;
        }

        $action_button = $question
            ->find('css', self::QUESTION_DISPLAY_LOCATOR)
            ->find('css', 'button[title*="' . $action_type . '"]');

        if ($action_button) {
            return $action_button;
        }

        return null;
    }

    /**
     * @When /^I click on the (Edit element|Actions|Add sub-element) button for question "([^"]*)"$/
     * @param string $action_type
     * @param string $question_text
     */
    public function i_click_on_the_action_for_question(string $action_type, string $question_text): void {
        behat_hooks::set_step_readonly(false);

        $action = $this->find_action_for_question($action_type, $question_text);

        if (!$action) {
            throw new ExpectationException(
                "Action {$action_type} for question with text {$question_text} not found", $this->getSession()
            );
        }

        $action->click();
    }

    /**
     * @Then /^I (should|should not) see the (Reporting ID|Edit element|Delete element) action for question "([^"]*)"$/
     * @param string $should_or_not
     * @param string $action_type
     * @param string $question_text
     * @throws ExpectationException
     */
    public function i_should_see_action_for_question(string $should_or_not, string $action_type, string $question_text): void {
        behat_hooks::set_step_readonly(true);

        $should_be_visible = $should_or_not === 'should';
        $action = $this->find_action_for_question($action_type, $question_text);

        if ($should_be_visible && !$action) {
            throw new ExpectationException(
                "Action {$action_type} for question with text {$question_text} not found", $this->getSession()
            );
        }

        if (!$should_be_visible && $action) {
            throw new ExpectationException(
                "Action {$action_type} for question with text {$question_text} was visible when it shouldn't have",
                $this->getSession()
            );
        }
    }

    /**
     * @Then /^I should see drag icon visible in the question "([^"]*)"$/
     * @param string $question_text
     */
    public function i_should_see_drag_icon_visible(string $question_text) {
        $element = $this->find_element_drag_wrapper_from_text($question_text);

        $element->click();
        $move_icon = $element->find('css', self::QUESTION_DRAG_MOVE_ICON_LOCATOR);

        if (!$move_icon->isVisible()) {
            throw new ExpectationException("move icon should be visible", $this->getSession());
        }
    }

    /**
     * @Then /^I should not see drag icon visible in the question "([^"]*)"$/
     * @param string $question_text
     */
    public function i_should_not_see_drag_icon_visible(string $question_text) {
        $element = $this->find_element_drag_wrapper_from_text($question_text);

        $element->click();
        $move_icon = $element->find('css', self::QUESTION_DRAG_MOVE_ICON_LOCATOR);

        if ($move_icon) {
            throw new ExpectationException("move icon should not be visible", $this->getSession());
        }
    }

    /**
     * @param string $question_text
     *
     * @return NodeElement
     * @throws ExpectationException
     */
    private function find_element_drag_wrapper_from_text(string $question_text): NodeElement {
        $drag_items = $this->find_all('css', self::QUESTION_DRAG_ITEM_LOCATOR);

        foreach ($drag_items as $question) {
            $question_title = $question->find('css', self::EDIT_QUESTION_DISPLAY_TITLE_LOCATOR);

            $actual_title = trim(str_replace('*', '', $question_title->getText()));

            if ($actual_title === $question_text) {
                return $question;
            }
        }
        throw new ExpectationException("Question not found with text {$question_text}", $this->getSession());
    }

    /**
     * @When /^I close popovers$/
     */
    public function close_all_popovers() {
        behat_hooks::set_step_readonly(false);

        /** @var NodeElement[] $popover_close_buttons */
        $popover_close_buttons = $this->find_all('css', '.tui-popoverFrame__close');
        foreach ($popover_close_buttons as $close_button) {
            if ($close_button->isVisible()) {
                $close_button->click();
            }
        }
    }

    /**
     * @Given /^I navigate to manage perform activity content page$/
     * @Given /^I navigate to manage perform activity content page of "(?P<section_number>\d+)" activity section$/
     * @param int $section_number
     */
    public function i_navigate_to_manage_perform_activity_content_page(int $section_number = 1): void {
        behat_hooks::set_step_readonly(false);

        $behat_general = behat_context_helper::get('behat_general');
        $behat_general->i_click_on_in_the(
            "Content",
            "link",
            self::TUI_TAB_ELEMENT,
            "css_element"
        );

        $this->wait_for_pending_js();

        $section_node = $this->get_section_node($section_number);
        $element_node = $this->find_element_in_container($section_node, "Edit content elements", "link_or_button");
        $this->click_node($element_node);
    }

    /**
     * @When /^I save the activity schedule$/
     */
    public function i_save_the_activity_schedule(): void {
        behat_hooks::set_step_readonly(false);

        $this->find('css', self::SCHEDULE_SAVE_LOCATOR)->click();
    }

    /**
     * @param string $element_type
     * @param string $question_text
     * @param bool $is_print
     * @return NodeElement
     */
    private function find_question_response(string $element_type, string $question_text, bool $is_print = false): NodeElement {
        $question = $this->find_question_from_text($question_text, $is_print);

        $response_locator = $this->get_response_element_response_locator($element_type);

        return $question->find('css', $response_locator);
    }

    /**
     * @param string $element_type
     * @param NodeElement $other_responses
     * @return NodeElement[]
     */
    private function find_question_other_responses_by_element(string $element_type, NodeElement $other_responses): array {
        $map = [
            'long text' => self::PARTICIPANT_FORM_HTML_VIEW_ONLY_RESPONSE_LOCATOR,
        ];

        $locator =  $map[$element_type] ?? self::PARTICIPANT_FORM_RESPONSE_DISPLAY_LOCATOR;
        $locator .= ', ' . self::PARTICIPANT_FORM_NO_PARTICIPANT;

        return $other_responses->findAll('css', $locator);
    }

    private function get_response_element_response_locator(string $element_type): string {
        $map = [
            'short text' => self::SHORT_TEXT_RESPONSE_LOCATOR,
            'long text' => self::LONG_TEXT_RESPONSE_LOCATOR,
            'date picker' => self::DATE_PICKER_RESPONSE_LOCATOR,
            'multi choice multi' => self::MULTI_CHOICE_MULTI_RESPONSE_LOCATOR,
            'multi choice single' => self::MULTI_CHOICE_SINGLE_RESPONSE_LOCATOR,
            'custom rating scale' => self::CUSTOM_RATING_SCALE_RESPONSE_LOCATOR,
            'numeric rating scale' => self::NUMERIC_RATING_SCALE_RANGE_RESPONSE_LOCATOR,
            'numeric rating scale:number' => self::NUMERIC_RATING_SCALE_NUMBER_RESPONSE_LOCATOR,
        ];

        $locator =  $map[$element_type] ?? null;

        if ($locator === null) {
            throw new ExpectationException("Invalid perform element type {$element_type}", $this->getSession());
        }

        return $locator;
    }

    public function find_question_from_text(string $question_text, bool $is_print = false): NodeElement {
        if ($is_print) {
            $questions_container_locator = self::PERFORM_ELEMENT_PRINT_LOCATOR;
            $question_locator = self::PERFORM_ELEMENT_QUESTION_TEXT_PRINT_LOCATOR;
        } else {
            $questions_container_locator = self::PERFORM_ELEMENT_LOCATOR . ', ' . self::PERFORM_ELEMENT_PRINT_LOCATOR;
            $question_locator = self::PERFORM_ELEMENT_QUESTION_TEXT_LOCATOR . ', ' . self::PERFORM_ELEMENT_QUESTION_TEXT_PRINT_LOCATOR;
        }

        /** @var NodeElement[] $questions */
        $questions = $this->find_all('css', $questions_container_locator);

        foreach ($questions as $question) {
            $found_question = $question->find('css', $question_locator);

            if ($found_question === null) {
                continue;
            }

            $actual_title = trim($found_question->getText());

            foreach ([' optional', ' \(optional\)', ' Required', ' \*'] as $remove_from_end) {
                $actual_title = preg_replace("/{$remove_from_end}\$/", '', $actual_title);
            }

            if ($actual_title === $question_text) {
                return $question;
            }
        }

        throw new ExpectationException("Question not found with text {$question_text}", $this->getSession());
    }

    public function find_admin_question_from_text(string $question_text): NodeElement {
        /** @var NodeElement[] $questions */
        $questions = $this->find_all('css', self::QUESTION_DISPLAY_LOCATOR);

        foreach ($questions as $question) {
            $question_title = $question->find('css', self::EDIT_QUESTION_DISPLAY_TITLE_LOCATOR);

            $actual_title = trim(str_replace('*','', $question_title->getText()));

            if ($actual_title === $question_text) {
                return $question;
            }
        }

        throw new ExpectationException("Question not found with text {$question_text}", $this->getSession());
    }

    private function find_required_question_from_text(string $question_text, bool $is_required): NodeElement {
        /** @var NodeElement[] $questions */

        $question = $this->find_question_from_text($question_text);

        if ($question !== null) {
            if ($is_required) {
                $required_found = $question->find('css', self::PERFORM_ELEMENT_QUESTION_REQUIRED_LOCATOR);
                if ($required_found === null) {
                    $this->fail('Found question but it is not required.');
                }
            } else {
                $required_found = $question->find('css', self::PERFORM_ELEMENT_QUESTION_OPTIONAL_LOCATOR);
                if ($required_found === null) {
                    $this->fail('Found question but it is not optional.');
                }
            }

            return $question;
        }


        throw new ExpectationException("Required Question not found with text {$question_text}", $this->getSession());
    }

    /**
     * @Then /^I should see no perform activity participants$/
     */
    public function i_should_see_no_participants(): void {
        $this->ensure_element_does_not_exist(
            self::MANAGE_CONTENT_PARTICIPANT_NAME_LOCATOR,
            'css_element'
        );
    }

    /**
     * @Then /^I should see "([^"]*)" as the perform activity participants$/
     * @Then /^I should see "([^"]*)" as the perform activity (view-only|responding) participants$/
     * @param $expected_participant_list
     * @param string $group
     * @throws ExpectationException
     */
    public function i_should_see_as_the_participants(
        $expected_participant_list,
        string $group = 'responding'
    ): void {
        $group_container = $this->find_participant_group_container($group);

        /** @var NodeElement[] $rows */
        $rows = $group_container->findAll('css', self::MANAGE_CONTENT_PARTICIPANT_NAME_LOCATOR);

        $expected_participants = explode(',', $expected_participant_list);

        foreach ($expected_participants as $index => $expected_participant) {
            if (trim($rows[$index]->getText()) !== trim($expected_participant)) {
                $this->fail("{$expected_participant} was not found in the {$index} position");
            }
        }
    }

    /**
     * @Then /^the mod perform (responding|view-only) participants popover should match:$/
     * @param TableNode $table
     * @param string $group
     */
    public function the_mod_perform_participants_popover_should_match(TableNode $table, string $group): void {
        $group_container = $this->find_participant_group_container($group);

        foreach ($table->getHash() as $hash) {
            $input = $group_container->find('css', "input[name=\"{$hash['name']}\"]");

            if ($hash['checked'] && !$input->isChecked()) {
                $this->fail("{$hash['name']} did not have the correct checked value");
            }

            if (!$hash['enabled'] && $input->getAttribute('disabled') !== 'disabled') {
                $this->fail("{$hash['name']} did not have the correct enabled value");
            }
        }
    }

    /**
     * @When /^I select "([^"]*)" in the (responding|view-only) participants popover(| then click cancel)$/
     * @param string $participant_list
     * @param string $group
     * @param string $then_click_cancel
     */
    public function i_select_in_the_participants_popover(
        string $participant_list,
        string $group,
        string $then_click_cancel
    ): void {
        $relationships = explode(',', $participant_list);

        $group_container = $this->find_participant_group_container($group);

        foreach ($relationships as $relationship) {
            $relationship = trim($relationship);

            $input = $group_container->find('css', "input[name=\"{$relationship}\"]");
            $input->getParent()->find('css', 'label')->click();
        }

        if ($then_click_cancel) {
            // "Cancel".
            $group_container->find('css', behat_totara_tui::SECONDARY_BTN)->click();
        } else {
            // "Done".
            $group_container->find('css', behat_totara_tui::PRIMARY_BTN)->click();
        }
    }

    /**
     * @When /^I click the add (responding|view-only) participant button$/
     * @When /^I click the add (responding|view-only) participant button in "([^"]*)" activity section$/
     *
     * @param string $responding
     * @param int $section_number
     * @return void
     * @throws ExpectationException
     */
    public function i_click_the_add_participant_button(
        string $responding,
        int $section_number = 1
    ): void {
        behat_hooks::set_step_readonly(false);

        if ($responding === 'responding') {
            $selector = self::MANAGE_CONTENT_ADD_RESPONDING_PARTICIPANTS_BUTTON_LABEL;
        } else {
            $selector = self::MANAGE_CONTENT_ADD_VIEW_ONLY_PARTICIPANTS_BUTTON_LABEL;
        }

        $css_selector = sprintf($selector, $section_number);

        $this->find(
            'css',
            $css_selector
        )->click();
    }

    /**
     * @When /^I should see the add participant button is disabled$/
     * @When /^I should see the add participant button is disabled in "([^"]*)" activity section$/
     *
     * @param int $section_number
     * @return void
     */
    public function i_should_see_the_add_participant_button_is_disabled(int $section_number = 1): void {
        $css_selector = sprintf(self::MANAGE_CONTENT_ADD_RESPONDING_PARTICIPANTS_BUTTON_LABEL, $section_number);
        $this->execute('behat_general::the_element_should_be_disabled',
            [$css_selector, 'css_element']
        );
    }

    /**
     * @When /^I should see the subject instance was created "(?P<date>(?:[^"]|\\")*)" in the "(?P<element>(?:[^"]|\\")*)" "(?P<selector_type>(?:[^"]|\\")*)"$/
     *
     * @param string $date
     * @param string $element
     * @param string $selector_type
     * @return void
     */
    public function i_should_see_the_subject_instance_was_created(string $date, string $element, string $selector_type): void {
        $date_text = sprintf("Created %s", $date);
        $this->execute('behat_general::assert_element_contains_text',
            [$date_text, $element, $selector_type]
        );
    }

    /**
     * @When /^I should see the subject instance should be completed before "(?P<date>(?:[^"]|\\")*)" in the "(?P<element>(?:[^"]|\\")*)" "(?P<selector_type>(?:[^"]|\\")*)"$/
     *
     * @param string $date
     * @param string $element
     * @param string $selector_type
     * @return void
     */
    public function i_should_see_the_subject_instance_is_due(string $date, string $element, string $selector_type): void {
        $date_text = sprintf("Due date: %s", $date);
        $this->execute('behat_general::assert_element_contains_text',
            [$date_text, $element, $selector_type]
        );
    }

    /**
     * @When /^Subject instances for "(?P<track_description>(?:[^"]|\\")*)" track are due "(?P<due_date>(?:[^"]|\\")*)"$/
     *
     * @param string $track_description
     * @param string $due_date
     * @return void
     */
    public function subject_instances_for_track_are_due(string $track_description, string $due_date): void {
        $track = track::repository()
            ->where('description', $track_description)
            ->select('id')
            ->order_by('id')
            ->first_or_fail();

        $user_assignments = track_user_assignment::repository()
            ->where('track_id', $track->id)
            ->select('id')
            ->get();
        subject_instance::repository()
            ->where_in('track_user_assignment_id', $user_assignments->pluck('id'))
            ->update(
                [
                    'due_date' => $due_date
                ]
            );
    }

    /**
     * @When /^I should see the add participant button$/
     * @When /^I should see the add participant button in "([^"]*)" activity section$/
     *
     * @param int $section_number
     * @return void
     */
    public function i_should_see_the_add_participant_button(int $section_number = 1): void {
        $css_selector = sprintf(self::MANAGE_CONTENT_ADD_RESPONDING_PARTICIPANTS_BUTTON_LABEL, $section_number);
        $this->ensure_element_exists(
            $css_selector,
            'css_element'
        );
    }

    /**
     * @When /^I remove "([^"]*)" as a perform activity participant$/
     * @param string $participant_to_remove
     */
    public function i_remove_as_a_participant(string $participant_to_remove): void {
        behat_hooks::set_step_readonly(false);

        /** @var NodeElement[] $rows */
        $rows = $this->find_all('css', self::MANAGE_CONTENT_PARTICIPANT_NAME_LOCATOR);

        foreach ($rows as $participant_row) {
            if (trim($participant_row->getText()) === $participant_to_remove) {
                $participant_row->find('css', sprintf( self::TUI_TRASH_ICON_BUTTON, $participant_to_remove))->click();
                return;
            }
        }

        $this->fail("{$participant_to_remove} participant not found");
    }

    /**
     * @Given /^I should see "([^"]*)" in the perform activity response visibility description$/
     * @param string $expected_text
     * @throws ExpectationException
     */
    public function i_should_see_in_the_perform_activity_response_visibility_description(string $expected_text): void {
        if ($expected_text === '') {
            return;
        }

        $actual_text = $this->find('css', self::RESPONSE_VISIBILITY_DESCRIPTION_LOCATOR)->getText();

        if (strpos($actual_text, $expected_text) === false) {
            $this->fail("{$expected_text} was not found in the response visibility description: Actual text: {$actual_text}");
        }
    }

    /**
     * Display information about the time, the instance creation time and the due date, then pause.
     *
     * @Given /^(?:|I )pause to check the time for perform activity notification$/
     */
    public function i_pause_to_check_the_time_for_perform_activity_notification(): void {
        global $CFG, $DB;
        /** @var moodle_database $DB */
        \behat_hooks::set_step_readonly(true);
        // Pick the first record belonging to the current user.
        // Yes this is not ideal as the user could have more than one instances, but hey it is just convenience.
        // First, we need to extract the current user id from the profile link because $USER->id is not correctly set.
        $el = $this->find('css', '.logininfo a[title="View profile"]');
        if ($el && !empty($href = $el->getAttribute('href')) && preg_match('/id=(\d+)/', $href, $matches)) {
            $userid = $matches[1];
        } else {
            $userid = 0; // user id is unknown
        }
        $record = current($DB->get_records('perform_subject_instance', ['subject_user_id' => $userid], '', '*', 0, 1)) ?: new stdClass();
        $clock = \mod_perform\notification\factory::create_clock();
        $due = !empty($record->due_date) ? $record->due_date : 0;
        $creation = !empty($record->created_at) ? $record->created_at : 0;
        $time = $clock->get_time();
        // Windows don't support ANSI code by default, but with ANSICON.
        $isansicon = getenv('ANSICON');
        $ansi = !(($CFG->ostype === 'WINDOWS') && empty($isansicon));
        $out_a_time = function ($int, $clr, $tm, $info) use ($ansi) {
            $str = userdate($tm, '%b %d %Y %p %I:%M', 99, false, false);
            if ($ansi) {
                return "\033[{$int};{$clr}m{$str}\033[0m  ({$info})\n";
            } else {
                return "{$str}  ({$info})\n";
            }
        };
        // Display the due date, the adjusted time and the instance creation time respectively.
        $current_bias = get_config('mod_perform', 'notification_time_travel') ?: 0;
        $info_time = sprintf('%d day %d hour in the %s', (int)($current_bias / 86400), (int)($current_bias / 3600) % 24, $current_bias > 0 ? 'future' : 'past');
        $tz = ' in ' . \core_date::get_server_timezone();
        $text = $ansi ? "\033[s\n" : "\n";
        $text .= $out_a_time(1, 31, $due ?: -2682315804, $due ? ('due date' . $tz) : 'due date not set');
        $text .= $out_a_time(1, 32, $time, $current_bias ? ($info_time . $tz) : 'time not adjusted');
        $text .= $out_a_time(0, 33, $creation, $creation ? ('instance created' . $tz) : 'instance not created');
        $text .= $ansi ? "\033[4A\033[0m\033[u\033[4B" : '';
        fwrite(STDOUT, $text);
        $this->execute('behat_general::i_pause_scenario_executon');
    }

    /**
     * @Given /^I time travel to "midnight (past|future)" for perform activity notification$/
     * @param string $direction
     */
    public function i_time_travel_to_midnight_for_the_perform_activity_notification(string $direction): void {
        $time = \mod_perform\notification\factory::create_clock()->get_time();
        $midnight = \mod_perform\notification\conditions\after_midnight::get_last_midnight($time);
        if ($direction === 'future') {
            if ($time > $midnight) {
                $midnight += DAYSECS;
            }
        } else if ($direction === 'past') {
            // do nothing
        } else {
            $this->fail('direction must be future or past');
        }
        \mod_perform\notification\factory::create_clock_with_time_offset($midnight - $time);
    }

    /**
     * @Given /^I time travel to "(\d+) (day|days|hour|hours) (past|future)" for perform activity notification$/
     * @param string $time
     * @param string $unit
     * @param string $direction
     */
    public function i_time_travel_to_for_the_perform_activity_notification(string $time, string $unit, string $direction): void {
        $bias = (int)$time;
        if (!is_number($time) || $bias === 0) {
            $this->fail('time must be an integer');
        }
        if ($unit === 'days' || $unit === 'day') {
            $bias *= DAYSECS;
        } else if ($unit === 'hours' || $unit === 'hour') {
            $bias *= HOURSECS;
        } else {
            $this->fail('unit must be days or hours');
        }
        if ($direction === 'future') {
            // do nothing
        } else if ($direction === 'past') {
            $bias = -$bias;
        } else {
            $this->fail('direction must be future or past');
        }
        // create_clock_with_time_offset stores $bias in the database for further tasks.
        factory::create_clock_with_time_offset($bias);
        $this->execute('behat_tool_task::i_run_the_scheduled_task', [check_notification_trigger_task::class]);
    }

    /**
     * @When /^I navigate to the perform manage participation subject instances report for activity "([^"]*)"$/
     * @param string $activity_name
     */
    public function i_navigate_to_the_perform_manage_participation_subject_instances_report_for_activity(
        string $activity_name
    ): void {
        $activity = $this->get_activity_by_name($activity_name);

        $this->navigate_to_page(manage_participation::get_url(
            ['activity_id' => $activity->id]
        ));
    }

    /**
     * @Given /^I should see "([^"]*)" in the "([^"]*)" line of the perform activities instance info card$/
     * @param string $expected_value
     * @param string $label_text
     * @throws ExpectationException
     */
    public function i_should_see_in_the_line_of_the_perform_activities_instance_info_card(
        string $expected_value,
        string $label_text
    ): void {
        $labels = $this->find_all('css', self::INSTANCE_INFO_CARD_LABEL_LOCATOR);

        $value_index = null;
        foreach ($labels as $i => $label) {
            if (trim($label->getText()) === $label_text) {
                $value_index = $i;
                break;
            }
        }

        if ($value_index === null) {
            $this->fail("Label not found with the text: {$label_text}");
        }

        $value = $this->find_all('css', self::INSTANCE_INFO_CARD_VALUE_LOCATOR)[$value_index];

        $actual_text = trim($value->getText());

        if ($actual_text !== $expected_value) {
            $this->fail("'{$label_text}' value was not '{$expected_value}', found '{$actual_text}'");
        }
    }

    /**
     * @Given /^I should see today's date in the "([^"]*)" line of the perform activities instance info card$/
     * @param string $label_text
     */
    public function i_should_see_todays_date_in_the_line_of_the_perform_activities_instance_info_card(string $label_text) {
        $today_date_formatted = (new DateTime())->format('j F Y');

        $this->i_should_see_in_the_line_of_the_perform_activities_instance_info_card($today_date_formatted, $label_text);
    }

    /**
     * @Given /^I add one of every element type in the mod perform form builder (and make them required)$/
     * @Given /^I add one of every element type in the mod perform form builder$/
     * @param bool $required
     */
    public function i_add_one_of_every_element_type_in_the_mod_perform_form_builder(bool $required = false): void {
        foreach ($this->get_form_builder_element_add_buttons() as $i => $element_add_button) {
            $this->execute('behat_general::i_click_on', ['Add element', 'link_or_button']);

            if (!$element_add_button->isVisible()) {
                $element_add_button->focus(); // Ensure we scroll the element option into view.
            }

            $element_add_button->click();

            $this->wait_for_pending_js();

            $this->fill_last_element_settings_in_form_builder(trim($element_add_button->getHtml()), $required);

            $this->wait_for_pending_js();
        }
    }

    /**
     * @return NodeElement[]
     */
    private function get_form_builder_element_add_buttons(): array {
        $this->execute('behat_general::i_click_on', ['Add element', 'link_or_button']);
        $element_add_buttons = $this->find_all('css', self::FORM_BUILDER_ADD_ELEMENT_BUTTONS);
        $this->execute('behat_general::i_click_on', ['Add element', 'link_or_button']);

        return $element_add_buttons;
    }

    private function fill_last_element_settings_in_form_builder(string $title, bool $required): void {
        $element_setting_containers = $this->find_all('css', self::ADMIN_FORM);

        /** @var NodeElement $element_setting_container */
        $element_setting_container = end($element_setting_containers);

        $element_title = $element_setting_container->find('css', self::ADMIN_FORM_TITLE_INPUT);
        $element_title->setValue($title);

        switch ($title) {
            case 'Multiple choice: Multi-select':
                $this->fill_multi_choice_multi_admin_form_settings($element_setting_container, $required);
                break;
            case 'Rating scale: Numeric':
                $this->fill_numeric_rating_scale_admin_form_settings($element_setting_container);
                break;
            case 'Static content':
                $this->fill_static_content_admin_form_settings();
                break;
            case 'Date picker':
                break;
            case 'Response aggregation':
                // This must come AFTER (in terms of order, from calling method) the rating scale elements, so we can use them as source elements.
                $this->fill_aggregation_admin_form_settings();
                break;
            case 'Response redisplay':
                // This must come AFTER (in terms of order, from calling method) any other element, so we can use them one as a  source element.
                $this->fill_redisplay_admin_form_settings();
                break;
            case 'Review items':
                $this->fill_linked_review_admin_form_settings($element_setting_container);
                break;
            default:
                $this->fill_element_admin_form_settings($element_setting_container);
        }

        if ($required) {
            $response_required_input = $element_setting_container->find('css', self::ADMIN_FORM_RESPONSE_REQUIRED);

            if ($response_required_input !== null) {
                $response_required_input->getParent()->find('css', 'label')->click();
            }
        }

        $element_setting_container->find('css', self::ADMIN_FORM_DONE_BUTTON)->click();
    }

    private function fill_element_admin_form_settings(NodeElement $element_setting_container): void {
        $inputs = $element_setting_container->findAll('css', 'input');

        /** @var NodeElement $input */
        foreach ($inputs as $i => $input) {
            // Don't fill in title again.
            if ($input->getAttribute('name') === self::FORM_BUILDER_RAW_TITLE_NAME) {
                continue;
            }

            $type = $input->getAttribute('type');

            if (in_array($type, ['text', 'number'], true)) {
                $input->focus();
                $input->setValue($i + 1);
            }
        }
    }

    private function fill_linked_review_admin_form_settings(NodeElement $settings_container): void {
        $table_node = new TableNode([
            ['content_type', 'Competencies']
        ]);

        $selection_relationships = $settings_container->find('css', 'input[name="selection_relationships"]');
        $selection_relationships->getParent()->find('css', 'label')->click();

        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', ['data' => $table_node]);
        $this->execute('behat_general::i_wait_for_pending_js');
    }

    private function fill_multi_choice_multi_admin_form_settings(NodeElement $settings_container, bool $required): void {
        $answer_0 = $settings_container->find('css', 'input[name="options[0][value]"]');
        $answer_0->focus();
        $answer_0->setValue('Choice 0');

        $settings_container->find('css', '[title=Add]')->click();

        $answer_1 = $settings_container->find('css', 'input[name="options[1][value]"]');
        $answer_1->focus();
        $answer_1->setValue('Choice 1');

        if ($required) {
            $min = $settings_container->find('css', 'input[name="min"]');
            $min->focus();
            $min->setValue('1');
        }
    }

    private function fill_numeric_rating_scale_admin_form_settings(NodeElement $settings_container): void {
        $low_value = $settings_container->find('css', 'input[name=lowValue]');
        $low_value->focus();
        $low_value->setValue('1');

        $high_value = $settings_container->find('css', 'input[name=highValue]');
        $high_value->focus();
        $high_value->setValue('3');

        $default_value = $settings_container->find('css', 'input[name=defaultValue]');
        $default_value->focus();
        $default_value->setValue('2');
    }

    private function fill_static_content_admin_form_settings(): void {
        $this->execute('behat_weka::i_set_the_weka_editor_with_css_to', [self::ADMIN_FORM_STATIC_CONTENT_WEKA, 'static content']);
    }

    private function fill_aggregation_admin_form_settings(): void {
        /** @var behat_forms $behat_forms */
        $behat_forms = behat_context_helper::get('behat_forms');

        /** @var behat_totara_tui $behat_totara_tui */
        $behat_totara_tui = behat_context_helper::get('behat_totara_tui');

        $behat_forms->i_set_the_following_fields_to_these_values(new TableNode([
            ['sourceSectionElementIds[0][value]', 'Rating scale: Custom'],
            ['sourceSectionElementIds[1][value]', 'Rating scale: Numeric'],
        ]));

        $behat_totara_tui->i_click_the_tui_checkbox_in_the_group( 'average', 'calculations');

        $behat_forms->i_set_the_following_fields_to_these_values(new TableNode([
            ['excludedValues[0][value]', '0'],
        ]));
    }

    private function fill_redisplay_admin_form_settings(): void {
        /** @var behat_forms $behat_forms */
        $behat_forms = behat_context_helper::get('behat_forms');

        $behat_forms->i_set_the_following_fields_to_these_values(new TableNode([
            ['rawTitle', 'Previous response'],
            ['activityId', 'One of every element (Current activity)'],
        ]));

        $this->wait_for_pending_js();

        $behat_forms->i_set_the_following_fields_to_these_values(new TableNode([
            [redisplay::SOURCE_SECTION_ELEMENT_ID, 'Text: Long response (Text: Long response)'],
        ]));
    }

    /**
     * @Given /^I navigate to the mod perform response data report for "([^"]*)" activity$/
     * @param string $activity_name
     */
    public function i_navigate_to_the_mod_perform_response_data_report_for_activity(string $activity_name): void {
        $activity = $this->get_activity_by_name($activity_name);

        $url = \mod_perform\controllers\reporting\performance\activity::get_url(['activity_id' => $activity->id]);

        $this->navigate_to_page($url);
    }

    /**
     * @Given /^I navigate to the mod perform subject instance report for user "([^"]*)"$/
     * @param string $user_name
     */
    public function i_navigate_to_the_mod_perform_subject_instance_report_for_user(string $user_name): void {
        $user = $this->get_user_by_username($user_name);

        $url = \mod_perform\controllers\reporting\performance\user::get_url(['subject_user_id' => $user->id]);

        $this->navigate_to_page($url);
    }


    /**
     * @When /^I manually activate the perform activity "([^"]*)"$/
     * @param string $activity_name
     */
    public function i_manually_activate_the_perform_activity(string $activity_name): void {
        $activity = $this->get_activity_by_name($activity_name);
        $activity->status = \mod_perform\state\activity\active::get_code();
        $activity->save();
    }

    /**
     * @Then /^the perform element summary should contain:$/
     */
    public function the_perform_element_summary_should_contain(TableNode $table) {
        foreach ($table->getRows() as $i => $row) {
            $this->i_should_see_in_perform_element_summary_item($row[0], $row[1], $i + 1);
        }
    }

    private function i_should_see_in_perform_element_summary_item(
        $expected_title,
        $expected_value,
        $in_position
    ): void {
        $parts = [
            [
                'name' => 'title',
                'locator' => self::ADMIN_CUSTOM_ELEMENT_SUMMARY_SECTION_TITLE,
                'expected' => trim($expected_title)
            ],
            [
                'name' => 'value',
                'locator' => self::ADMIN_CUSTOM_ELEMENT_SUMMARY_SECTION_VALUE,
                'expected' => trim($expected_value)
            ],
        ];

        foreach ($parts as $part) {
            $part_element = $this->find_all('css', $part['locator'])[$in_position - 1];
            $actual_text = trim($part_element->getText());

            if ($actual_text !== $part['expected']) {
                $this->fail("Summary part {$part['name']} {$in_position} did not contain text \"{$part['expected']}\", instead found \"{$actual_text}\"");
            }
        }
    }

    /**
     * Convenience method to fail from an ExpectationException.
     *
     * @param string $error error message.
     */
    private function fail(string $error): void {
        throw new ExpectationException($error, $this->getSession());
    }

    private function get_activity_by_name(string $activity_name): activity {
        /** @var activity $activity */
        $activity = activity::repository()
            ->where('name', $activity_name)
            ->one();

        if (!$activity) {
            throw new DriverException('Activity with name \''.$activity_name.'\' not found.');
        }

        return $activity;
    }

    private function find_participant_group_container(string $group): NodeElement
    {
        if ($group === 'responding') {
            $participant_group = 'Responding participants';
        } else {
            $participant_group = 'View-only participants';
        }

        $headings = $this->find_all('css', '.tui-performActivitySection__participant-heading');

        $headings_for_group = array_filter($headings, function (NodeElement $heading) use ($participant_group) {
            return trim($heading->getText()) === $participant_group;
        });

        /** @var NodeElement $heading */
        $heading = reset($headings_for_group);

        return $heading->getParent();
    }

    /**
     * @Given /^I navigate to the view only report view of performance activity "([^"]*)" where "([^"]*)" is the subject$/
     * @param string $activity_name
     * @param string $subject_user_name
     */
    public function i_navigate_to_the_read_only_report_view_of_performance_activity_for(
        string $activity_name,
        string $subject_user_name
    ): void {
        $target_subject_instance = $this->get_subject_instance_from_activity_and_subject($activity_name, $subject_user_name);

        $url = view_only_user_activity::get_url(['subject_instance_id' => $target_subject_instance->id]);

        $this->navigate_to_page($url);
    }

    /**
     * @When /^I navigate to the "(view|print)" user activity page for performance activity "([^"]*)" where "([^"]*)" is the subject and "([^"]*)" is the participant$/
     * @param string $page_type
     * @param string $activity_name
     * @param string $subject_user_name
     * @param string $participant_user_name
     * @throws Exception
     */
    public function i_navigate_to_the_user_activity_page_for_activity_subject_participant(
        string $page_type,
        string $activity_name,
        string $subject_user_name,
        string $participant_user_name
    ): void {
        $participant_instance = $this->get_participant_instance_from_activity_subject_participant(
            $activity_name, $subject_user_name, $participant_user_name
        );

        /** @var perform_controller $controller_class */
        if ($page_type === 'print') {
            $controller_class = print_user_activity::class;
            $params = ['participant_section_id' => $participant_instance->participant_sections->first()->id];
        } else {
            $controller_class = view_user_activity::class;
            $params = ['participant_instance_id' => $participant_instance->id];
        }

        $this->navigate_to_page($controller_class::get_url($params));
    }

    private function get_user_by_username(string $user_name): entity {
        return user::repository()
            ->where('username', $user_name)
            ->one(true);
    }

    private function get_subject_instance_from_activity_and_subject(string $activity_name, string $subject_user_name): entity {
        return subject_instance::repository()
            ->as('si')
            ->join([track_user_assignment::TABLE, 'tua'], 'tua.id', 'si.track_user_assignment_id')
            ->join([track::TABLE, 't'], 't.id', 'tua.track_id')
            ->join([activity::TABLE, 'a'], 'a.id', 't.activity_id')
            ->where('a.name', $activity_name)
            ->join([user::TABLE, 'u'], 'u.id', 'si.subject_user_id')
            ->where('u.username', $subject_user_name)
            ->one(true);
    }

    private function get_participant_instance_from_activity_subject_participant(
        string $activity_name,
        string $subject_user_name,
        string $participant_user_name
    ): participant_instance {
        return participant_instance::repository()
            ->as('pi')
            ->join([user::TABLE, 'pu'], 'pu.id', 'pi.participant_id')
            ->join([subject_instance::TABLE, 'si'], 'si.id', 'pi.subject_instance_id')
            ->join([track_user_assignment::TABLE, 'tua'], 'tua.id', 'si.track_user_assignment_id')
            ->join([track::TABLE, 't'], 't.id', 'tua.track_id')
            ->join([activity::TABLE, 'a'], 'a.id', 't.activity_id')
            ->join([user::TABLE, 'su'], 'su.id', 'si.subject_user_id')
            ->where('a.name', $activity_name)
            ->where('su.username', $subject_user_name)
            ->where('pu.username', $participant_user_name)
            ->order_by('id')
            ->first(true);
    }

}
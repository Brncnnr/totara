@totara @totara_notification @javascript @vuejs @engage_article
Feature: Sending notification with placeholders

  Background:
    Given I log in as "admin"
    And the following "users" exist:
      | firstname | lastname | username | email           |
      | One       | User     | one      | one@example.com |
      | Two       | User     | two      | two@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | summary      |
      | Certification 1 | cert1     | Program HTML |
    And the following job assignments exist:
      | user | manager |
      | one  | two     |
    And I log out

  Scenario: Sending overridden built-in notification to user on assigned to certification with placeholder
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    When I click on "Actions for Team member assigned in certification" "button"
    And I click on "//a[@title='Edit notification Team member assigned in certification'][contains(text(),'Edit')]" "xpath_element"
    And I click on the "Enable customising field subject" tui toggle button
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to ""
    And I activate the weka editor with css ".tui-notificationPreferenceForm__subjectEditor"
    And I type "New comment from " in the weka editor
    When I type "[subject" in the weka editor
    Then I should see "Subject Full name"
    And I should see "Subject Full name (with link)"
    When I click on "Subject Full name" "link"
    # They are concatenated string with <span\> for the placeholder - hence this is the only to
    # check for the value in weka editor.
    Then I should see "New comment from " in the ".tui-notificationPreferenceForm__subjectEditor" "css_element"
    And I should see "Subject Full name" in the ".tui-notificationPreferenceForm__subjectEditor" "css_element"
    And I should not see "Subject Full name (with link)"
    And I click on the "Enable customising field body" tui toggle button
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to ""
    And I activate the weka editor with css ".tui-notificationPreferenceForm__bodyEditor"
    And I type "Hello user " in the weka editor
    When I type "[certification" in the weka editor
    Then I should see "Certification Full name"
    And I should see "Certification Full name (with link)"
    When I click on "Certification Full name" "link"
    # They are concatenated string with <span\> for the placeholder - hence this is the only to
    # check for the value in weka editor.
    Then I should see "Hello user " in the ".tui-notificationPreferenceForm__bodyEditor" "css_element"
    And I should see "Certification Full name" in the ".tui-notificationPreferenceForm__bodyEditor" "css_element"
    And I type " you have new comment" in the weka editor
    And I click on "Save" "button"
    And I reset the email sink
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    When I trigger cron
    Then the following emails should not have been sent:
      | To              | Subject                             | Body                                                     |
      | two@example.com | New comment from [subject:fullname] | Hello user [certification:fullname] you have new comment |
    And the following emails should have been sent:
      | To              | Subject                   | Body                                            |
      | two@example.com | New comment from One User | Hello user Certification 1 you have new comment |
    And the message "New comment from One User" contains "Hello user Certification 1 you have new comment" for "two" user

@totara @totara_notification @javascript @vuejs @engage_article
Feature: Sending overridden notification

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

  Scenario: Sending overridden built-in notification to user on certification
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    And I click on "Learner assigned in certification details" "button"
    When I click on "Actions for Team member assigned in certification" "button"
    And I click on "//a[@title='Edit notification Team member assigned in certification'][contains(text(),'Edit')]" "xpath_element"
    And I click on the "Enable customising field subject" tui toggle button
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Overridden subject at system"
    And I click on the "Enable customising field body" tui toggle button
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Overridden body at system"
    And I click on "Save" "button"
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    # Check the notification
    And I reset the email sink
    And I trigger cron
    Then the following emails should not have been sent:
      | To              | Subject                                       | Body                                                                               |
      | two@example.com | One User has been assigned in Certification 1 | Your team member <a href="http://">One User</a> is now assigned on Certification 1 |
    And the following emails should have been sent:
      | To              | Subject                      | Body                      |
      | two@example.com | Overridden subject at system | Overridden body at system |
    And the message "Overridden subject at system" contains "Overridden body at system" for "two" user

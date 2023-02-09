@totara @totara_notification @javascript @vuejs @engage_article
Feature: Notifications are not sent when notifiable event status is disabled

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

  Scenario: Notifications are sent when notifiable event status is enabled
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-pressed][aria-label='Learner assigned in certification notification status']" "css_element" should exist

    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    # Check the notification
    And I reset the email sink
    And I trigger cron
    Then the following emails should have been sent:
      | To              | Subject                                   | Body                                                   |
      | one@example.com | You have been assigned in Certification 1 | You are now assigned on certification Certification 1. |

  Scenario: Notifications are not sent when notifiable event status is disabled
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    When I click on the "Learner assigned in certification notification status" tui toggle button

    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    # Check the notification
    And I reset the email sink
    And I trigger cron
    Then the following emails should not have been sent:
      | To              | Subject                                   | Body                                                   |
      | one@example.com | You have been assigned in Certification 1 | You are now assigned on certification Certification 1. |


  Scenario: Notifications are sent when notifiable event status is enabled for a user
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-pressed][aria-label='Learner assigned in certification notification status']" "css_element" should exist

    # Check the user has the preference enabled
    When I log out
    And I log in as "one"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-pressed][aria-label='Learner assigned in certification notification status']" "css_element" should exist

    And I log out
    And I log in as "admin"
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    # Check the notification
    And I reset the email sink
    And I trigger cron
    Then the following emails should have been sent:
      | To              | Subject                                   | Body                                                   |
      | one@example.com | You have been assigned in Certification 1 | You are now assigned on certification Certification 1. |

  Scenario: Notifications are not sent when notifiable event status is disabled for a user
    Given I log in as "admin"
    And I navigate to system notifications page
    And I click on "Certification" "button"
    Then ".tui-toggleSwitch__btn[aria-pressed][aria-label='Learner assigned in certification notification status']" "css_element" should exist

    When I log out
    And I log in as "one"
    And I follow "Preferences" in the user menu
    And I follow "Notification preferences"
    And I click on "Certification" "button"
    And I click on the "Learner assigned in certification notification status" tui toggle button
    And I log out
    And I log in as "admin"
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user |
      | cert1   | one  |
    # Check the notification
    And I reset the email sink
    And I trigger cron
    Then the following emails should not have been sent:
      | To              | Subject                                   | Body                                                   |
      | one@example.com | You have been assigned in Certification 1 | You are now assigned on certification Certification 1. |


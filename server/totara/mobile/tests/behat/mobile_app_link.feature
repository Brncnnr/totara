@totara @totara_mobile @javascript
Feature: Confirm that the mobile app link banner works correctly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"

  Scenario: Check that mobile app link banner works with default settings
    And I log out
    And I log in as "student1"
    And I should see "Would you like to switch to the mobile app?"
    And I should see "Go to mobile app"
    When I follow "Go to mobile app"
    Then I am at the totara mobile app installer

  Scenario: Check the mobile app link banner is only shown on first request
    And I log out
    And I log in as "student1"
    And I should see "Would you like to switch to the mobile app?"
    When I follow "Continue in browser"
    Then I should not see "Would you like to switch to the mobile app?"
    And I follow "Dashboard"
    Then I should not see "Would you like to switch to the mobile app?"

  Scenario: Check that mobile app link banner works with custom url scheme
    And I set the following fields to these values:
      | URL scheme | /totara/plan/record/index.php  |
    And I click on "Save changes" "button"
    And I log out
    And I log in as "student1"
    And I should see "Would you like to switch to the mobile app?"
    When I follow "Go to mobile app"
    Then I should see "Record of Learning"

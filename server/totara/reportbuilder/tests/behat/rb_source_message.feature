@totara @totara_reportbuilder
Feature: Make sure the message report is shown correctly
  In order to check the message report is not throwing any errors
  As admin
  I need to create a custom message report and add some content and access restrictions.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | One      | user1@example.com    |
      | user2    | User      | Two      | user2@example.com    |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following job assignments exist:
      | user     | manager  |
      | user1    | manager1 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname              | shortname                    | source         | accessmode |
      | Custom message report | report_custom_message_report | totaramessages | 1          |
    And I log in as "admin"
    And I navigate to my "Custom message report" report
    And I press "Edit this report"
    And I switch to "Access" tab
    And the field "Only certain users can view this report (see below)" matches value "1"
    And I set the field "Authenticated User" to "1"
    And I press "Save changes"
    And I switch to "Content" tab
    And I set the field "Show records matching all of the checked criteria below" to "true"
    And I set the field "Show records based on user" to "1"
    And I set the field "Records for user's direct reports for any of the user's job assignments" to "1"
    And I press "Save changes"
    And I log out

  Scenario: Manager seeing the report.
    Given I log in as "manager1"
    And I click on "Reports" in the totara menu
    When I click on "Custom message report" "link" in the "#myreports_section" "css_element"
    Then I should not see "'user' not in join list for content"

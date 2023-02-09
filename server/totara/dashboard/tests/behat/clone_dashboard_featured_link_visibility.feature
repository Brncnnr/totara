@totara @totara_dashboard
Feature: Clone a dashboard in order to test the correct behaviour related to the visibility settings for the dashboard featured links tile

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email              | idnumber |
      | user1    | First     | User     | first@example.com  | T1       |
      | user2    | Second    | User     | second@example.com | T2       |
      | user3    | Third     | User     | third@example.com  | T3       |
      | user4    | Forth     | User     | forth@example.com  | T4       |
    And the following "cohorts" exist:
      | name     | idnumber |
      | Cohort 1 | CH1      |
      | Cohort 2 | CH2      |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | CH1    |
      | user2 | CH1    |
      | user2 | CH2    |
      | user3 | CH2    |
    And the following totara_dashboards exist:
      | name                | locked | published |
      | Primary dashboard   | 0      | 2         |
      | Secondary dashboard | 0      | 2         |
    And I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Primary dashboard" "link"
    And I press "Blocks editing on"
    And I add the "Featured Links" block
    And I click on "Add Tile" "link"
    And I set the field "url" to "https://www.example.com"
    And I set the field "textbody" to "default description"
    And I click on "Save changes" "button"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    And I set the "Access" Totara form field to "Apply rules"
    And I click on "Expand all" "text"
    And I set the "Define access by audience rules" Totara form field to "1"
    And I click on "Add audiences" "button"
    And I click on "Cohort 1" "link"
    And I click on "OK" "button"
    And I click on "Save changes" "button"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Clone dashboard" "link" in the "Primary dashboard" "table_row"
    And I press "Continue"
    And I log out

  @javascript
  Scenario: Cloning a dashboard featured link tile copies audiences
    When I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Primary dashboard copy 1" "link"
    And I press "Blocks editing on"
    And I click on "div.block-totara-featured-links-edit div.moodle-actionmenu" "css_element"
    And I click on "Visibility" "link"
    Then "Cohort 1" "link" should exist
    And I log out

  @javascript
  Scenario: Featured link tile can only visible to given audiences
    When I log in as "user3"
    And I click on "Primary dashboard copy 1" "link"
    Then "default description" "link" should not exist
    And I log out
    When I log in as "user2"
    And I click on "Primary dashboard copy 1" "link"
    Then "default description" "link" should exist
    And I log out

@totara @totara_reportbuilder @core_badges @javascript
Feature: Badges report filter
  As an admin
  I should be able to filter badges using the report builder

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname     | shortname           | source       |
      | Badge report | report_badge_report | badge_issued |
    And I log in as "admin"
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 1           |
      | Description   | Test badge description |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    When I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I set the field "potentialrecipients" to "User One (user1@example.com),User Two (user2@example.com),User Three (user3@example.com)"
    And I click on "Award badge" "button"

    # Add a second badge.
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 2           |
      | Description   | Test badge description |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    When I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I set the field "potentialrecipients" to "User One (user1@example.com)"
    And I click on "Award badge" "button"

  Scenario: Test badge report builder filter
    And I navigate to my "Badge report" report
    # The badges filter testing should be one of the default filters.
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should not see "User Four"

    # Now do some filtering.
    When I click on "Add badges" "link"
    And I click on "Test Badge 2" "link" in the "Choose badges" "totaradialogue"
    And I click on "Save" "button" in the "Choose badges" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should not see "User Four"

    When I click on "Add badges" "link"
    And I click on "Test Badge 1" "link" in the "Choose badges" "totaradialogue"
    And I click on "Save" "button" in the "Choose badges" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should not see "User Four"

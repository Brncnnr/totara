@engage @totara @totara_msteams @totara_catalog @mod_scorm @javascript
Feature: Play SCORM activity in MS Teams
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "courses" exist:
      | fullname     | shortname | category |
      | SCORM Player | SPC       | 0        |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | SPC    | student |
    And I log in as "admin"
    And I am on "SCORM Player" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Awesome SCORM package |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager

  Scenario: msteams701: Play SCORM in current window
    And I set the following fields to these values:
      | Display package | Current window |
    And I click on "Save and display" "button"
    And I log out
    Given I log in as "user1"
    And I am on Microsoft Teams "catalog" page
    When I click on "SCORM Player" "text"
    And I follow "Awesome SCORM package"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Enter" "button_exact"
    And I switch to "scorm_object" iframe
    And I switch to "contentFrame" iframe
    And I should see "Play of the game"

  Scenario: msteams702: Play SCORM in new window
    And I set the following fields to these values:
      | Display package | New window |
    And I click on "Save and display" "button"
    And I log out
    Given I log in as "user1"
    And I am on Microsoft Teams "catalog" page
    When I click on "SCORM Player" "text"
    And I follow "Awesome SCORM package"
    Then I should see "This page is not fully compatible with Microsoft Teams"
    And I click on "Enter" "button_exact"
    And I switch to "Popup" window
    And I switch to "scorm_object" iframe
    And I switch to "contentFrame" iframe
    And I should see "Play of the game"

  Scenario: msteams703: Play SCORM in new window (simple)
    And I set the following fields to these values:
      | Display package | New window (simple) |
    And I click on "Save and display" "button"
    And I log out
    Given I log in as "user1"
    And I am on Microsoft Teams "catalog" page
    When I click on "SCORM Player" "text"
    And I follow "Awesome SCORM package"
    Then I should see "This page is not fully compatible with Microsoft Teams"
    And I click on "Enter" "button_exact"
    And I switch to "scorm_content_1" window
    And I switch to "contentFrame" iframe
    And I should see "Play of the game"

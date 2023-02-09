@totara @totara_api @javascript
Feature: API documentation is rendered correctly.

  Background:
    Given I am on a totara site
    And I enable the "api" advanced feature

  Scenario: Documentation page is rendered without any warnings or errors.
    # This test is skipped if API documentation assets aren't built
    Given the API documentation has been built
    When I log in as "admin"
    And I navigate to "API documentation" node in "Site administration > Development > API"
    Then ".alert.alert-warning" "css_element" should not exist

  Scenario: User with correct privileges can see the documentation menu item and navigate to the page.
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "roles" exist:
      | name     | shortname |
      | apiadmin | apiadmin  |
    And the following "permission overrides" exist:
      | capability                   | permission | role      | contextlevel | reference |
      | totara/api:viewdocumentation | Allow      | apiadmin  | System       |           |
    And the following "system role assigns" exist:
      | user  | role     |
      | user1 | apiadmin |
    And I log in as "user1"
    And I navigate to "API documentation" node in "Site administration > Development > API"
    Then I should see "Back to API"

  Scenario: User with insufficient privileges can not access the documentation page.
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I log in as "user1"
    And I am on the API documentation page
    Then I should see "Access denied"
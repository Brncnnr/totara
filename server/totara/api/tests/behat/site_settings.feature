@totara @totara_api @javascript
Feature: API settings are navigable and work correctly.

  Background:
    Given I am on a totara site
    And I enable the "api" advanced feature

  Scenario: Settings page is rendered without any warnings or errors.
    When I log in as "admin"
    And I navigate to "API settings" node in "Site administration > Development > API"
    Then ".alert.alert-warning" "css_element" should not exist

  Scenario: User with correct privileges can see the settings menu item and navigate to the page.
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "roles" exist:
      | name     | shortname |
      | apiadmin | apiadmin  |
    And the following "permission overrides" exist:
      | capability         | permission | role     | contextlevel | reference |
      | moodle/site:config | Allow      | apiadmin | System       |           |
    And the following "system role assigns" exist:
      | user  | role     |
      | user1 | apiadmin |
    And I log in as "user1"
    And I navigate to "API settings" node in "Site administration > Development > API"
    Then ".alert.alert-warning" "css_element" should not exist

  Scenario: User with insufficient privileges can not access the settings page.
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I log in as "user1"
    And I am on the API settings page
    Then I should see "Access denied"

  Scenario: Settings require positive integers.
    When I log in as "admin"
    And I navigate to "API settings" node in "Site administration > Development > API"
    And I set the following fields to these values:
      | Site rate limit | -1 |
    And I press "Save changes"
    Then I should see "This value is not valid"
    And I set the following fields to these values:
      | Site rate limit | 1.5 |
    And I press "Save changes"
    Then I should see "This value is not valid"
    And I set the following fields to these values:
      | Site rate limit | 1 |
    And I press "Save changes"
    Then I should see "Changes saved"

    And I set the following fields to these values:
      | Client rate limit | 0 |
    And I press "Save changes"
    Then I should see "This value is not valid"
    And I set the following fields to these values:
      | Client rate limit | 1 |
    And I press "Save changes"
    Then I should see "Changes saved"

    And I set the following fields to these values:
      | Maximum query complexity | 0 |
    And I press "Save changes"
    Then I should see "This value is not valid"
    And I set the following fields to these values:
      | Maximum query complexity | 1 |
    And I press "Save changes"
    Then I should see "Changes saved"

    And I set the following fields to these values:
      | Maximum query depth | 0 |
    And I press "Save changes"
    Then I should see "This value is not valid"
    And I set the following fields to these values:
      | Maximum query depth | 1 |
    And I press "Save changes"
    Then I should see "Changes saved"

    And I set the following fields to these values:
      | Default token expiration | -1 |
    And I press "Save changes"
    Then I should see "Could not save setting"
    And I set the following fields to these values:
      | Default token expiration | 0 |
    And I press "Save changes"
    Then I should see "Duration must be 1 second or more"
    And I set the following fields to these values:
      | Default token expiration | 1 |
    And I press "Save changes"
    Then I should see "Changes saved"

  Scenario: Settings require valid integers.
    When I log in as "admin"
    And I navigate to "API settings" node in "Site administration > Development > API"
    And I set the following fields to these values:
      | Site rate limit | 2147483649 |
    And I press "Save changes"
    Then I should see "Number must be 2147483647 or less"

    And I set the following fields to these values:
      | Maximum query depth | 2147483649 |
    And I press "Save changes"
    Then I should see "Number must be 2147483647 or less"

    And I set the following fields to these values:
      | Site rate limit | 2147483649 |
    And I press "Save changes"
    Then I should see "Number must be 2147483647 or less"

    And I set the following fields to these values:
      | Maximum query complexity | 2147483649 |
    And I press "Save changes"
    Then I should see "Number must be 2147483647 or less"

    And I set the following fields to these values:
      | Default token expiration | 2147483649 |
    And I press "Save changes"
    Then I should see "Duration must be 2147483647 seconds or less"
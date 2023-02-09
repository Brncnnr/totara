@core @core_auth
Feature: Authentication
  In order to validate my credentials in the system
  As a user
  I need to log into the system

# Totara: our theme does not include login info in footer

  Scenario: Log in with the predefined admin user with Javascript disabled
    Given I log in as "admin"
    Then I should see "Admin User" in the ".usermenu .usertext" "css_element"

  @javascript
  Scenario: Log in with the predefined admin user with Javascript enabled
    Given I log in as "admin"
    Then I should see "Admin User" in the ".usermenu .usertext" "css_element"

  Scenario: Log in as an existing admin user filling the form
    Given the following "users" exist:
      | username | password | firstname | lastname | email |
      | testuser | testuser | Test | User | moodle@example.com |
    And I am on site homepage
    When I follow "Log in"
    And I set the field "Username" to "testuser"
    And I set the field "Password" to "testuser"
    And I press "Log in"
    Then I should see "Test User" in the ".usermenu .usertext" "css_element"

  Scenario: Log in as an unexisting user filling the form
    Given the following "users" exist:
      | username | password | firstname | lastname | email |
      | testuser | testuser | Test | User | moodle@example.com |
    And I am on site homepage
    When I follow "Log in"
    And I set the field "Username" to "testuser"
    And I set the field "Password" to "unexisting"
    And I press "Log in"
    Then I should see "Invalid login, please try again"

  Scenario: Log out
    Given I log in as "admin"
    When I log out
    Then I should see "You are not logged in" in the ".login" "css_element"

  @javascript
  Scenario: Test regular user can log in with username and password containing trailing space
    Given I am on a totara site
    And the following "users" exist:
      | username | password | firstname | lastname | email          |
      | user1    | pass1    | Prvni     | Uzivatel | u1@example.com |

    When I use magic for persistent login to open the login page
    And I should not see "You are not logged in"
    And I should not see "You are logged in"
    And I set the field "Username" to "user1"
    And I set the field "Password" to "pass1 "
    And I press "Log in"
    Then I should see "Prvni Uzivatel"

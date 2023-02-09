@core @javascript

Feature: Log user out
  In order to confirm that the logout functionality works
  As a user
  I need navigate the logout page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email         |
      | testuser | Test      | User     | test@test.com |
    Given I am on site homepage
    And I log in as "testuser"

  Scenario: Log out by navigating to the logout page
    Given I log out
    Then I should see "You are not logged in" in the ".login" "css_element"

  Scenario: Log out by clicking on the log out link in the dropdown menu
    Given I follow "Log out" in the user menu
    Then I should see "You are not logged in" in the ".login" "css_element"
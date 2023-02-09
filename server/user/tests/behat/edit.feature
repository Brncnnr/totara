@core @core_user @javascript
Feature: Basic editing of users
  In order to use the user/edit.php page comfortably
  As a user
  It needs to be redirecting back to the original page

  Scenario: Edit own user info from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should see "User details"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Update profile"
    Then I should see "Prvni Uzivatel"
    And I should see "User details"

  Scenario: Cancel editing of  own user info from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Profile" in the user menu
    And I should see "User details"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Cancel"
    Then I should see "First User"
    And I should see "User details"

  Scenario: Edit own user info from preferences
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I should see "Legacy notification preferences"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Update profile"
    Then I should see "Prvni Uzivatel"
    And I should see "Legacy notification preferences"

  Scenario: Cancel editing of  own user info from preferences
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "user1"
    And I follow "Preferences" in the user menu
    And I should see "Legacy notification preferences"
    And I follow "Edit profile"
    And I set the following fields to these values:
      | First name | Prvni    |
      | Surname    | Uzivatel |
    When I press "Cancel"
    Then I should see "First User"
    And I should see "Legacy notification preferences"

  Scenario: Admin/manager can create a new password for others but not for yourself
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "admin"
    When I follow "Preferences" in the user menu
    And I follow "Edit profile"
    Then I should not see "New password"

    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "First User" "link"
    When I click on "Edit profile" "link" in the ".block_totara_user_profile_category_contact" "css_element"
    Then I should see "New password"

  Scenario: Admin/manager can create a new password for others admin but not for yourself
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First     | User     | user1@example.com |
    And I log in as "admin"
    And I navigate to "Permissions > Site administrators" in site administration

    And I set the field "Users" to "First User (user1, user1@example.com)"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "First User" "link"
    When I click on "Edit profile" "link" in the ".block_totara_user_profile_category_contact" "css_element"
    Then I should see "New password"
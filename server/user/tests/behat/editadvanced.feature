@core @core_user @javascript
Feature: Advanced editing of users
  In order to let admin manage users
  As an admin
  I need to be able to add and update user accounts

  Scenario: Add a new user from admin tree
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I press "Create user"
    When I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | u1@example.com    |
    And I press "Save and go back"
    And I follow "User One"
    Then I should see "User details"
    And I should see "u1@example.com"

  Scenario: Cancel adding of a new user from admin tree
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I press "Create user"
    When I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | u1@example.com    |
    And I press "Cancel"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email       |
      | admin    | moodle@example.com |
    And I should not see "u1@example.com"

  Scenario: Add a new user from all users
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I press "Create user"
    When I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | u1@example.com    |
    And I press "Save and go back"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email       |
      | admin    | moodle@example.com |
      | user1    | u1@example.com     |

  Scenario: Cancel adding of a new user from all users
    Given I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I press "Create user"
    When I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | u1@example.com    |
    And I press "Cancel"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email       |
      | admin    | moodle@example.com |
    And I should not see "u1@example.com"

  Scenario: Edit user as admin from all users
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | One      | user1@example.com |
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "User One" "table_row"
    When I set the following fields to these values:
      | Username      | u1             |
      | Email address | u1@example.com |
    And I press "Save and go back"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email       |
      | admin    | moodle@example.com |
      | u1       | u1@example.com     |

  Scenario: Cancel editing of user as admin from all users
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "User One" "table_row"
    When I set the following fields to these values:
      | Username      | u1             |
      | Email address | u1@example.com |
    And I press "Cancel"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email       |
      | admin    | moodle@example.com |
      | user1    | user1@example.com  |

  Scenario: Edit user as admin from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I follow "Edit profile"
    When I set the following fields to these values:
      | Username      | u1             |
      | Email address | u1@example.com |
    And I press "Update profile"
    Then I should see "User details"
    And I should see "u1@example.com"

  Scenario: Cancel editing of user as admin from profile
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I log in as "admin"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I click on "User One" "link" in the "User One" "table_row"
    And I follow "Edit profile"
    When I set the following fields to these values:
      | Username      | u1             |
      | Email address | u1@example.com |
    And I press "Cancel"
    Then I should see "User details"
    And I should see "user1@example.com"

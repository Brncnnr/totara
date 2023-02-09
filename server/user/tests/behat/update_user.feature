@totara @core @core_user @javascript
Feature: User account updates

  As an user account manager
  In order to keep the user data up to date
  I need to be able to update accounts

  Background:
    Given I am on a totara site

  Scenario: User manager without manage login permission updating account
    Given the following "users" exist:
      | username     | firstname | lastname | email                   |
      | user1        | First     | User     | user1@example.com       |
      | usercreator  | User      | Creator  | usercreator@example.com |
    And the following "roles" exist:
      | shortname   |
      | usercreator |
    And the following "role assigns" exist:
      | user        | role        | contextlevel | reference |
      | usercreator | usercreator | System       |           |
    And the following "permission overrides" exist:
      | capability                        | permission | role        | contextlevel | reference |
      | moodle/user:update                | Allow      | usercreator | System       |           |
      | moodle/user:viewalldetails        | Allow      | usercreator | System       |           |
    And I log in as "usercreator"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    When I click on "Edit First User" "link" in the "First User" "table_row"
    And I should not see "Suspended account"
    And I should not see "New password"
    And I should not see "Force password change"
    And I set the following fields to these values:
      | Username                        | uziv1             |
      | First name                      | Prvni             |
      | Surname                         | Uzivatel          |
      | Email address                   | uziv1@example.com |
    And I press "Save and go back"
    Then the following should exist in the "system_browse_users" table:
      | Username | User Status | User's Fullname |
      | uziv1    | Active      | Prvni Uzivatel  |

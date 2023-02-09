@totara @core @core_user @javascript
Feature: User account creation

  As an user account creator
  In order to allow new user to use Totara
  I need to be able to create accounts

  Background:
    Given I am on a totara site

  Scenario: Account creator without manage login permission adding account
    Given the following "users" exist:
      | username     | firstname | lastname | email                   |
      | usercreator  | User      | Creator  | usercreator@example.com |
    And the following "roles" exist:
      | shortname   |
      | usercreator |
    And the following "role assigns" exist:
      | user        | role        | contextlevel | reference |
      | usercreator | usercreator | System       |           |
    And the following "permission overrides" exist:
      | capability                        | permission | role        | contextlevel | reference |
      | moodle/user:create                | Allow      | usercreator | System       |           |
      | moodle/user:viewalldetails        | Allow      | usercreator | System       |           |
    And I log in as "usercreator"
    And I navigate to "Manage users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    When I press "Create user"
    And I set the following fields to these values:
      | Username                        | user1             |
      | New password                    | A.New.Pw.123      |
      | First name                      | User              |
      | Surname                         | One               |
      | Email address                   | a1@example.com    |
      | Suspended                       | 1                 |
    And I press "Save and go back"
    Then the following should exist in the "system_browse_users" table:
      | Username | User Status |
      | user1    | Suspended   |

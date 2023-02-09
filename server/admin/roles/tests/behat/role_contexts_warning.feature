@core @core_admin @core_admin_roles
Feature: Warn when a role is assigned
  In order to inform when changing a context level may be problematic
  As an admin
  I need to be told if my context level is assigned to a role

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | course1   |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "roles" exist:
      | name       | shortname  | archetype |
      | onebanner  | onebanner  | manager   |
      | twobanners | twobanners | manager   |
      | nobanners  | nobanners  | manager   |
    And the following "system role assigns" exist:
      | user  | role      |
      | user1 | onebanner |
      | user1 | twobanners |
    And the following "role assigns" exist:
      | user  | role      | contextlevel | reference |
      | user1 | twobanners | Course       | course1   |

  @javascript
  Scenario: A warning is shown when the role has a context assigned
    Given I am on a totara site
    And I log in as "admin"

    When I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "onebanner" "link" in the "onebanner" "table_row"
    Then I should see "Viewing the definition of role 'onebanner'"
    And I should not see "This role is currently assigned to users in"
    When I press "Edit"
    Then I should see "This role is currently assigned to users in System context. They can only be removed manually."
    And I should not see "This role is currently assigned to users in Course context. They can only be removed manually."

    When I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "twobanners" "link" in the "twobanners" "table_row"
    Then I should see "Viewing the definition of role 'twobanners'"
    And I should not see "This role is currently assigned to users in"
    When I press "Edit"
    Then I should see "This role is currently assigned to users in System context. They can only be removed manually."
    And I should see "This role is currently assigned to users in Course context. They can only be removed manually."

    When I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "nobanners" "link" in the "nobanners" "table_row"
    Then I should see "Viewing the definition of role 'nobanners'"
    And I should not see "This role is currently assigned to users in"
    When I press "Edit"
    And I should not see "This role is currently assigned to users in"

@totara @totara_catalog @javascript
Feature: Test catalogue page customisation
  As an administrator or a user with permissions
  I need to be able to edit the catalog page
  In order to add other content to it

  Scenario: Edit page as an administrator
    Given I am on a totara site
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    Then I should see "Customise this page"
    And I should not see the "Featured Links" block

    When I follow "Customise this page"
    Then I should see "Stop customising this page"
    And I should not see the "Featured Links" block
    When I add the "Featured Links" block to the "bottom" region
    Then I should see the "Featured Links" block in the "bottom" region

    When I follow "Stop customising this page"
    Then I should see "Customise this page"

  Scenario: Cannot edit catalogue page blocks as a guest
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | guestloginbutton | Show |
    And I log out
    And I press "Log in as a guest"
    When I click on "Find Learning" in the totara menu
    Then I should not see "Customise this page"

  Scenario: Can edit catalogue page blocks with capability
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname |
      | testuser1 | Normal    | User     |
      | testuser2 | Page      | Editor   |
    And the following "roles" exist:
      | shortname  |
      | pageeditor |
    And the following "role assigns" exist:
      | user      | role       | contextlevel | reference |
      | testuser1 | pageeditor | System       |           |
    And the following "permission overrides" exist:
      | capability                       | permission | role       | contextlevel | reference |
      | moodle/site:manageblocks         | Allow      | pageeditor | System       |           |
      | moodle/block:edit                | Allow      | pageeditor | System       |           |
      | block/calendar_month:addinstance | Allow      | pageeditor | System       |           |
    When I log in as "testuser2"
    And I click on "Find Learning" in the totara menu
    Then I should not see "Customise this page"
    And I log out

    When I log in as "testuser1"
    And I click on "Find Learning" in the totara menu
    Then I should see "Customise this page"
    And I should not see the "Calendar" block

    When I follow "Customise this page"
    Then I should see "Stop customising this page"
    And I should not see the "Calendar" block
    When I add the "Calendar" block to the "top" region
    Then I should see the "Calendar" block in the "top" region

    When I follow "Stop customising this page"
    Then I should see "Customise this page"
    And I should see the "Calendar" block in the "top" region

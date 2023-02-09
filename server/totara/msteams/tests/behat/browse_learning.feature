@engage @totara @totara_msteams @totara_dashboard @block_current_learning @javascript
Feature: Browse the current learning tab
  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
      | user2    | C1     | student |

  Scenario: msteams501: User can customise their current learning page
    Given I log in as "user1"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-row-item" "css_element"
    And I should see "Logged in as User One."
    And I should see "Sign out"
    And I log out Microsoft Teams
    Given I log in as "user1"
    And I am on "Dashboard" page
    And I press "Customise this page"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I click on "Tile view" "radio"
    And I press "Save changes"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"
    And I should see "Logged in as User One."
    And I should see "Sign out"
    And I click on "Sign out" "link"
    Given I log in as "user2"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-row-item" "css_element"

  Scenario: msteams502: Admin can customise the current learning page for all users
    Given I log in as "admin"
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I click on "Tile view" "radio"
    And I press "Save changes"
    And I log out
    Given I log in as "user1"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"
    And I should see "Logged in as User One."
    And I should see "Sign out"

  @tenant @totara_tenant
  Scenario: msteams503: Admin can customise the current learning page for each tenant
    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name         | idnumber |
      | Tenant One   | ten1     |
      | Tenant Two   | ten2     |
      | Tenant Three | ten3     |
    And the following "users" exist:
      | username | firstname | lastname | tenantmember | tenantparticipant | tenantusermanager | email                |
      | user3    | User      | Three    | ten1         |                   |                   | user3@example.com    |
      | user4    | User      | Four     | ten2         |                   |                   | user4@example.com    |
      | user5    | User      | Five     | ten3         |                   |                   | user5@example.com    |
      | user6    | User      | Six      |              | ten1, ten2, ten3  |                   | user6@example.com    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | user3    | C1     | student |
      | user4    | C1     | student |
      | user5    | C1     | student |
      | user6    | C1     | student |
    Given I log in as "admin"

    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "My Learning" "link" in the "My Learning" "table_row"
    And I press "Blocks editing on"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I click on "Tile view" "radio"
    And I press "Save changes"

    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Tenant One dashboard" "link"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I click on "Tile view" "radio"
    And I press "Save changes"

    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I click on "Tenant Two dashboard" "link"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I click on "List view (default)" "radio"
    And I press "Save changes"
    And I log out

    Given I log in as "user1"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"
    And I log out Microsoft Teams

    Given I log in as "user2"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"
    And I log out Microsoft Teams

    Given I log in as "user3"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"
    And I log out Microsoft Teams

    Given I log in as "user4"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-row-item" "css_element"
    And I log out Microsoft Teams

    Given I log in as "user5"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-row-item" "css_element"
    And I log out Microsoft Teams

    Given I log in as "user6"
    When I am on Microsoft Teams "mylearning" page
    Then I should see "Course 1" in the ".block_current_learning-tile" "css_element"

  Scenario: msteams504: Admin can customise CSS for all users
    Given I log in as "admin"
    And I navigate to "Ventura" node in "Site administration > Appearance > Themes"
    When I click on "Colours" "link"
    And I set the field "Primary brand colour" to "#FF000B"
    And I set the field "Accent colour" to "#00FFE6"
    And I click on "Save Colours Settings" "button"
    And I reload the page
    Then element ":root" should have a css property "--color-state" with a value of "#FF000B"
    And element ":root" should have a css property "--color-primary" with a value of "#00FFE6"
    And I log out
    Given I log in as "user1"
    When I am on Microsoft Teams "catalog" page
    Then element ":root" should have a css property "--color-state" with a value of "#FF000B"
    And element ":root" should have a css property "--color-primary" with a value of "#00FFE6"
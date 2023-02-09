@block @totara @block_totara_user_profile @javascript
Feature: Add User Profile blocks in a Default profile page
  In order to have one or multiple User Profile blocks in a Profile page
  As a admin
  I need to be able to create and change such blocks

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |

  Scenario: Adding User detail block in a Default profile page
    Given I log in as "admin"
    And I navigate to "Default profile page" node in "Site administration > Users"
    And I press "Blocks editing on"
    And I add the "User Profile" block
    And I configure the "User Profile" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Override default block title | Yes          |
      | Block title                  | User information |
      | Display User Profile category| User details |
    When I press "Save changes"
    Then I should see "User information"
    Then I should see "User details"

  Scenario: Adding Reports block in a Default profile page
    Given I log in as "admin"
    And I navigate to "Default profile page" node in "Site administration > Users"
    And I press "Blocks editing on"
    And I configure the "User Profile" block
    And I expand all fieldsets
    And I set the following fields to these values:
      | Display User Profile category| Reports |
    When I press "Save changes"
    Then I should see "Reports"
    And "Reset profile for all users" "button" should not exist

    When I log out
    And I log in as "learner1"
    And I follow "Profile" in the user menu
    Then I should see "Reports"

  Scenario: Removing ability to see fields in User Profile block
    Given I log in as "admin"
    And I navigate to "Default profile page" node in "Site administration > Users"
    And I press "Blocks editing on"
    And I configure the "User Profile" block
    And I expand all fieldsets
    And I click on "config_editprofile" "checkbox"
    And I click on "config_email" "checkbox"
    When I press "Save changes"
    And I log out

    And I log in as "learner1"
    And I follow "Profile" in the user menu
    Then I should not see "Edit Profile"
    Then I should not see "Email address"

  Scenario: Empty custom fields appear in custom settings
    Given I log in as "admin"
    When I navigate to "User profile fields" node in "Site administration > Users"
    And I set the following fields to these values:
      | datatype | text |
    And I set the following fields to these values:
      | Name       | customfield_text1 |
      | Short name | customfieldtext1  |
    And I press "Save changes"
    Then I should see "customfield_text1"

    When I set the following fields to these values:
      | datatype | textarea |
    And I set the following fields to these values:
      | Name       | customfield_textarea1 |
      | Short name | customfieldtextarea1  |
    And I press "Save changes"
    Then I should see "customfield_textarea1"

    When I set the following fields to these values:
      | datatype | menu |
    And I set the following fields to these values:
      | Name       | customfield_menu1 |
      | Short name | customfieldmenu1  |
    And I set the field "Menu options (one per line)" to multiline:
"""
A
B
"""
    And I press "Save changes"
    Then I should see "customfield_menu1"

    When I set the following fields to these values:
      | datatype | datetime |
    And I set the following fields to these values:
      | Name       | customfield_datetime1 |
      | Short name | customfielddatetime   |
    And I press "Save changes"
    Then I should see "customfield_datetime1"

    When I set the following fields to these values:
      | datatype | date |
    And I set the following fields to these values:
      | Name       | customfield_date1 |
      | Short name | customfielddate1  |
    And I press "Save changes"
    Then I should see "customfield_date1"

    When I set the following fields to these values:
      | datatype | checkbox |
    And I set the following fields to these values:
      | Name       | customfield_checkbox1 |
      | Short name | customfieldcheckbox1  |
    And I press "Save changes"
    Then I should see "customfield_checkbox1"

    When I navigate to "Default profile page" node in "Site administration > Users"
    Then I should see "customfield_checkbox1"
    And I should not see "customfield_text1"
    And I should not see "customfield_textarea1"
    And I should not see "customfield_menu1"
    And I should not see "customfield_datetime1"
    And I should not see "customfield_date1"

    When I press "Blocks editing on"
    And I configure the "User Profile" block
    And I expand all fieldsets
    Then I should see "customfield_checkbox1"
    And I should see "customfield_text1"
    And I should see "customfield_textarea1"
    And I should see "customfield_menu1"
    And I should see "customfield_datetime1"
    And I should see "customfield_date1"


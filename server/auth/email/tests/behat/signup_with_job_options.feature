@auth @auth_email @totara_job @javascript
Feature: User should be able to select organisation, position, and manager when signing up
  In order to create an account to use the site
  As a user
  I need to be able fill out a sign up page with job assignment options

  Scenario: Select organisation, position, and manager upon sign up while logged in as a guest
    Given the following config values are set as admin:
      | guestloginbutton        | Show         |            |
      | auth                    | manual,email |            |
      | registerauth            | email        |            |
      | passwordpolicy          | 0            |            |
      | allowsignupposition     | 1            | totara_job |
      | allowsignuporganisation | 1            | totara_job |
      | allowsignupmanager      | 1            | totara_job |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | oframe   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber | org_framework |
      | Organisation One | org1     | oframe        |
      | Organisation Two | org2     | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber |
      | Position Framework | pframe   |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber | pos_framework |
      | Position One | pos1     | pframe        |
      | Position Two | pos2     | pframe        |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Manager   | Test     | manager@example.com |
      | user1    | User      | Test     | user1@example.com   |
    And the following job assignments exist:
      | user  | manager |
      | user1 | manager |
    And I am on site homepage
    And I follow "Log in"
    When I press "Log in as a guest"
    Then I should see "You are currently using guest access"
    And I follow "Log in"
    And I press "Create new account"
    And I set the following fields to these values:
      | Username      | user2             |
      | Password      | user2             |
      | Email address | user2@example.com |
      | Email (again) | user2@example.com |
      | First name    | User2             |
      | Surname       | Example           |
    And I click on "Choose position" "button"
    And I click on "Position Two" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I click on "Choose organisation" "button"
    And I click on "Organisation Two" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I click on "Choose manager" "button"
    And I click on "Manager Test - Unnamed job assignment" "link" in the "Choose manager" "totaradialogue"
    And I click on "OK" "button" in the "Choose manager" "totaradialogue"
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at user2@example.com"
    And I confirm email for "user2"
    And I should see "Thanks, User2 Example"
    And I should see "Your registration has been confirmed"
    And I log out

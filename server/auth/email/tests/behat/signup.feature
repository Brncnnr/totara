@auth @auth_email
Feature: User must accept policy when logging in and signing up
  In order to record user agreement to use the site
  As a user
  I need to be able to accept site policy during sign up

  Scenario: Accept policy on sign up, no site policy
    Given the following config values are set as admin:
      | auth            | manual,email |
      | registerauth    | email |
      | passwordpolicy  | 0     |
    And I am on site homepage
    And I follow "Log in"
    When I press "Create new account"
    Then I should not see "I understand and agree"
    And I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I press "Continue"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user can login and browse the site (edit their profile).
    And I log in as "user1"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"

  Scenario: Accept policy on sign up, with site policy
    Given the following config values are set as admin:
      | auth            | manual,email |
      | registerauth    | email              |
      | passwordpolicy  | 0                  |
    And I am on site homepage
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1    |
    And the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages |statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | true      |
    And I log out
    And I follow "Log in"
    When I press "Create new account"
    Then I should see "Policy 1"
    And I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should see "Consent is required to access the site"
    When I set the "P1 - Consent statement 1 (Consent is required to access the site)" Totara form field to "1"
    And I press "Submit"
    Then I should see "New account"
    When I set the following fields to these values:
      | Username      | user1                 |
      | Password      | user1                 |
      | Email address | user1@address.invalid |
      | Email (again) | user1@address.invalid |
      | First name    | User1                 |
      | Surname       | L1                    |
    And I press "Create my new account"
    And I should see "An email should have been sent to your address at user1@address.invalid"
    And I confirm email for "user1"
    And I should see "Thanks, User1 L1"
    And I should see "Your registration has been confirmed"
    And I press "Continue"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"
    And I log out
    # Confirm that user is not asked to agree to site policy again after the next login.
    And I log in as "user1"
    And I open my profile in edit mode
    And the field "First name" matches value "User1"

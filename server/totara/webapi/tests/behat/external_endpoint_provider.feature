@totara @totara_api @javascript
Feature: Confirm that the external endpoint works as expected
  Test API provider functionality of the external endpoint

  Background:
    Given I am on a totara site
    And the following "client provider" exist in "totara_oauth2" plugin:
      | name   | description | client_id   | client_secret   |
      | Test 1 | Test 1      | client_id_1 | client_secret_1 |
    And I am using the API endpoint client emulator

  Scenario: Check that emulation page is working
    Then I should see "API endpoint emulator loading..."
    And I should see "Initialised."
    And I should see "Setting up new oauth2 token form:"

  Scenario: Test invalid client
    When I set the field "client_id" to "invalid_client_id"
    And I set the field "client_secret" to "client_secret_1"
    And I set the field "grant_type" to "client_credentials"
    And I click on "Submit Credentials 1" "button"
    Then I should see "Error: invalid_client: Client authentication failed"

  Scenario: Test invalid secret
    When I set the field "client_id" to "client_id_1"
    And I set the field "client_secret" to "invalid_client_secret"
    And I set the field "grant_type" to "client_credentials"
    And I click on "Submit Credentials 1" "button"
    Then I should see "Error: invalid_client: Client authentication failed"

  Scenario: Test invalid grant type
    When I set the field "client_id" to "client_id_1"
    And I set the field "client_secret" to "client_secret_1"
    And I set the field "grant_type" to "invalid_grant_type"
    And I click on "Submit Credentials 1" "button"
    Then I should see "Error: unsupported_grant_type: The authorization grant type is not supported by the authorization server"

  Scenario: Test valid token request
    When I set the field "client_id" to "client_id_1"
    And I set the field "client_secret" to "client_secret_1"
    And I set the field "grant_type" to "client_credentials"
    And I click on "Submit Credentials 1" "button"
    Then I should see "OAuth2 token retrieved successfully"
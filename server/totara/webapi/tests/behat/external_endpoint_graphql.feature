@totara @totara_api @javascript
Feature: Confirm that the external endpoint graphql queries work as expected
  Test API graphql functionality of the external endpoint

  Background:
    Given I am on a totara site
    And the following "client provider" exist in "totara_oauth2" plugin:
      |id | name   | description | client_id   | client_secret   |
      | 1 | Test 1 | Test 1      | client_id_1 | client_secret_1 |
    And the following "users" exist:
      | username | firstname  | lastname  |
      | user1    | user_first | user_last |
    And the following "api clients" exist in "totara_api" plugin:
      | name        | username | client_provider_id |
      | Test client | user1    | 1                  |
    And I am using the API endpoint client emulator
    And I set the field "client_id" to "client_id_1"
    And I set the field "client_secret" to "client_secret_1"
    And I set the field "grant_type" to "client_credentials"
    And I click on "Submit Credentials 1" "button"

  Scenario: Test request without authorization header
    And I set the field "jsondata" to "{\"query\":\"query {totara_webapi_status {status}}\",\"variables\":{},\"operationName\":null}"
    And I click on "authorization" "checkbox"
    And I click on "Submit Request 2" "button"
    And I ignore "The request did not contain the required Authorization header. Ensure you set the header in your request and that it is not being stripped by your server or proxy configuration" exceptions in log
    Then I should see "The request did not contain the required Authorization header. Ensure you set the header in your request and that it is not being stripped by your server or proxy configuration" in the "#response2" "css_element"

  Scenario: Test request with authorization header
    And I set the field "jsondata" to "{\"query\":\"query {totara_webapi_status {status}}\",\"variables\":{},\"operationName\":null}"
    And I click on "Submit Request 2" "button"
    Then I should see "\"status\": \"ok\"" in the "#response2" "css_element"
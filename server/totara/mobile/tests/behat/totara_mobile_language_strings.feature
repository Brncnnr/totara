@totara @totara_mobile @javascript
Feature: Test the totara_mobile_language_strings feature

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1  |
    And I click on "Save changes" "button"

  Scenario: Test totara mobile languge strings with default strings
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "admin"
    And I set the field "password" to "admin"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "totara_mobile_language_strings",
      "variables": { "lang": "en" }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"json_string\": \"{\\"app\\":{\\"general\\":{\\"loading\\":\\"Loading...\\"" in the "#response2" "css_element"

  Scenario: Test totara mobile languge strings with customised strings
    When I navigate to "Language customisation" node in "Site administration > Localisation"
    And I select "en" from the "Language" singleselect
    And I click on "Open language pack for editing" "button"
    And I click on "Continue" "button"
    And I set the following fields to these values:
    | Show strings of these components | totara_mobile |
    And I click on "Show strings" "button"
    And I click on "Next" "link" in the ".paging" "css_element"
    And I set the field with xpath "//div[normalize-space(text())='app:general:loading']/parent::td/following-sibling::td[2]/textarea" to "Clouding..."
    And I click on "Save changes to the language pack" "button"
    And I click on "Continue" "button"
    When I am using the mobile emulator
    Then I should see "Device emulator loading..."
    And I should see "Making login_setup request"
    And I set the field "username" to "admin"
    And I set the field "password" to "admin"
    When I click on "Submit Credentials 1" "button"
    Then I should see "Native login OK"
    And I should see "Setting up new GraphQL browser"
    When I set the field "jsondata2" to multiline:
    """
    {
      "operationName": "totara_mobile_language_strings",
      "variables": { "lang": "en" }
    }
    """
    And I click on "Submit Request 2" "button"
    Then I should not see "Coding error detected" in the "#response2" "css_element"
    And I should see "\"json_string\": \"{\\"app\\":{\\"general\\":{\\"loading\\":\\"Clouding...\\"" in the "#response2" "css_element"

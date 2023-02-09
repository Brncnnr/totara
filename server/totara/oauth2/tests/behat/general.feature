@totara @totara_oauth2 @auth @oauth2 @auth_oauth2 @javascript
Feature: General behaviour with totara oauth2

  Scenario: View OAuth2 provider details with one client provider
    Given I am on a totara site
    And the following "client provider" exist in "totara_oauth2" plugin:
      | name     | description     | client_id    | client_secret    | scope      |
      | ONE_NAME | ONE_DESCRIPTION | ONE_CLIENTID | ONE_CLIENTSECRET | xapi:write |
      | TWO_NAME | TWO_DESCRIPTION | TWO_CLIENTID | TWO_CLIENTSECRET | xapi:write |
    And I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 provider details" in site administration
    Then I should see "OAuth 2 provider details"
    And I should see "ONE_NAME"
    And I should see "TWO_NAME"
    When I click on "ONE_NAME" "button"
    Then I should see "ONE_DESCRIPTION"
    And I should see "ONE_CLIENTID"
    And the field "Client secret" matches value "ONE_CLIENTSECRET"
    And I should see "Write access to Experience API (xAPI)"
    And I should not see "TWO_CLIENTID"
    And "Show client secret for TWO_NAME" "button" should not be visible
    And I should not see "TWO_DESCRIPTION"

  Scenario: View OAuth2 provider details without one client provider
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 provider details" in site administration
    Then I should see "OAuth 2 provider details"
    And I should see "No OAuth 2 providers have been created."
    And I should see "Add provider"
    And I should not see "ONE_NAME"
    And I should not see "ONE_DESCRIPTION"
    When I click on "Add provider" "button"
    Then I should see "Add OAuth 2 provider"

  Scenario: Create a OAuth2 provider
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 provider details" in site administration
    And I click on "Add provider" "button"
    Then I should see "Add OAuth 2 provider"
    And I should see "Required fields"
    And I set the field "Name" to "Test provider name"
    And I set the field "Description" to "Test provider description"
    When I click on "Add provider" "button" in the ".tui-oauth2ProviderForm__buttonGroup" "css_element"
    Then I should see "Provider added."
    And I ensure the "Test provider name" tui collapsible is expanded
    And I should see "Test provider name"
    And I should see "Test provider description"
    And I should see "Client ID"
    And I should see "Client secret"
    And I should see "Write access to Experience API (xAPI)"

  Scenario: Show and hide OAuth2 client secret
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 provider details" in site administration
    And I click on "Add provider" "button"
    Then I should see "Add OAuth 2 provider"
    And I should see "Required fields"
    And I set the field "Name" to "Test provider name"
    And I set the field "Description" to "Test provider description"
    When I click on "Add provider" "button" in the ".tui-oauth2ProviderForm__buttonGroup" "css_element"
    Then I should see "Provider added."
    And I ensure the "Test provider name" tui collapsible is expanded
    And I should see "Test provider name"
    And I should see "Test provider description"
    And I should see "Client ID"
    And I should see "Client secret"
    And I should see "Write access to Experience API (xAPI)"

    When I click on "Show client secret for Test provider name" "button"
    Then "input[type=text]" "css_element" should exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "input[type=password]" "css_element" should not exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "Show client secret for Test provider name" "button" should not exist
    And I click on "Hide client secret for Test provider name" "button"
    Then "input[type=password]" "css_element" should exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "input[type=text]" "css_element" should not exist in the "//div[@class='tui-inputGroup']" "xpath_element"

  Scenario: Delete a OAuth2 provider
    Given I am on a totara site
    And the following "client provider" exist in "totara_oauth2" plugin:
      | name     | description     | client_id    | client_secret    | scope      |
      | ONE_NAME | ONE_DESCRIPTION | ONE_CLIENTID | ONE_CLIENTSECRET | xapi:write |
    And I log in as "admin"
    And I navigate to "Server > OAuth 2 > OAuth 2 provider details" in site administration
    When I click on "Actions for ONE_NAME" "button"
    Then I should see "Delete"
    When I click on "Delete provider: ONE_NAME" "link"
    Then I should see "Confirm"
    And I should see "Are you sure you want to delete this OAuth 2 provider?"
    When I click on "Continue" "button"
    Then I should see "Provider deleted."
    And I should see "No OAuth 2 providers have been created."
    And I should not see "ONE_NAME"
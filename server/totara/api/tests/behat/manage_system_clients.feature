@totara @totara_api @javascript
Feature: API Client management is shown correctly in the admin menu.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |


  Scenario: I can see API > Client in the admin menu when the feature is enabled
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

  Scenario: I cannot see API > Client in the admin menu when the feature is disabled
    When I disable the "api" advanced feature
    And I log out
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should not exist in the "Administration" "block"
    And "API clients" "link" should not exist in the "Administration" "block"

  Scenario: Add and remove API clients in admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am <a> description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am <a> description"
    And I should see "Client ID"
    And I should see "Client secret"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Delete"
    And I confirm the tui confirmation modal
    Then I should see "Client deleted." in the tui success notification toast and close it
    And I should not see "Mr Client"
    And I should not see "I am <a> description"
    And I should not see "Client ID"
    And I should not see "Client secret"
    And I should see "No clients have been created."

  Scenario: Show and hide client secret of Api Clients in Admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should see "Client secret"
    And I should see "Enabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "No clients have been created."

    When I click on "Show" "button_exact" in the ".tui-form" "css_element"
    Then "input[type=text]" "css_element" should exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "input[type=password]" "css_element" should not exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "Show" "button_exact" should not exist
    And "Hide" "button_exact" should exist
    And I click on "Hide" "button_exact" in the ".tui-form" "css_element"
    Then "input[type=password]" "css_element" should exist in the "//div[@class='tui-inputGroup']" "xpath_element"
    And "input[type=text]" "css_element" should not exist in the "//div[@class='tui-inputGroup']" "xpath_element"

  Scenario: Enable and disable API clients in admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should see "Enabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Disable"
    Then I should see "Client disabled." in the tui success notification toast and close it
    And I should see "Mr Client (disabled)"
    And I should see "Disabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "Enabled" in the ".tui-totara_api-clients__form" "css_element"

    When I click on "Actions for Mr Client" "button"
    And I follow "Enable"
    Then I should see "Client enabled." in the tui success notification toast and close it
    And I should see "Mr Client"
    But I should not see "Mr Client (disabled)"
    And I should see "Enabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "Disabled" in the ".tui-totara_api-clients__form" "css_element"

  Scenario: Create disabled API client in admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."
    And I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Enabled" tui "checkbox"
    And I click on "Add" "button_exact"
    Then I should see "Mr Client (disabled)"

  Scenario: Edit API client details in admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should see "Enabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Edit client details"
    Then the field "Name" matches value "Mr Client"
    And the field "Description" matches value "I am a description"
    And the "Enabled" "checkbox" should be enabled
    When I set the field "Name" to "A different client"
    And I set the field "Description" to ""
    And I click on "Tag list Service account" "button"
    And I click on "User Two" option in the dropdown menu
    And I click on the "status" tui checkbox
    And I click on "Save" "button_exact"
    Then I should see "Changes saved." in the tui success notification toast and close it
    And I should see "A different client (disabled)"
    And I should not see "Mr Client"
    And I should not see "I am a description"
    And I should not see "User One"
    And I should see "User Two"

  Scenario: Edit API client settings in admin
    When I enable the "api" advanced feature
    And I log in as "admin"
    And I navigate to "Development > Debugging" in site administration
    # xpath required because partial match will find 'Web API' whether this is shown or not
    Then "//span[text()='API']" "xpath_element" should exist in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "Development > API > API clients" in site administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should see "Enabled" in the ".tui-totara_api-clients__form" "css_element"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Edit client settings"
    Then the field "Client rate limit" matches value "250000"
    And the field "Token expiration number" matches value "1"
    And the "Token expiration units" select box should contain "days"
    And the "Error response" select box should contain "Site default (Normal)"
    When I set the field "Client rate limit" to "0"
    And I set the field "Token expiration number" to "2147483647"
    And I click on "Save" "button_exact"
    Then I should see "Number must be 1 or more"
    And I should see "Duration must be 2147483647 seconds or less"
    When I set the field "Client rate limit" to "2147483647"
    When I set the field "Token expiration units" to "seconds"
    And I set the field "Error response" to "Developer"
    And I click on "Save" "button_exact"
    Then I should see "Changes saved." in the tui success notification toast and close it
    When I click on "Actions for Mr Client" "button"
    And I follow "Edit client settings"
    Then I should see "This client's rate limit exceeds the site limit, so the site limit is being enforced."
    And the field "Client rate limit" matches value "2147483647"
    And the field "Token expiration number" matches value "2147483647"
    And the field "Token expiration units" matches value "seconds"
    And the field "Error response" matches value "Developer"

  Scenario: Use multilang filter on API client name and description
    When I log in as "admin"
    And I enable the "api" advanced feature
    And the multi-language content filter is enabled
    And I navigate to "Development > API > API clients" in site administration
    And I click on "Add client" "link"
    # TODO TL-35198 add another language to Name and test after save.
    And I set the field "Name" to "<span class=\"multilang\" lang=\"en\">Mr Client</span>"
    And I set the field "Description" to "I am <span class=\"multilang\" lang=\"en\">Mister Client</span><span class=\"multilang\" lang=\"nl\">Mijnheer de Klant</span>."
    And I click on "Tag list Service account" "button"
    And I click on "User One" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Mr Client"
    And I should see "Mister Client"
    And I should not see "Klant"
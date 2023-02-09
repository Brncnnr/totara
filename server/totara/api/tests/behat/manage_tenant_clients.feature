@totara @totara_api @javascript
Feature: API Client management is shown correctly in the tenant category menu.

  Background:
    Given I am on a totara site
    And I enable the "api" advanced feature
    And tenant support is enabled with full tenant isolation
    And the following "tenants" exist:
      | name          | idnumber | categoryname | suspended |
      | First Tenant  | ten1     | T1           | 0         |
      | Second Tenant | ten2     | T2           | 0         |
      | Third Tenant  | ten3     | T3           | 1         |
    And the following "users" exist:
      | username | firstname | lastname | tenantmember | tenantdomainmanager |
      | t1dm     | T1        | Domain   | ten1         | ten1                |
      | t1u      | T1        | User     | ten1         |                     |
      | t1user   | T3        | User     | ten1         |                     |
      | t2dm     | T2        | Domain   | ten2         | ten2                |
      | t2u      | T2        | User     | ten2         |                     |
      | t3u      | T3        | User     | ten3         |                     |
      | system   | System    | User     |              |                     |
    And the following "system role assigns" exist:
      | user | role           |
      # Allow this user to manage categories, without being a domain manager.
      | t1u  | editingteacher |

  Scenario: I can see API > Client in the tenant category menu when the feature is enabled
    When I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

  Scenario: I can not see API > Client in the tenant category menu when the feature is disabled
    When I disable the "api" advanced feature
    And I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should not see "API" in the "Administration" "block"

  Scenario: I can not see API > Client in the tenant category menu if I don't have permission
    When I log in as "admin"
    When I set the following administration settings values:
      | catalogtype | moodle |
    And I log out
    And I log in as "t1u"
    And I click on "Courses" in the totara menu
    And I click on "T1" "link"
    # All the above is to get to a page with the category admin block on it
    # As an editingteacher we see it, but we shouldn't see the API link
    Then I should not see "API" in the "Administration" "block"

  Scenario: Add and remove API clients in tenant
    When I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "T1 User" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Delete"
    And I confirm the tui confirmation modal
    Then I should see "Client deleted." in the tui success notification toast and close it
    And I should not see "Mr Client"
    And I should not see "I am a description"
    And I should see "No clients have been created."

  Scenario: Enable and disable API clients in tenent
    When I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "T1 User" option in the dropdown menu
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "Mr Client"
    And I should see "I am a description"
    And I should not see "No clients have been created."

    When I click on "Actions for Mr Client" "button"
    And I follow "Delete"
    And I confirm the tui confirmation modal
    Then I should see "Client deleted." in the tui success notification toast and close it
    And I should not see "Mr Client"
    And I should not see "I am a description"
    And I should see "No clients have been created."

  Scenario: Edit API client details in tenent
    When I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "T1 User" option in the dropdown menu
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
    And I click on "T3 User" option in the dropdown menu
    And I click on the "status" tui checkbox
    And I click on "Save" "button_exact"
    Then I should see "Changes saved." in the tui success notification toast and close it
    And I should see "A different client (disabled)"
    And I should not see "Mr Client"
    And I should not see "I am a description"
    And I should not see "T1 User"
    And I should see "T3 User"

  Scenario: Edit API client settings in tenent
    When I log in as "t1dm"
    And I navigate to "Courses > Courses and categories" in site administration
    Then I should see "API" in the "Administration" "block"
    And I should not see "API clients" in the "Administration" "block"
    When I expand "API" node
    Then I should see "API clients" in the "Administration" "block"
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

    When I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "T1 User" option in the dropdown menu
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

  Scenario: Create suspended tenant client
    Given I log in as "admin"
    When I navigate to "Manage tenants" node in "Site administration > Tenants"
    And I click on "Third Tenant" "link"
    And I expand "API" node
    When I navigate to "API > API clients" in current page administration
    Then I should see "No clients have been created."

    And I click on "Add client" "link"
    And I set the field "Name" to "Mr Client"
    And I set the field "Description" to "I am a description"
    And I click on "Tag list Service account" "button"
    And I click on "T3 User" option in the dropdown menu
    And the "status" "checkbox" should be disabled
    And I click on "Add" "button_exact"
    Then I should see "Client added." in the tui success notification toast and close it
    And I should see "disabled"
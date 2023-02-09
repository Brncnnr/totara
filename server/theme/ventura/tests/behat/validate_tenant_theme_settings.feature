@core @theme @theme_ventura @javascript
Feature: Theme settings basic validations for tenants
  Theme settings should work as expected
  As a user
  I need to confirm that I can navigate to theme settings and see all the different elements

  Background:
    Given I log in as "admin"
    And I am on a totara site
    And tenant support is enabled with full tenant isolation
    And the following "tenants" exist:
      | name          | idnumber |
      | First Tenant  | ten1     |
      | Second Tenant | ten2     |
    And the following "users" exist:
      | username | firstname | lastname | tenantmember | tenantparticipant |
      | user1    | name1     | surname1 | ten1         |                   |
      | user2    | name2     | surname2 | ten2         |                   |
      | user3    | name3     | surname3 |              | ten1              |
      | user4    | name4     | surname4 |              | ten2              |
      | user5    | name5     | surname5 |              |                   |
    And the following "users" exist:
      | username | firstname | lastname | email          | tenantmember | tenantparticipant | tenantdomainmanager |
      | user6    | name6     | surname6 | user6@test.com | ten1         |                   | ten1                |
    And I navigate to "Ventura" node in "Site administration > Appearance > Themes"

  Scenario: Confirm we see the tenant selection page
    Then "Edit site brand" "link" should exist
    Then I should see the tui datatable contains:
      | Tenant        | Tenant identifier | Branding |
      | First Tenant  | ten1              | Site     |
      | Second Tenant | ten2              | Site     |

  Scenario: Confirm that we can navigate to site theme settings
    When I click on "Edit site brand" "link"
    Then I should see "Edit Ventura theme" in the ".tui-pageHeading" "css_element"
    And "Brand" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Colours" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Images" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Custom" "link" should exist in the ".tui-tabs__tabs" "css_element"

  Scenario: Confirm that all settings appear for tenant
    When I click on "Edit settings for First Tenant" "link"
    Then the "" tui "toggle_switch" should be off in the "Custom tenant branding" tui "form"

    When I click on the "Custom tenant branding" tui toggle button
    Then "Brand" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Colours" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Images" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And "Custom" "link" should exist in the ".tui-tabs__tabs" "css_element"
    And I should see "Logo" in the ".tui-tabContent" "css_element"
    And the URL for image nested in ".tui-tabs .tui-form .tui-formRow:nth-child(1)" should match "/theme\/image.php\/ventura\/totara_core\/[0-9]+\/logo/"
    And I should see "Logo alternative text" in the ".tui-tabContent" "css_element"
    And I should see "Favicon" in the ".tui-tabContent" "css_element"
    And the URL for image nested in ".tui-tabs .tui-form .tui-formRow:nth-child(3)" should match "/theme\/image.php\/ventura\/theme\/[0-9]+\/favicon/"

    When I click on "Colours" "link" in the ".tui-tabs__tabs" "css_element"
    And I click on "More colours" "button"
    Then the field "Primary brand colour" matches value "#4b7e2b"
    And the field "Header background colour" matches value "#ffffff"
    And the field "Header text colour" matches value "#262626"
    And the field "Page text colour" matches value "#262626"

    When I click on "Images" "link" in the ".tui-tabs__tabs" "css_element"
    Then the field "Display login page image" matches value "1"
    And the field "Login alternative text" matches value "Totara Login"
    And the URL for image nested in "#tabpanel-uid-6 .tui-collapsible:nth-child(1) .tui-formRow:nth-child(2)" should match "/theme\/image.php\/ventura\/totara_core\/[0-9]+\/default_login/"
    And I should not see "Learn" in the ".tui-collapsible__header" "css_element"
    And I should not see "Engage" in the ".tui-collapsible__header" "css_element"

    When I click on "Custom" "link" in the ".tui-tabs__tabs" "css_element"
    Then I should see "Custom footer" in the ".tui-tabContent:nth-of-type(4)" "css_element"
    And I should not see "Custom CSS" in the ".tui-tabContent:nth-of-type(4)" "css_element"

  Scenario: Edit tenant settings
    When I click on "Edit settings for First Tenant" "link"
    And I click on the "Custom tenant branding" tui toggle button
    And I click on "Colours" "link" in the ".tui-tabs__tabs" "css_element"
    And I set the field "Primary brand colour" to "#FF000B"
    And I set the field "Accent colour" to "#00FFE6"
    And I click on "Save Colours Settings" "button"
    And I click on "Custom" "link" in the ".tui-tabs__tabs" "css_element"
    And I set the field "Custom footer" to "First Tenant Footer"
    And I click on "Save Custom Settings" "button"
    And I reload the page

    # Confirm that nothing changed for admin user who uses 'site' theme colours
    Then element ":root" should have a css property "--color-state" with a value of "#4b7e2b"
    And element ":root" should have a css property "--color-primary" with a value of "#69bd45"

    # Confirm that tenant member sees new color
    When I log out
    And I log in as "user1"
    Then element ":root" should have a css property "--color-state" with a value of "#FF000B"
    And element ":root" should have a css property "--color-primary" with a value of "#00FFE6"

    # Confirm that tenant member from another tenancy sees site color
    When I log out
    And I log in as "user2"
    Then element ":root" should have a css property "--color-state" with a value of "#4b7e2b"
    And element ":root" should have a css property "--color-primary" with a value of "#69bd45"

    # Confirm that tenant participant sees site color
    When I log out
    And I log in as "user3"
    Then element ":root" should have a css property "--color-state" with a value of "#4b7e2b"
    And element ":root" should have a css property "--color-primary" with a value of "#69bd45"

    # Confirm that the tenant participants do not see the tenant footer
    When I log out
    Then I should not see "First Tenant Footer"

    # Confirm that tenant member sees the tenant footer
    When I log in as "user1"
    And I log out
    Then I should see "First Tenant Footer"

  Scenario: Log in as tenant domain manager and confirm that we can send test emails
    Given I log out
    And I log in as "user6"
    And I navigate to "Ventura" node in "Site administration > Appearance"
    And I click on the "Custom tenant branding" tui toggle button
    And I click on "Brand" "link" in the ".tui-tabs__tabs" "css_element"

    # Confirm that notification settings are present
    When I click on "Email notifications" "button"
    Then I should see "HTML header" in the ".tui-tabContent" "css_element"
    And I should see "HTML footer" in the ".tui-tabContent" "css_element"
    And I should see "Plain-text footer" in the ".tui-tabContent" "css_element"

    # Confirm that email gets sent
    When I reset the email sink
    And I click on "Test email notification" "button"
    Then the following emails should have been sent:
      | To             | Subject        | Body              |
      | user6@test.com | This is a test | Body of the email |
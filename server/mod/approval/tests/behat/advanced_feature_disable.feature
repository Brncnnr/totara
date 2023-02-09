@totara @mod_approval @javascript @vuejs
Feature: Disable Approval Workflows feature at site-level

  Scenario: Disable in the advanced features page
    Given I log in as "admin"
    And I expand "Site administration" node
    Then I should see "Approval workflows"

    When I navigate to "Experimental settings" node in "Site administration > Development > Experimental"
    And I set the field "Enable Approval Workflows" to "0"
    And I press "Save changes"
    Then I should see "Changes saved"

    When I am on homepage
    And I expand "Site administration" node
    Then I should not see "Approval workflows"

  Scenario: Disable main menu navigation
    Given I log in as "admin"
    When I navigate to "Navigation > Main menu" in site administration
    And I click on "Edit" "link" in the "Approval" "table_row"
    And I set the following Totara form fields to these values:
      | Parent item | Top |
    And I press "Save changes"
    Then I should see "Main menu has been updated successfully"
    And I should see "Approval" in the totara menu
    And I should see "Applications" in the totara menu

    When I disable the "approval_workflows" advanced feature
    And I reload the page
    And I should not see "Approval" in the totara menu
    And I should not see "Applications" in the totara menu

  Scenario: Hide report source in user reports interface
    Given I log in as "admin"
    When I navigate to "Reports > Manage user reports" in site administration
    And I click on "Create report" "button"
    And I set the field with xpath "//input[@id='search_input']" to "Approval"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I wait for pending js
    Then I should see "Approval Workflow Types" in the ".totara_reportbuilder__createreport_list" "css_element"
    And I should see "Approval Workflows Applications" in the ".totara_reportbuilder__createreport_list" "css_element"

    When I disable the "approval_workflows" advanced feature
    And I reload the page
    And I wait for pending js
    And I set the field with xpath "//input[@id='search_input']" to "Approval"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I wait for pending js
    Then I should see "0 records shown"
    Then I should not see "Approval Workflow Types" in the ".totara_reportbuilder__createreport_list" "css_element"
    And I should not see "Approval Workflows Applications" in the ".totara_reportbuilder__createreport_list" "css_element"
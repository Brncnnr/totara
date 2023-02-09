@totara @mod_approval @javascript @vuejs
Feature: Edit formviews
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Mana      | Geer     | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname          | idnumber |
      | Default Framework | ODF      |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | org_framework | fullname | shortname | idnumber | org_parent |
      | ODF           | Agency   | org       | org      |            |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name              |
      | Workflow type one |
    And the following "forms" exist in "mod_approval" plugin:
      | title         |
      | Test form uno |
    And the following "form versions" exist in "mod_approval" plugin:
      | form          | version | json_schema |
      | Test form uno | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name           | description       | id_number | form          | workflow_type     | type         | identifier |
      | Draft workflow | draft description | WKF001    | Test form uno | Workflow type one | organisation | org        |
    And the following "workflow versions" exist in "mod_approval" plugin:
      | workflow | form_version | status |
      | WKF001   | 1            | draft  |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name    | type            |
      | WKF001   | Stage 1 | FORM_SUBMISSION |
      | WKF001   | Stage 2 | APPROVALS       |
      | WKF001   | Stage 3 | FINISHED        |
    And I log in as "manager"
    And I navigate to "Manage approval workflows" node in "Site administration > Approval workflows"
    And I click on "Draft workflow" "link"
    And I click on "Stage 1" "button"

  Scenario: Change visibility
    # Change formview visibility
    When I select "Visible" in "Visibility of section 'Section A - Food & drinks'" radio group
    Then "Visibility options for What beverage do you want, Editable selected" "button" should exist
    When I click on "Visibility options for What beverage do you want" "button"
    And I click on "Editable and required" option in the dropdown menu
    Then "Visibility options for What beverage do you want, Editable and required selected" "button" should exist
    When I reload the page
    Then "Visibility options for What beverage do you want, Editable and required selected" "button" should exist

    # Separate stages have separate formviews
    When I click on "Stage 2" "button"
    Then "Visibility options for What beverage do you want, Editable and required selected" "button" should not exist
    And I click on "Stage 1" "button"

    # Hide and show sections - fields hidden, visibility remembered
    When I select "Hidden" in "Visibility of section 'Section A - Food & drinks'" radio group
    Then "Visibility options for What beverage do you want, Editable selected" "button" should not exist
    And I select "Visible" in "Visibility of section 'Section A - Food & drinks'" radio group
    Then "Visibility options for What beverage do you want, Editable and required selected" "button" should exist

    # Preview
    When I click on "Preview form" "button"
    And I switch to "totara_approval_workflow_form_view_preview" window
    Then I should see "What beverage do you want"
    And I should see "What food do you want"
    Then I close the current window
    And I switch to the main window

    When I select "Hidden" in "Visibility of section 'Section A - Food & drinks'" radio group
    And I click on "Preview form" "button"
    Then I should not see "What beverage do you want"
    And I should not see "What food do you want"
    Then I close the current window
    And I switch to the main window

    # Reload and check visibility
    When I reload the page
    And I select "Visible" in "Visibility of section 'Section A - Food & drinks'" radio group
    Then "Visibility options for What food do you want, Hidden selected" "button" should exist
    Then "Visibility options for What beverage do you want, Hidden selected" "button" should exist

    # Should not be able to fill fields
    When I set the field "What food do you want" to "Durian"
    Then the field "What food do you want" matches value ""

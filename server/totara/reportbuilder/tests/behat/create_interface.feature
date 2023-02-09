@totara @totara_reportbuilder @javascript
Feature: Test the create report interface
  To filter the users in a report by several appraisers at a time
  As an authenticated user
  I need to use the all appraisers filter

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I enable the "appraisals" advanced feature
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    Then I should see "Learner goal status overview"

  Scenario: Create report interface excludes sources and templates from disabled features
    When I disable the "goals" advanced feature
    And I set the field "search_input" to "goal"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I should see "0 records shown"

  Scenario: Search text filtering works on the create report page
    When I set the field "search_input" to "Manager"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    Then I should see "Manager goal status overview"
    And I should not see "Learner goal status overview"

    When I click on "Remove" "link" in the ".tw-selectRegionPanel__content" "css_element"
    Then I should see "Learner goal status overview"

    # Test the load more functionality
    When I set the field "search_input" to "m"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I should see "Learner program completion status overview"
    And I should not see "Learner goal status overview"

    # This should be on the next page
    And I should not see "Goal Custom Fields"

    When I click on "Load more" "button"
    Then I should see "Goal Custom Fields"
    # Confirm filter was applied for next page
    And I should not see "Site Logs"

    When I click on "Clear all" "link"
    Then I should see "Learner goal status overview"
    Then I should see "Manager goal status overview"

  Scenario: Template filtering works on report creation page
    When I click on "Administrator" "link"
    Then I should see "Admin site activity overview"
    And I should not see "Learner goal status overview"
    And I should not see "Manager goal status overview"
    And I should not see "Seminar Assets"

    When I click on "Learner" "link"
    Then I should see "Admin site activity overview"
    And I should see "Learner goal status overview"
    And I should not see "Manager goal status overview"
    And I should not see "Seminar Assets"

    # Deselect Administrator
    When I click on "Administrator" "link"
    Then I should not see "Admin site activity overview"
    And I should see "Learner goal status overview"
    And I should not see "Manager goal status overview"
    And I should not see "Seminar Assets"

    When I click on "Clear all" "link"
    Then I should see "Admin site activity overview"
    And I should see "Learner goal status overview"
    And I should see "Manager goal status overview"
    And I should see "Appraisal Detail (legacy)"

  Scenario: Report sources filtering works on report creation page
    Given I disable the "competency_assignment" advanced feature
    When I click on "Certification" "link" in the ".tw-selectRegionPanel" "css_element"
    Then I should see "Certification Completion"
    And I should not see "Record of Learning: Competencies"
    And I should not see "Manager goal status overview"
    And I should not see "Seminar Assets"

    When I click on "Learning" "link" in the ".tw-selectRegionPanel" "css_element"
    Then I should see "Certification Completion"
    And I should see "Record of Learning: Competencies"
    And I should not see "Manager goal status overview"
    And I should not see "Admin site activity overview"

    # Deselect Administrator
    When I click on "Certification" "link" in the ".tw-selectRegionPanel" "css_element"
    Then I should not see "Certification Completion"
    And I should see "Record of Learning: Competencies"
    And I should not see "Manager goal status overview"
    And I should not see "Admin site activity overview"

    When I click on "Clear all" "link"
    Then I should see "Admin site activity overview"
    And I should see "Learner goal status overview"
    And I should see "Manager goal status overview"
    And I should see "Admin site activity overview"

  Scenario: Multiple filtering works on report creation page
    When I set the field "search_input" to "Completion"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I click on "Learner" "link"
    And I click on "Program" "link" in the ".tw-selectRegionPanel" "css_element"
    Then I should see "Program Completion"
    And I should see "Learner certification completion status"
    And I should not see "Course completion by Organisation"
    And I should not see "Learner goal status overview"
    And I should not see "Admin site activity overview"
    And I should not see "Manager goal status overview"

    When I click on "Clear all" "link"
    And I should see "Admin site activity overview"
    And I should see "Learner goal status overview"
    And I should see "Manager goal status overview"

  Scenario: Create report and view from template
    When I click on "Learner goal status overview" "text"
    Then I should see "This report gives the logged in user an overview"
    And I should see "Default columns"
    And I should see "Goal Name"

    When I click on "Create and view" "button"
    Then I should see "Learner goal status overview"
    And I should see "There are no records in this report"

  Scenario: Create report and edit from template
    When I click on "Learner goal status overview" "text"
    Then I should see "This report gives the logged in user an overview"
    And I should see "Default columns"
    And I should see "Goal Name"

    When I click on ".totara_reportbuilder__report_create_details_buttons button:nth-child(2)" "css_element"
    Then I should see "Learner goal status overview"
    And I should see "Edit Report"
    And the field "Report Title" matches value "Learner goal status overview"
    And I should not see "There are no records in this report"

  Scenario: Create report and view from report source
    When I click on "Audience Members" "text"
    Then I should see "Audience information including information on users who are members of the audience."
    And I should see "Default columns"
    And I should see "Audience Name"

    When I click on "Create and view" "button"
    Then I should see "Audience Members"
    And I should see "There are no records in this report"

  Scenario: Create report and edit from source
    When I click on "Audience Members" "text"
    Then I should see "Audience information including information on users who are members of the audience."
    And I should see "Default columns"
    And I should see "Audience Name"

    When I click on ".totara_reportbuilder__report_create_details_buttons button:nth-child(2)" "css_element"
    Then I should see "Audience Members"
    And I should see "Edit Report"
    And the field "Report Title" matches value "Audience Members"
    And I should not see "There are no records in this report"

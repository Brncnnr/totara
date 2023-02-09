@totara @core @core_course
Feature: Course with Collapsible sections setting
  in order to not see collapse section for the first section

  @javascript
  Scenario: I can not see 'Default collapse state' setting for the first section
    Given I am on a totara site
    And I log in as "admin"
    And the following "courses" exist:
      | fullname | shortname | format   | numsections |
      | Course 1 | C1        | topics   | 4           |
    And I click on "Show admin menu window" "button"
    And I click on "Courses and categories" "link" in the "#quickaccess-popover-content" "css_element"
    And I click on "Edit" "link" in the ".course-item-actions" "css_element"
    And I expand all fieldsets
    And I click on "Collapsible sections" "checkbox"
    And I click on "Save and display" "button"
    And I click on "Turn editing on" "button"
    And I click on "Edit" "link" in the "#action-menu-7-menubar" "css_element"
    When I click on "Edit section" "link"
    Then I should not see "Default collapse state"
    And I should see "CSS classes"
    And I click on "Cancel" "button"
    And I click on "Edit" "link" in the "#action-menu-8-menubar" "css_element"
    When I click on "Edit topic" "link" in the "//li[contains(@id,'section-1')]" "xpath_element"
    Then I should see "Default collapse state"
    And I should see "CSS classes"

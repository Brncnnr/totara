@totara @totara_reportbuilder @javascript
Feature: Verify the blank date filters are reset after hitting the Clear button.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | Learner1 | learner1@example.com |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name            |
      | learner1 | Learning Plan 1 |
    And the following "objectives" exist in "totara_plan" plugin:
      | user     | plan            | name        |
      | learner1 | Learning Plan 1 | Objective 1 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname             | source       |
      | RoL Objectives | report_rol_objectives | dp_objective |
    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "RoL Objectives"

  Scenario: Verify blank date is cleared for 'Date Created' and 'Date Updated' filters when the Clear button is hit.
    When I switch to "Filters" tab
    And I select "Date Created" from the "newstandardfilter" singleselect
    And I press "Add"
    And I wait until "a[class='deletefilterbtn action-icon']" "css_element" exists
    And I select "Date Updated" from the "newstandardfilter" singleselect
    And I press "Add"
    And I wait until "a[class='movefilterupbtn action-icon']" "css_element" exists
    And I follow "View This Report"
    And I set the field "objective-timecreatednotset" to "1"
    And I set the field "objective-timemodifiednotset" to "1"
    And I click on "input[value=Search]" "css_element"
    Then "input[name=objective-timecreatednotset][checked=checked]" "css_element" should exist
    And "input[name=objective-timemodifiednotset][checked=checked]" "css_element" should exist
    When I click on "input[value=Clear]" "css_element"
    Then "input[name=objective-timecreatednotset][checked=checked]" "css_element" should not exist
    And "input[name=objective-timemodifiednotset][checked=checked]" "css_element" should not exist

@totara @totara_reportbuilder @javascript
Feature: Check that courses reports columns and fields work as expected

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | trainer1 | Trainer   | One      | trainer1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
      | Course 2 | C2        | 1                |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname      | source  |
      | Courses Report | report_courses | courses |


  Scenario: Mobile compatibility column and filter test
    Given I log in as "admin"
    And I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1 |
    And I click on "Save changes" "button"
    When I am on "Course 2" course homepage
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | Mobile-friendly course | Yes |
    And I click on "Save and display" "button"
    When I navigate to my "Courses Report" report
    And I press "Edit this report"
    And I switch to "Columns" tab
    And I add the "Course mobile compatibility" column to the report
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Course mobile compatibility"
    And I click on "Save changes" "button"
    And I follow "View This Report"
    # Check the values of the mobile compatibility field.
    Then I should see "No" in the "course_mobilecompatible" report column for "Course 1"
    And I should see "Yes" in the "course_mobilecompatible" report column for "Course 2"
    # Check the results of the mobile compatibility filter.
    When I set the field "Course mobile compatibility" to "No"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Course 1"
    And I should not see "Course 2"
    When I set the field "Course mobile compatibility" to "Yes"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "Course 1"
    And I should see "Course 2"

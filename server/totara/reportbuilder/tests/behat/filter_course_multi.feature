@totara @totara_reportbuilder @javascript
Feature: Use the multi-item course filter
  To filter the courses in a report
  by several courses at a time
  I need to use the multi-item course filter

  Background:
    Given I am on a totara site
    # Audience visibility: 3 is 'No users' and 2 is 'All users'.
    And the following "courses" exist:
      | fullname    | shortname | audiencevisible |
      | CourseOne   | Course1   | 3               |
      | CourseTwo   | Course2   | 2               |
      | CourseThree | Course3   | 2               |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | one      | user1@example.com |
    And I log in as "admin"
    And the following config values are set as admin:
      | audiencevisibility | 1 |

  Scenario: Use filter with Courses report source
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source  |
      | Courses  | courses   | courses |
    And I navigate to my "Courses" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "Authenticated User" to "1"
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    And the "Choose Courses" "button" should be disabled
    When I select "is equal to" from the "Course (multi-item)" singleselect
    Then the "Choose Courses" "button" should be enabled
    When I press "Choose Courses"
    And I click on "Miscellaneous" "link" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "CourseOne" "link" in the "Choose Courses" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Choose Courses" "totaradialogue"
    And I click on "Save" "button" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I select "isn't equal to" from the "Course (multi-item)" singleselect
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I press "Save this search"
    And I set the field "Search Name" to "Not1or2"
    And I click on "Shared" "radio"
    And I press "Save changes"
    Then I should see "Saved searches"
    And I should see "View a saved search"
    When I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I click on "Courses" "link" in the ".reportmanager" "css_element"
    And I set the field "sid" to "Not1or2"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  Scenario: Use filter with Courses report source and ensure changing operator back to any course clears pre-selected items
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source  |
      | Courses  | courses   | courses |
    And I navigate to my "Courses" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    And the "Choose Courses" "button" should be disabled
    When I select "is equal to" from the "Course (multi-item)" singleselect
    Then the "Choose Courses" "button" should be enabled
    When I press "Choose Courses"
    And I click on "Miscellaneous" "link" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "CourseOne" "link" in the "Choose Courses" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Choose Courses" "totaradialogue"
    And I click on "Save" "button" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I select "is any value" from the "Course (multi-item)" singleselect
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  Scenario: Use filter with Courses report source and ensure I can delete pre-selected items
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source  |
      | Courses  | courses   | courses |
    And I navigate to my "Courses" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    And the "Choose Courses" "button" should be disabled
    When I select "is equal to" from the "Course (multi-item)" singleselect
    Then the "Choose Courses" "button" should be enabled
    When I press "Choose Courses"
    And I click on "Miscellaneous" "link" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    And I click on "CourseOne" "link" in the "Choose Courses" "totaradialogue"
    And I click on "CourseTwo" "link" in the "Choose Courses" "totaradialogue"
    And I click on "CourseThree" "link" in the "Choose Courses" "totaradialogue"
    And I click on "Save" "button" in the "Choose Courses" "totaradialogue"
    And I wait "1" seconds
    Then I should see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseThree" in the "Course (multi-item)" "fieldset"
    When I click on "//div[contains(text(),'CourseOne')]//a[contains(@class, 'action-icon')]" "xpath_element"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseThree" in the "Course (multi-item)" "fieldset"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseThree" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
    When I click on "//div[contains(text(),'CourseTwo')]//a[contains(@class, 'action-icon')]" "xpath_element"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseThree" in the "Course (multi-item)" "fieldset"
    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "CourseOne" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseTwo" in the "Course (multi-item)" "fieldset"
    And I should see "CourseThree" in the "Course (multi-item)" "fieldset"
    And I should not see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  Scenario: Test filter with spaces
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname | shortname | source  |
      | Courses  | courses   | courses |
    When I navigate to my "Courses" report
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"
  # Use normal search
    When I set the field "course-fullname" to "CourseOne"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should not see "CourseThree" in the ".reportbuilder-table" "css_element"
  # Use search with spaces
    When I set the field "course-fullname" to "    "
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "CourseOne" in the ".reportbuilder-table" "css_element"
    And I should see "CourseTwo" in the ".reportbuilder-table" "css_element"
    And I should see "CourseThree" in the ".reportbuilder-table" "css_element"

  Scenario: Add filter with Seminar Sessions report source
    Given the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname         | shortname          | source             |
      | Seminar Sessions | facetoface_summary | facetoface_summary |
    And I navigate to my "Seminar Sessions" report
    And I press "Edit this report"
    And I switch to "Filters" tab
    And I select "Course (multi-item)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    When I follow "View This Report"
    Then I should see "Course (multi-item)"

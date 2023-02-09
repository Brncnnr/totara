@totara @perform @totara_competency @competency_achievement @javascript
Feature: Manage Criteria group achievement paths
  In order for users to achieve a rating in a competency through sets of criteria
  I need to add a Criteria based achievement paths in the competency's achievement criteria

  Background:
    Given I am on a totara site
    And a competency scale called "ggb" exists with the following values:
      | name    | description          | idnumber       | proficient | default | sortorder |
      | Great   | Is great at doing it | great          | 1          | 0       | 1         |
      | Good    | Is ok at doing it    | good           | 0          | 0       | 2         |
      | Bad     | Has no idea          | bad            | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname             | idnumber | description                    | scale  |
      | Competency Framework | fw1      | Framework for Competencies     | ggb    |
    And the following "competency" hierarchy exists:
      | framework | fullname   | idnumber | description       | parent |
      | fw1       | Parent     | parent   | Parent competency |        |
      | fw1       | Child1     | child1   | First child       | parent |
      | fw1       | Child2     | child2   | Second child      | parent |
      | fw1       | Another    | another  | Some other        |        |
    And the following "courses" exist:
      | fullname    | shortname | enablecompletion |
      | Course 1    | course1   | 1                |
      | Course 2    | course2   | 1                |
      | Course 3    | course3   | 1                |
      | No tracking | notrack   | 0                |

  Scenario: Manage Criteria group basics with coursecompletion
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "No achievement paths added"
    And the "Apply changes" "button" should be disabled

    When I add a "singlevalue" pathway
    And I wait for pending js
    Then I should see the following singlevalue scale values:
      | name  |
      | Great |
      | Good  |
      | Bad   |
    And I should not see "No achievement paths added"
    And the "Apply changes" "button" should be disabled
    And the "Criteria-based paths" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"

    When I add a criteria group with "coursecompletion" criterion to "Good" scalevalue
    And I wait for pending js
    Then I should see "coursecompletion" criterion in criteria group "1" in "Good" scalevalue
    When I click on "Remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    # Adding another wait for now as pending js seems to end too soon - may be due to js_pending and js_complete in different js files?
    And I wait for the next second
    Then I should see "0" criteria groups in "Good" scalevalue

    When I add a criteria group with "coursecompletion" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "No courses added" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    When I click on "Add courses" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    And I toggle the legacy adder list entry "Course 1" in "Select courses"
    And I save my legacy selections and close the "Select courses" adder
    And I wait for pending js
    Then I should not see "No courses added" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I should see "Course 1" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    When I click on "Add courses" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then the legacy adder list entry "Course 1" in "Select courses" should not be enabled
    When I toggle the legacy adder list entry "Course 3" in "Select courses"
    And I toggle the legacy adder list entry "No tracking" in "Select courses"
    And I save my legacy selections and close the "Select courses" adder
    And I wait for pending js
    Then I should see "Course 1" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I should see "Course 3" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "No tracking" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "Course completion not possible (completion not tracked, or completion settings not valid)" error for "No tracking" item in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    # Reload to ensure all saved and retrieved correctly
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "coursecompletion" criterion in criteria group "1" in "Good" scalevalue
    And I should see error indicator for "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "Course 1" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I should see "Course 3" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I should see "No tracking" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I should see "Course completion not possible (completion not tracked, or completion settings not valid)" error for "No tracking" item in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    When I click on "Remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then "Remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue should not be visible
    And "Undo remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue should be visible

    When I click on "Undo remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then "Remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue should be visible
    And "Undo remove criteria" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue should not be visible

    When I remove "No tracking" item in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should not see error indicator for "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue


  Scenario: Manage Criteria group basics with othercompetency
    Given I log in as "admin"
    # Fist ensure user can achieve proficiency in Child1 competency
    And I navigate to the competency achievement paths page for the "Child1" competency
    And I add a "learning_plan" pathway
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    # Now for the test
    When I navigate to the competency achievement paths page for the "Parent" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "othercompetency" criterion to "Bad" scalevalue
    And I wait for pending js
    Then I should see "othercompetency" criterion in criteria group "1" in "Bad" scalevalue
    When I click on "Remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    # Adding another wait for now as pending js seems to end too soon - may be due to js_pending and js_complete in different js files?
    And I wait for the next second
    Then I should see "0" criteria groups in "Bad" scalevalue

    When I add a criteria group with "othercompetency" criterion to "Bad" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    Then I should see "No competencies added" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue

    When I click on "Add competencies" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    And I toggle the legacy adder list entry "Child1" in "Select competencies"
    And I save my legacy selections and close the "Select competencies" adder
    And I wait for pending js
    Then I should not see "No competencies added" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I should see "Child1" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue

    When I click on "Add competencies" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    Then the legacy adder list entry "Child1" in "Select competencies" should not be enabled
    When I toggle the legacy adder list entry "Child2" in "Select competencies"
    And I save my legacy selections and close the "Select competencies" adder
    And I wait for pending js
    Then I should see "Child1" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I should see "Child2" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    When I toggle criterion detail of "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    Then I should see "Proficiency not possible due to invalid criteria on this competency" error for "Child2" item in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue

    # Reload to ensure all saved and retrieved correctly
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "othercompetency" criterion in criteria group "1" in "Bad" scalevalue
    And I should see error indicator for "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    When I toggle criterion detail of "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    Then I should see "Child1" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I should see "Child2" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I should see "Proficiency not possible due to invalid criteria on this competency" error for "Child2" item in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue

    When I click on "Remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    Then "Remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue should not be visible
    And "Undo remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue should be visible

    When I click on "Undo remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    Then "Remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue should be visible
    And "Undo remove criteria" "button" in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue should not be visible

    When I remove "Child2" item in "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should not see error indicator for "othercompetency" criterion "1" in criteria group "1" in "Bad" scalevalue


  Scenario: Manage Criteria group basics with childcompetency
    Given I log in as "admin"
    # Fist ensure user can achieve proficiency in Child1 competency
    And I navigate to the competency achievement paths page for the "Child1" competency
    And I add a "learning_plan" pathway
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    # Now for the test
    When I navigate to the competency achievement paths page for the "Parent" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "childcompetency" criterion to "Good" scalevalue
    And I wait for pending js
    Then I should see "childcompetency" criterion in criteria group "1" in "Good" scalevalue
    When I click on "Remove criteria" "button" in "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    # Adding another wait for now as pending js seems to end too soon - may be due to js_pending and js_complete in different js files?
    And I wait for the next second
    Then I should see "0" criteria groups in "Good" scalevalue

    When I add a criteria group with "childcompetency" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    Then criterion aggregation should be set to complete "all" in "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    And the "Apply changes" "button" should be enabled

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should see "Proficiency not possible due to invalid criteria on one or more child competency" in "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue

    # Now also ensure that users can become proficient in Child2
    And I navigate to the competency achievement paths page for the "Child2" competency
    And I add a "learning_plan" pathway
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    When I navigate to the competency achievement paths page for the "Parent" competency
    Then I should not see error indicator for "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should not see "Proficiency not possible due to invalid criteria on one or more child competency" in "childcompetency" criterion "1" in criteria group "1" in "Good" scalevalue


  Scenario: Manage Criteria group basics with linkedcourses
    Given the following "linked courses" exist in "totara_competency" plugin:
      | competency | course  | mandatory |
      | child1     | course1 | 1         |
      | child1     | course3 | 0         |
      | child2     | course1 | 1         |
      | child2     | notrack | 0         |
    And I log in as "admin"

    # No linked courses
    When I navigate to the competency achievement paths page for the "Another" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "linkedcourses" criterion to "Good" scalevalue
    And I wait for pending js
    Then I should see "linkedcourses" criterion in criteria group "1" in "Good" scalevalue
    When I click on "Remove criteria" "button" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    # Adding another wait for now as pending js seems to end too soon - may be due to js_pending and js_complete in different js files?
    And I wait for the next second
    Then I should see "0" criteria groups in "Good" scalevalue

    When I add a criteria group with "linkedcourses" criterion to "Good" scalevalue
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should see "No courses linked to the competency" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue

    # Linked courses can all be completed
    When I navigate to the competency achievement paths page for the "Child1" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "linkedcourses" criterion to "Good" scalevalue
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should not see error indicator for "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should not see "No courses linked to the competency" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue

    # Not enough linked courses
    When I set criterion aggregation to complete "3" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should see "Not enough linked courses – link more courses, or reduce the number required" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue

    # Error is displayed when any of the linked courses can not be completed
    When I navigate to the competency achievement paths page for the "Child2" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "linkedcourses" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I set criterion aggregation to complete "1" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see error indicator for "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I wait for pending js
    Then I should see "Course completion not possible in one or more linked courses (completion not tracked, or completion settings not valid)" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue


  Scenario: Manage Criteria group basics with onactivate
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Another" competency

    # onactivate initially available on all scalevalues
    And I add a "singlevalue" pathway
    And I wait for pending js
    Then the "onactivate" criterion type option should be enabled in "Great" scalevalue
    And the "onactivate" criterion type option should be enabled in "Good" scalevalue
    And the "onactivate" criterion type option should be enabled in "Bad" scalevalue

    # onactivate not available with any other type
    When I add a criteria group with "childcompetency" criterion to "Great" scalevalue
    And I wait for pending js
    Then I should see "childcompetency" criterion in criteria group "1" in "Great" scalevalue
    And the "onactivate" criterion type option should be disabled in "Great" scalevalue
    And the "onactivate" criterion type option should be enabled in "Good" scalevalue
    And the "onactivate" criterion type option should be enabled in "Bad" scalevalue
    And the "onactivate" criterion type option should be disabled in criteria group "1" in "Great" scalevalue

    # onactivate not available anywhere when added
    When I add a criteria group with "onactivate" criterion to "Bad" scalevalue
    And I wait for pending js
    Then I should see "onactivate" criterion in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    And "Add" "button" in "Bad" scalevalue should not be visible
    And "Add" "button" in criteria group "1" in "Bad" scalevalue should not be visible
    And the "onactivate" criterion type option should be disabled in "Great" scalevalue
    And the "onactivate" criterion type option should be disabled in "Good" scalevalue

    When I click on "Remove criteria" "button" in "onactivate" criterion "1" in criteria group "1" in "Bad" scalevalue
    And I wait for pending js
    Then "Add" "button" in "Bad" scalevalue should be visible
    And the "onactivate" criterion type option should be disabled in "Great" scalevalue
    And the "onactivate" criterion type option should be enabled in "Good" scalevalue
    And the "onactivate" criterion type option should be enabled in "Good" scalevalue

    #check reload after save
    When I add a criteria group with "onactivate" criterion to "Bad" scalevalue
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    And I navigate to the competency achievement paths page for the "Another" competency
    Then I should see "onactivate" criterion in criteria group "1" in "Bad" scalevalue
    And "Add" "button" in "Bad" scalevalue should not be visible
    And "Add" "button" in criteria group "1" in "Bad" scalevalue should not be visible
    And the "onactivate" criterion type option should be disabled in "Great" scalevalue
    And the "onactivate" criterion type option should be disabled in "Good" scalevalue


  Scenario: Criterion item aggregation in Criteria groups
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Another" competency
    And I add a "singlevalue" pathway
    And I wait for pending js
    And I add a criteria group with "childcompetency" criterion to "Great" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue
    Then criterion aggregation should be set to complete "all" in "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue
    And I set criterion aggregation to complete "2" in "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue

    When I add a "coursecompletion" criterion to criteria group "1" in "Great" scalevalue
    And I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue
    Then criterion aggregation should be set to complete "all" in "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue
    And I set criterion aggregation to complete "3" in "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue

    When I add a criteria group with "linkedcourses" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    Then criterion aggregation should be set to complete "all" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I set criterion aggregation to complete "4" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue

    When I add a criteria group with "othercompetency" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue
    Then criterion aggregation should be set to complete "all" in "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue
    And I set criterion aggregation to complete "5" in "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue

    When I add a criteria group with "onactivate" criterion to "Bad" scalevalue
    And I wait for pending js
    Then the "Apply changes" "button" should be enabled

    #check reload after save
    When I click on "Apply changes" "button"
    And I wait for pending js
    And I navigate to the competency achievement paths page for the "Another" competency
    Then criterion aggregation should be set to complete "2" in "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue
    And criterion aggregation should be set to complete "3" in "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue
    And criterion aggregation should be set to complete "4" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And criterion aggregation should be set to complete "5" in "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue

    #set to invalid reqItems
    When I toggle criterion detail of "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue
    And I set criterion aggregation to complete "0" in "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue
    Then I should see "Reset to 1 – this is the minimum number of child competencies that can be required for completion"
    And criterion aggregation should be set to complete "1" in "childcompetency" criterion "1" in criteria group "1" in "Great" scalevalue

    When I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue
    And I set criterion aggregation to complete "0" in "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue
    Then I should see "Reset to 1 – this is the minimum number of courses that can be required for completion"
    And criterion aggregation should be set to complete "1" in "coursecompletion" criterion "1" in criteria group "1" in "Great" scalevalue

    When I toggle criterion detail of "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    And I set criterion aggregation to complete "0" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "Reset to 1 – this is the minimum number of linked courses that can be required for completion"
    And criterion aggregation should be set to complete "1" in "linkedcourses" criterion "1" in criteria group "1" in "Good" scalevalue

    When I toggle criterion detail of "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue
    And I set criterion aggregation to complete "0" in "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue
    Then I should see "Reset to 1 – this is the minimum number of competencies that can be required for completion"
    And criterion aggregation should be set to complete "1" in "othercompetency" criterion "1" in criteria group "2" in "Good" scalevalue

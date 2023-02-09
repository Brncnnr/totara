@totara @perform @totara_competency @competency_achievement @javascript
Feature: Manage Competency achievement paths

  Background:
    Given I am on a totara site
    And a competency scale called "ggb" exists with the following values:
      | name  | description          | idnumber | proficient | default | sortorder |
      | Great | Is great at doing it | great    | 1          | 0       | 1         |
      | Good  | Is ok at doing it    | good     | 0          | 0       | 2         |
      | Bad   | Has no idea          | bad      | 0          | 1       | 3         |
    And the following "competency" frameworks exist:
      | fullname                         | idnumber    | description                | scale |
      | Competency Framework             | fw1         | Framework for Competencies | ggb   |
      | Single item competency framework | single_item | Framework for Competencies | ggb   |
    And the following "competency" hierarchy exists:
      | framework   | fullname           | idnumber           | description                   | parent |
      | fw1         | Parent             | parent             | Parent competency             |        |
      | fw1         | Child1             | child1             | First child                   | parent |
      | fw1         | Child2             | child2             | Second child                  | parent |
      | fw1         | Another            | another            | Some other                    |        |
      | single_item | Single item parent | Single item parent | Single item parent competency |        |
    And the following "assignments" exist in "totara_competency" plugin:
      | competency         | user_group_type | user_group |
      | Single item parent | user            | admin      |
    And the following "courses" exist:
      | fullname    | shortname | enablecompletion |
      | Course 1    | course1   | 1                |
      | Course 2    | course2   | 1                |
      | Course 3    | course3   | 1                |
      | No tracking | notrack   | 0                |

  Scenario: Add multiple achievement paths for a competency
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "No achievement paths added"
    And the "Apply changes" "button" should be disabled

    When I add a "manual" pathway
    And I wait for pending js
    Then I should see "manual" pathway "before" criteria groups

    When I add a "manual" pathway
    And I wait for pending js
    And I click on "Add raters" "button" in "manual" pathway "2" "before" criteria groups
    And I toggle the legacy adder list entry "Self" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I wait for pending js
    Then I should see "Self" in "manual" pathway "2" "before" criteria groups

    When I click on "Add raters" "button" in "manual" pathway "1" "before" criteria groups
    And I toggle the legacy adder list entry "Manager" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I wait for pending js
    Then I should see "Manager" in "manual" pathway "1" "before" criteria groups

    When I add a "singlevalue" pathway
    Then I should see the following singlevalue scale values:
      | name  |
      | Great |
      | Good  |
      | Bad   |
    And the "Criteria-based paths" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"

    When I add a "learning_plan" pathway
    And I wait for pending js
    Then I should see "learning_plan" pathway "after" criteria groups
    And the "Learning plan" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"

    When I add a criteria group with "coursecompletion" criterion to "Good" scalevalue
    And I wait for pending js
    And I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I click on "Add courses" "button" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    And I toggle the legacy adder list entry "Course 1" in "Select courses"
    And I save my legacy selections and close the "Select courses" adder
    And I wait for pending js
    Then I should see "Course 1" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    # Reload to ensure all saved and retrieved correctly
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "Manager" in "manual" pathway "1" "before" criteria groups
    And I should see "Manager" in "manual" pathway "1" "before" criteria groups
    And I should see "learning_plan" pathway "after" criteria groups
    And the "Learning plan" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"
    And I should see "coursecompletion" criterion in criteria group "1" in "Good" scalevalue
    When I toggle criterion detail of "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue
    Then I should see "Course 1" in "coursecompletion" criterion "1" in criteria group "1" in "Good" scalevalue

  Scenario: Removing multi-value pathways doesn't impact the display of criteria-based block
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Parent" competency
    Then I should see "No achievement paths added"

    When I add a "singlevalue" pathway
    Then I should see the following singlevalue scale values:
      | name  |
      | Great |
      | Good  |
      | Bad   |
    When I add a "manual" pathway
    And I wait for pending js
    Then I should see "manual" pathway "after" criteria groups
    And "Remove pathway" "button" should be visible in "manual" pathway "1" "after" criteria groups

    When I click on "Remove pathway" "button" in "manual" pathway "1" "after" criteria groups
    And I wait for pending js
    Then I should not see "manual" pathway "after" criteria groups
    And I should not see "No achievement paths added"
    And I should see the following singlevalue scale values:
      | name  |
      | Great |
      | Good  |
      | Bad   |

  Scenario: Achievement path warnings are shown if achievement paths need attention
    # Top level manage competencies page (all frameworks).
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    Then I should see "Competency Framework" in the "#frameworkstable" "css_element"
    And I should see "Some achievement paths need review" in the "#frameworkstable .lastrow" "css_element"

    # Manage competencies page (single framework).
    When I click on "Single item competency framework" "link"
    Then I should see "Single item parent" in the ".hierarchy-index" "css_element"
    And I should see "Some of these competencies have achievement paths which need to be reviewed." in the ".alert-warning" "css_element"
    And I should see "Competencies cannot be rated without a valid achievement path." in the ".alert-warning" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchy-index" "css_element"
    When I click on "Hide details" "button"
    Then I should see "Achievement paths need review" in the ".hierarchy-index" "css_element"
    And I should not see "Description: Single item parent competency"

    # Manage assignments page.
    When I click on "Manage competency assignments" "link"
    Then I should see "Single item parent" in the "[data-tw-list-row=1]" "css_element"
    And I should see "Achievement paths need review" in the "[data-tw-list-row=1]" "css_element"

    # Create assignments page.
    When I click on "Create assignments" "link_or_button"
    Then I should see "Single item parent" in the "[data-tw-list-row=5]" "css_element"
    And I should see "Achievement paths need review" in the "[data-tw-list-row=5]" "css_element"
    # Purpose of test is to confirm that the warning is shown when creating assignments
    # Not actually doing assignment to avoid intermittent failure on dropdown items

    # View only competency summary page.
    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Single item competency framework" "link"
    And I click on "Single item parent" "link"
    Then I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    # Add a valid achievement path.
    When I click on "Edit" "link" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    And I add a "manual" pathway
    And I wait for pending js
    Then I should see "manual" pathway

    When I click on "Add raters" "button" in "manual" pathway
    And I toggle the legacy adder list entry "Manager" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I wait for pending js

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should not see "No achievement paths added"

    # View only competency summary page.
    When I click on "Back to Competency page" "link"
    Then I should not see "The achievement paths for this competency need review."
    Then I should not see "Competencies cannot be rated without a valid achievement path."
    And I should not see "No achievement paths added"
    And I should see "Back to Single item competency framework"

    # Manage competencies page (single framework).
    When I click on "Back to Single item competency framework" "link"
    Then I should see "Single item parent" in the ".hierarchy-index" "css_element"
    And I should not see "Some of these competencies have achievement paths which need to be reviewed."
    And I should not see "Achievement paths need review" in the ".hierarchy-index" "css_element"

    # Top level manage competencies page (all frameworks).
    When I click on "Back to all competency frameworks" "link"
    Then I should not see "Some achievement paths need review" in the "#frameworkstable .lastrow" "css_element"

    # Manage assignments page.
    When I click on "Manage competency assignments" "link"
    Then I should see "Single item parent" in the "[data-tw-list-row=1]" "css_element"
    And I should not see "Achievement paths need review" in the "[data-tw-list-row=1]" "css_element"

    # Create assignments page.
    When I click on "Create assignments" "link_or_button"
    Then I should see "Single item parent" in the "[data-tw-list-row=5]" "css_element"
    And I should not see "Achievement paths need review" in the "[data-tw-list-row=5]" "css_element"

  Scenario: Achievement path validation is updated through the full hierarchy
    Given the following "competency" frameworks exist:
      | fullname              | idnumber | description                            | scale |
      | Multi-level Framework | multi    | Framework for multiple level hierarchy | ggb   |
    And the following "competency" hierarchy exists:
      | framework | fullname      | idnumber       | description       | parent    |
      | multi     | Ml-Parent     | ml-parent      | Parent competency |           |
      | multi     | Ml-Child      | ml-child       | Child             | ml-parent |
      | multi     | Ml-LowerChild | ml-lower-child | Child of child    | ml-child  |
    When I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    Then I should see "Multi-level Framework" in the "#frameworkstable" "css_element"
    When I click on "Multi-level Framework" "link"
    Then I should see "Ml-Parent" in the ".hierarchyitem.depth1" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth1" "css_element"
    And I should see "Ml-Child" in the ".hierarchyitem.depth2" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth2" "css_element"
    And I should see "Ml-LowerChild" in the ".hierarchyitem.depth3" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth3" "css_element"

    # Add child competency criteria to the hierarchy
    # Then add manual to the lowest child and verify that validity is changed on all levels
    When I click on "Ml-Parent" "link"
    Then I should see "No achievement paths added"
    When I navigate to the competency achievement paths page for the "Ml-Parent" competency
    When I add a "singlevalue" pathway
    And I wait for pending js
    When I add a criteria group with "childcompetency" criterion to "Great" scalevalue
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    When I click on "Back to Competency page" "link"
    And I wait for pending js
    Then I should see "Back to Multi-level Framework"

    When I click on "Back to Multi-level Framework" "link"
    And I wait for pending js
    And I click on "Ml-Child" "link"
    Then I should see "No achievement paths added"
    When I navigate to the competency achievement paths page for the "Ml-Child" competency
    When I add a "singlevalue" pathway
    And I wait for pending js
    When I add a criteria group with "childcompetency" criterion to "Great" scalevalue
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    When I click on "Back to Competency page" "link"
    And I wait for pending js
    Then I should see "Back to Multi-level Framework"

    # All competencies should still show the warning
    When I click on "Back to Multi-level Framework" "link"
    And I wait for pending js
    Then I should see "Ml-Parent" in the ".hierarchyitem.depth1" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth1" "css_element"
    And I should see "Ml-Child" in the ".hierarchyitem.depth2" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth2" "css_element"
    And I should see "Ml-LowerChild" in the ".hierarchyitem.depth3" "css_element"
    And I should see "Achievement paths need review" in the ".hierarchyitem.depth3" "css_element"

    # Now add a Manual path on the lowest level and ensure validation is checked on all levels
    And I click on "Ml-LowerChild" "link"
    Then I should see "No achievement paths added"
    When I navigate to the competency achievement paths page for the "Ml-LowerChild" competency
    When I add a "manual" pathway
    And I wait for pending js
    And I click on "Add raters" "button" in "manual" pathway
    And I wait for pending js
    And I toggle the legacy adder list entry "Manager" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I wait for pending js
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    When I click on "Back to Competency page" "link"
    And I wait for pending js
    Then I should see "Back to Multi-level Framework"

    # No warnings should be shown
    When I click on "Back to Multi-level Framework" "link"
    And I wait for pending js
    Then I should see "Ml-Parent" in the ".hierarchyitem.depth1" "css_element"
    And I should not see "Achievement paths need review" in the ".hierarchyitem.depth1" "css_element"
    And I should see "Ml-Child" in the ".hierarchyitem.depth2" "css_element"
    And I should not see "Achievement paths need review" in the ".hierarchyitem.depth2" "css_element"
    And I should see "Ml-LowerChild" in the ".hierarchyitem.depth3" "css_element"
    And I should not see "Achievement paths need review" in the ".hierarchyitem.depth3" "css_element"


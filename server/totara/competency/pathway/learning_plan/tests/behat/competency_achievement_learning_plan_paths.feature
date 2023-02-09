@totara @perform @totara_competency @competency_achievement @javascript
Feature: Manage Learning plan achievement paths
  In order for users to achieve a rating in a competency through legacy learning plans
  I need to add a Learning plan achievement path in the competency's achievement criteria

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname             | idnumber |
      | Competency Framework | fw       |
    And the following "competency" hierarchy exists:
      | framework | fullname  | idnumber | description      |
      | fw        | Comp1     | comp1    | First competency |

  Scenario: Add and Remove Learning plan achievement path
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Comp1" competency
    Then I should see "No achievement paths added"
    And the "Apply changes" "button" should be disabled

    When I add a "learning_plan" pathway
    Then I should see "learning_plan" pathway
    And I should not see "No achievement paths added"
    And the "Apply changes" "button" should be enabled
    And the "Learning plan" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"
    And "Remove pathway" "button" should be visible in "learning_plan" pathway

    When I click on "Remove pathway" "button" in "learning_plan" pathway
    Then I should see "No achievement paths added"
    And the "Learning plan" "option" should be enabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"

    When I add a "learning_plan" pathway
    Then I should see "learning_plan" pathway
    When I click on "Apply changes" "button"
    Then I should see "Changes applied successfully"
    When I follow "Back to Competency page"
    Then I should see "Any scale value" in the ".tui-competencySummaryAchievementConfiguration__scaleValue" "css_element"
    And I should see "Learning plan" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    # When a new learning plan is added while an removal of an existing one has not yet been applied, you are
    # not allowed to undo the removal of the original pathway
    When I navigate to the competency achievement paths page for the "Comp1" competency
    Then I should see "learning_plan" pathway
    And the "Learning plan" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"
    When I click on "Remove pathway" "button" in "learning_plan" pathway
    And I add a "learning_plan" pathway
    Then "Remove pathway" "button" should not be visible in "learning_plan" pathway "1"
    And "Undo remove pathway" "button" should be visible in "learning_plan" pathway "1"
    And "Remove pathway" "button" should be visible in "learning_plan" pathway "2"
    And "Undo remove pathway" "button" should not be visible in "learning_plan" pathway "2"

    When I click on "Undo remove pathway" "button" in "learning_plan" pathway "1"
    Then I should see "This pathway can only be used once per competency."

    When I click on "Remove pathway" "button" in "learning_plan" pathway "2"
    And I click on "Undo remove pathway" "button" in "learning_plan" pathway "1"
    Then "Remove pathway" "button" should be visible in "learning_plan" pathway "1"
    And I should not see "This pathway can only be used once per competency."

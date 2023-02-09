@totara @perform @totara_competency @competency_achievement @javascript
Feature: Manage Manual rating achievement paths
  In order for users to achieve a rating in a competency through someone manually giving a rating
  As an admin
  I need to add a Manual rating achievement path in the competency's achievement criteria

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname             | idnumber |
      | Competency Framework | fw       |
    And the following "competency" hierarchy exists:
      | framework | fullname  | idnumber | description      |
      | fw        | Comp1     | comp1    | First competency |

  Scenario: Add Manual achievement path
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Comp1" competency
    Then I should see "No achievement paths added"
    And the "Apply changes" "button" should be disabled

    When I add a "manual" pathway
    And I wait for pending js
    Then I should not see "No achievement paths added"
    And I should see "manual" pathway
    And I should see "No raters added" in "manual" pathway
    And the "Apply changes" "button" should be enabled
    And I wait for pending js

    When I click on "Add raters" "button" in "manual" pathway
    And I wait for pending js
    And I toggle the legacy adder list entry "Manager" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I should not see "No raters added" in "manual" pathway
    And I should see "Manager" in "manual" pathway

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    # Reload to ensure all saved and retrieved correctly
    And I navigate to the competency achievement paths page for the "Comp1" competency
    Then I should see "manual" pathway
    And I should see "Manager" in "manual" pathway

    When I click on "Add raters" "button"
    And I wait for pending js
    Then the legacy adder list entry "Manager" in "Select raters" should not be enabled
    When I toggle the legacy adder list entry "Self" in "Select raters"
    When I toggle the legacy adder list entry "Appraiser" in "Select raters"
    And I save my legacy selections and close the "Select raters" adder
    And I wait for pending js
    Then I should see "Manager" in "manual" pathway
    Then I should see "Self" in "manual" pathway
    Then I should see "Appraiser" in "manual" pathway

    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    When I follow "Back to Competency page"
    And I wait for pending js
    Then I should see "Any scale value" in the ".tui-competencySummaryAchievementConfiguration__scaleValue-header" "css_element"
    And I should see "Manual rating" in the ".tui-competencySummaryAchievementCriteria__criterion-header" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementCriteria__criterion-items" "css_element"
    And I should see "Self" in the ".tui-competencySummaryAchievementCriteria__criterion-items" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementCriteria__criterion-items" "css_element"

  Scenario: Remove Manual achievement path
    Given I log in as "admin"
    When I navigate to the competency achievement paths page for the "Comp1" competency
    And I add a "manual" pathway
    And I wait for pending js
    Then I should see "manual" pathway
    And "Remove pathway" "button" should be visible in "manual" pathway
    And "Undo remove pathway" "button" should not be visible in "manual" pathway
    And I wait for pending js

    When I click on "Remove pathway" "button" in "manual" pathway
    And I wait for pending js
    Then I should see "No achievement paths added"

    When I add a "manual" pathway
    And I wait for pending js
    Then I should see "manual" pathway
    And I wait for pending js
    When I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"

    When I wait for pending js
    And I click on "Remove pathway" "button" in "manual" pathway
    Then I should see "manual" pathway
    And "Remove pathway" "button" should not be visible in "manual" pathway
    And "Undo remove pathway" "button" should be visible in "manual" pathway

    When I click on "Undo remove pathway" "button" in "manual" pathway
    Then "Remove pathway" "button" should be visible in "manual" pathway
    And "Undo remove pathway" "button" should not be visible in "manual" pathway

    When I click on "Remove pathway" "button" in "manual" pathway
    And I click on "Apply changes" "button"
    And I wait for pending js
    Then I should see "Changes applied successfully"
    And I should see "No achievement paths added"

    When I follow "Back to Competency page"
    Then I should see "No achievement paths added"


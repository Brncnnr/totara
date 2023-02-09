@totara @perform @totara_competency @pathway_perform_rating @javascript @vuejs
Feature: Add a perform rating pathway to a competency

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname             | idnumber |
      | Competency Framework | fw       |
    And the following "competency" hierarchy exists:
      | framework | fullname  | idnumber | description      |
      | fw        | Comp1     | comp1    | First competency |

  Scenario: Add and remove the pathway
    Given I log in as "admin"
    And I navigate to the competency achievement paths page for the "Comp1" competency
    Then I should see "No achievement paths added"
    And the "Apply changes" "button" should be disabled

    When I add a "perform_rating" pathway
    Then I should not see "No achievement paths added"
    And I should see "perform_rating" pathway
    And I should see "Performance activity"
    And the "Apply changes" "button" should be enabled
    And the "Performance activity" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"
    When I click on "Apply changes" "button"
    Then I should see "Changes applied successfully"
    And the "Performance activity" "option" should be disabled in the "[data-tw-editachievementpaths-add-pathway]" "css_element"

    When I click on "Remove pathway" "button" in "perform_rating" pathway
    And I add a "perform_rating" pathway
    Then "Remove pathway" "button" should not be visible in "perform_rating" pathway "1"
    And "Undo remove pathway" "button" should be visible in "perform_rating" pathway "1"
    And "Remove pathway" "button" should be visible in "perform_rating" pathway "2"
    And "Undo remove pathway" "button" should not be visible in "perform_rating" pathway "2"

    When I click on "Undo remove pathway" "button" in "perform_rating" pathway "1"
    Then I should see "This pathway can only be used once per competency."
    And the "Apply changes" "button" should be enabled

    When I click on "Apply changes" "button"
    Then I should see "Changes applied successfully"
    And I should see "perform_rating" pathway

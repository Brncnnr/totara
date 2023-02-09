@totara @contentmarketplace_linkedin @totara_contentmarketplace @javascript
Feature: Create course from content marketplace Linkedin Learning
  Background:
    Given I set up the "linkedin" content marketplace plugin
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level        | asset_type    | time_to_complete | time_to_complete_unit |
      | A   | Course A | BEGINNER     | COURSE        | 30               | MINUTE                |
      | B   | Course B | INTERMEDIATE | LEARNING_PATH | 4                | HOUR                  |
      | C   | Course C | ADVANCED     | VIDEO         | 5                | SECOND                |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |

  Scenario: Create courses from catalog import should make course catalog order by latest
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "The courses have been successfully created" in the ".alert-success" "css_element"
    And I should see "Latest"
    And I should not see "Alphabetical"
    And I should see "Course A"
    And I should not see "Course B"
    And I should not see "Course C"

    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "2" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "The courses have been successfully created" in the ".alert-success" "css_element"
    And "Course B" "text" should appear before "Course A" "text"
    And I should not see "Course C"

    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "3" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "The courses have been successfully created" in the ".alert-success" "css_element"
    And "Course C" "text" should appear before "Course A" "text"
    And "Course C" "text" should appear before "Course B" "text"
    And "Course B" "text" should appear before "Course A" "text"

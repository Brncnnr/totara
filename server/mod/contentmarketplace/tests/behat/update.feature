@mod @javascript @mod_contentmarketplace @totara @contentmarketplace_linkedin @totara_contentmarketplace
Feature: Update content marketplace activity within course
  Background:
    Given I set up the "linkedin" content marketplace plugin
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn          | title     |
      | urn:course:1 | Hibernate |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |

  Scenario: Create course from learning object and update activity.
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I should see "Hibernate"
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    When I click on "Find Learning" in the totara menu
    Then I should see "Hibernate"
    And I am on "Hibernate" course homepage
    And I click on "Administration" "button"
    And I click on "Edit settings" "link"
    Then I should see "Updating: Content marketplace"
    And the field "Name" matches value "Hibernate"
    And I follow "Common module settings"
    Then I should see "ID number"
    And the field "ID number" matches value ""
    And I set the field "ID number" to "lil_101"
    When I click on "Save and display" "button"
    Then I should see "Hibernate"
    And I click on "Administration" "button"
    And I click on "Edit settings" "link"
    And I follow "Common module settings"
    And the field "ID number" matches value "lil_101"

  Scenario: Create course from learning object and update course.
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I should see "Hibernate"
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    When I click on "Find Learning" in the totara menu
    Then I should see "Hibernate"
    And I am on "Hibernate" course homepage
    And I click on "Administration" "button"
    And I click on "Course administration" "button"
    And I click on "Edit settings" "link" in the "//div[@aria-label='Course administration']" "xpath_element"
    Then I should see "Edit course settings"
    And the field "Course full name" matches value "Hibernate"
    And I follow "Course format"
    Then I should see "Type of activity"
    And the field "Type of activity" matches value "Content marketplace"
    When I press "Save and display"
    Then I should see "Hibernate"
    And I should see "Course details"
    And I should not see "Edit course settings"

  Scenario: Create course from learning object and update with completion.
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I should see "Hibernate"
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    And I click on "Create course(s)" "button"
    When I click on "Find Learning" in the totara menu
    Then I should see "Hibernate"
    And I am on "Hibernate" course homepage
    And I click on "Administration" "button"
    And I click on "Edit settings" "link"
    And I follow "Activity completion"
    And I should see "Completion condition"
    And I should see "Show activity as complete when LinkedIn Learning conditions have been met"
    And the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be enabled

    When I set the field "Completion tracking" to "Learners can manually mark the activity as completed"
    Then the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be disabled

    When I set the field "Completion tracking" to "Show activity as complete when conditions are met"
    Then the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be enabled
    And I set the field "Show activity as complete when LinkedIn Learning conditions have been met" to "1"
    When I click on "Save and display" "button"
    Then I should see "Hibernate"
    And I click on "Administration" "button"
    And I click on "Edit settings" "link"
    When I follow "Activity completion"
    Then the field "Show activity as complete when LinkedIn Learning conditions have been met" matches value "1"
    And the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be enabled
    When I set the field "Completion tracking" to "Do not indicate activity completion"
    Then the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be disabled
    And I click on "Save and display" "button"
    And I should see "Hibernate"
    And I click on "Administration" "button"
    And I click on "Edit settings" "link"
    And I follow "Activity completion"
    Then the field "Show activity as complete when LinkedIn Learning conditions have been met" matches value "0"
    And the "Show activity as complete when LinkedIn Learning conditions have been met" "field" should be disabled

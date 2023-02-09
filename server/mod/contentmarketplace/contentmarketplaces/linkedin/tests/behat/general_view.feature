@mod @mod_contentmarketplace @contentmarketplace_linkedin @totara @totara_contentmarketplace @javascript
Feature: View content marketplace linkedin as activity within course
  Background:
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level    | asset_type | locale_language | locale_country |
      | A   | Course A | BEGINNER | COURSE     | en              | US             |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name  | type    | locale_language | locale_country |
      | category:1 | J2EE  | LIBRARY | en              | US             |
      | category:2 | JDBC  | SUBJECT | en              | US             |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |
    And I set up the "linkedin" content marketplace plugin
    And the following "users" exist:
      | username  | firstname | lastname | email                 |
      | learner   | Learner   | One      | learner@example.com   |

  Scenario: Course back url display depends upon course format
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "Course A"
    And I am on "Course A" course homepage
    And "Course A" "link" should not exist
    # Change the course into multi activities course
    And I click on "Administration" "button"
    # A slightly hack to let us navigate to course edit page.
    And I click on "Edit settings" "link"
    And I navigate to "Edit settings" node in "Course administration"
    And I follow "Course format"
    And I set the field "Format" to "topics"
    And I wait for the next second
    When I click on "Save and display" "button"
    Then I should see "Topic 1"
    And I should see "Topic 2"
    When I follow "Course A"
    Then "Course A" "link" should exist

  Scenario: View linkedin activity page depends on learners' capability
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I set the field "Select category" to "Category A"
    And I click on "Next: Review" "button"
    When I click on "Create course(s)" "button"
    Then I should see "Course A"
    And I am on "Course A" course homepage
    And I click on "Administration" "button"
    And I press "Course administration"
    And I press "Users"
    And I click on "Enrolment methods" "link"
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    And I click on "Enable" "link" in the "Guest access" "table_row"
    And I log out

    And I log in as "learner"
    And I am on "Course A" course homepage
    When I click on "Enrol to course Course A" "button"
    And I log out

    And I log in as "admin"
    And I set the following system permissions of "Learner" role:
      | capability                  | permission |
      | mod/contentmarketplace:view | Prevent    |
    And I log out

    And I log in as "learner"
    And I am on "Course A" course homepage
    Then I should see "Sorry, this activity is currently hidden"

@totara @block @block_current_learning @javascript
Feature: Users can view the current learning block in tiles view

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname      | shortname | enablecompletion |
      | Tile view | tv        | 1               |
    And the following "users" exist:
      | username |
      | learner  |
    And the following "course enrolments" exist:
      | user    | course | role    |
      | learner | tv     | student |
    And I log in as "admin"
    And I am on "Tile view" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Site Manager" to "1"
    And I click on "Save changes" "button"
    And I log out

  Scenario: A user can select tile view for the current learning block
    Given I log in as "learner"
    Then ".block_current_learning-tiles" "css_element" should not exist

    When I click on "Customise this page" "button"
    And I configure the "Current Learning" block
    And I expand all fieldsets
    And I set the field "Tile view" to "1"
    And I click on "Save changes" "button"
    And I click on "Stop customising this page" "button"
    Then ".block_current_learning-tiles" "css_element" should exist


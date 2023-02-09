@enrol @enrol_self @cohort
Feature: Self enrolment with audiences
  Background:
    Given the following "courses" exist:
      | fullname   | shortname | format |
      | Course 101 | C101      | topics |
    And the following "cohorts" exist:
      | name       | idnumber |
      | Audience 1 | a1       |

  @javascript
  Scenario: Delete audience should not break self enrolment.
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course 101" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    And I click on "Enable" "link" in the "Self enrolment (Learner)" "table_row"
    When I click on "Edit" "link" in the "Self enrolment (Learner)" "table_row"
    Then I should see "Self enrolment"
    And I set the field "Only audience members" to "Audience 1 [a1]"
    And I click on "Save changes" "button"
    And I am on homepage
    And I navigate to "Audiences > Audiences" in site administration
    When I click on "Delete" "link" in the "Audience 1" "table_row"
    Then I should see "Delete audience: Audience 1 (a1)"
    And I click on "Delete" "button"
    And I am on "Course 101" course homepage
    And I navigate to "Users > Enrolment methods" in current page administration
    When I click on "Edit" "link" in the "Self enrolment (Learner)" "table_row"
    Then I should see "Self enrolment"
    And the field "Only audience members" matches value "Unknown audience (1)!"
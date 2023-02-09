@totara @totara_cohort
Feature: Test dynamic audience with job assignment idnumbers.
  In order to compute the members of a cohort with dynamic membership
  As an admin
  I should be able to use job assignment idnumber field values for filter rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                  |
      | itsec    | Secretary | IT       | secretary1@example.com |
      | itdev    | Developer | IT       | developer1@example.com |
      | itmgr    | Manager   | IT       | manager1@example.com   |
      | finsec   | Secretary | Fin      | secretary2@example.com |
      | findev   | Developer | Fin      | developer2@example.com |
      | finmgr   | Manager   | Fin      | manager2@example.com   |
      | newbie   | Newbie    | Recruit  | recruit@example.com    |
    And the following job assignments exist:
      | user   | fullname      | idnumber |
      | itsec  | IT Secretary  | 1-2      |
      | itdev  | IT Developer  | 1-3      |
      | itmgr  | IT Manager    | 1-4      |
      | finsec | Fin Secretary | 2-2      |
      | findev | Fin Developer | 2-3      |
      | finmgr | Fin Manager   | 2-4      |
      | newbie |               | 5        |
    And the following "cohorts" exist:
      | name         | idnumber | cohorttype |
      | TestAudience | D1       | 2          |

    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "TestAudience"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "alljobassign-idnumber"

  @javascript
  Scenario: cohort_job_assignment_idnumber_01: "contains" general values
    When I set the field "equal" to "contains"
    And I set the field "listofvalues" to "1"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Secretary IT" in the "#cohort_members" "css_element"
    And I should see "Developer IT" in the "#cohort_members" "css_element"
    And I should see "Manager IT" in the "#cohort_members" "css_element"
    And I should not see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should not see "Developer Fin" in the "#cohort_members" "css_element"
    And I should not see "Manager Fin" in the "#cohort_members" "css_element"
    And I should not see "Newbie Recruit" in the "#cohort_members" "css_element"

  @javascript
  Scenario: cohort_job_assignment_idnumber_02: "starts with" specific values
    When I set the field "equal" to "starts with"
    And I set the field "listofvalues" to "2-"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should see "Developer Fin" in the "#cohort_members" "css_element"
    And I should see "Manager Fin" in the "#cohort_members" "css_element"
    And I should not see "Secretary IT" in the "#cohort_members" "css_element"
    And I should not see "Developer IT" in the "#cohort_members" "css_element"
    And I should not see "Manager IT" in the "#cohort_members" "css_element"
    And I should not see "Newbie Recruit" in the "#cohort_members" "css_element"

  @javascript
  Scenario: cohort_job_assignment_idnumber_03: "contains" mutiple, specific values
    When I set the field "equal" to "contains"
    And I set the field "listofvalues" to "1-2,2-2"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Secretary IT" in the "#cohort_members" "css_element"
    And I should see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should not see "Developer IT" in the "#cohort_members" "css_element"
    And I should not see "Manager IT" in the "#cohort_members" "css_element"
    And I should not see "Developer Fin" in the "#cohort_members" "css_element"
    And I should not see "Manager Fin" in the "#cohort_members" "css_element"
    And I should not see "Newbie Recruit" in the "#cohort_members" "css_element"

  @javascript
  Scenario: cohort_job_assignment_idnumber_03: "contains" specific, unknown value
    When I set the field "equal" to "contains"
    And I set the field "listofvalues" to "zzz"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "There are no records in this report"

  @javascript
  Scenario: cohort_job_assignment_idnumber_05: "does not contain" specific, unknown values
    When I set the field "equal" to "does not contain"
    And I set the field "listofvalues" to "zzz"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Secretary IT" in the "#cohort_members" "css_element"
    And I should see "Developer IT" in the "#cohort_members" "css_element"
    And I should see "Manager IT" in the "#cohort_members" "css_element"
    And I should see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should see "Developer Fin" in the "#cohort_members" "css_element"
    And I should see "Manager Fin" in the "#cohort_members" "css_element"
    And I should see "Newbie Recruit" in the "#cohort_members" "css_element"

  @javascript
  Scenario: cohort_job_assignment_idnumber_06: "does not contain" mutiple, specific values
    When I set the field "equal" to "does not contain"
    And I set the field "listofvalues" to "1-2,2-2,5"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "Secretary IT" in the "#cohort_members" "css_element"
    And I should see "Developer IT" in the "#cohort_members" "css_element"
    And I should see "Manager IT" in the "#cohort_members" "css_element"
    And I should not see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should see "Developer Fin" in the "#cohort_members" "css_element"
    And I should see "Manager Fin" in the "#cohort_members" "css_element"
    And I should not see "Newbie Recruit" in the "#cohort_members" "css_element"

  @javascript
  Scenario: cohort_job_assignment_idnumber_07: "does not contain" general values
    When I set the field "equal" to "does not contain"
    And I set the field "listofvalues" to "2-,5"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "Secretary IT" in the "#cohort_members" "css_element"
    And I should see "Developer IT" in the "#cohort_members" "css_element"
    And I should see "Manager IT" in the "#cohort_members" "css_element"
    And I should not see "Secretary Fin" in the "#cohort_members" "css_element"
    And I should not see "Developer Fin" in the "#cohort_members" "css_element"
    And I should not see "Manager Fin" in the "#cohort_members" "css_element"
    And I should not see "Newbie Recruit" in the "#cohort_members" "css_element"

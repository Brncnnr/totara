@enrol @javascript @totara @enrol_totara_facetoface @mod_facetoface
Feature: Admin can change default Seminar direct enrolment plugin settings
  In order to change Seminar direct enrolment settings
  As a admin
  I need to enable Seminar direct enrolment plugin

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course | approvaltype |
      | Test seminar name | <p>Test seminar description</p> | C1     | 0            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
      | Test seminar name | event 2 |
      | Test seminar name | event 3 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start               | finish               |
      | event 1      | 1 Jan next year 9am | 1 Jan next year 10am |
      | event 2      | 2 Jan next year 9am | 2 Jan next year 10am |
      | event 3      | 3 Jan next year 9am | 3 Jan next year 10am |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I am on "Test seminar name" seminar homepage
    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Seminar direct enrolment |
    And I log out

  Scenario: Change Enrolment displayed on course page setting from default setting to a new one
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Booking open" in the "1 January" "table_row"
    And I should see "Booking open" in the "2 January" "table_row"
    And I should see "Booking open" in the "3 January" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Test seminar name" seminar homepage
    And I navigate to "Enrolment methods" node in "Course administration > Users"
    And I click on "Edit" "link" in the "Seminar direct enrolment" "table_row"
    And I set the following fields to these values:
      | Enrolments displayed on course page | 2 |
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Booking open" in the "1 January" "table_row"
    And I should see "Booking open" in the "2 January" "table_row"
    And I should not see "3 January" in the "Booking open" "table_row"
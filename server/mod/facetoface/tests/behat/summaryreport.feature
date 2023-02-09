@javascript @mod @mod_facetoface @totara @totara_reportbuilder
Feature: Seminar sessions report overview
  In order to see all required information
  As an admin
  I need to configure seminar summary report and see all required information

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | teacher1  | Terry3    | Teacher  | teacher@example.com  |
      | student1  | Sam1      | Student1 | student1@example.com |
      | student2  | Sam2      | Student2 | student2@example.com |
      | student3  | Sam3      | Student3 | student3@example.com |
      | student4  | Sam4      | Student4 | student4@example.com |
      | student5  | Sam5      | Student5 | student5@example.com |
      | student6  | Sam6      | Student6 | student6@example.com |
      | student7  | Sam7      | Student7 | student7@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | teacher1 | C2     | editingteacher |
      | student4 | C2     | student        |
      | student5 | C2     | student        |
      | student6 | C2     | student        |
    And I log in as "admin"

    # Enable roles for student and trainer
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Learner" "checkbox" in the "#admin-facetoface_session_roles" "css_element"
    # Trainer is ambiguous with Editing Trainer
    And I click on "s__facetoface_session_roles[4]" "checkbox" in the "#admin-facetoface_session_roles" "css_element"
    And I press "Save changes"

    # Prepare report
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname        | shortname              | source             |
      | Seminar Summary | report_seminar_summary | facetoface_summary |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Seminar Summary"
    And I switch to "Columns" tab
    And I set the field "newcolumns" to "Number of Attendees"
    And I press "Add"
    And I set the field "newcolumns" to "Overbooking allowed"
    And I press "Add"
    And I set the field "newcolumns" to "Approval Type"
    And I press "Add"
    And I set the field "newcolumns" to "Event Status"
    And I press "Add"
    And I set the field "newcolumns" to "Booking Status"
    And I press "Add"
    And I set the field "newcolumns" to "Normal cost"
    And I press "Add"
    And I set the field "newcolumns" to "Discount cost"
    And I press "Add"
    And I set the field "newcolumns" to "Minimum bookings"
    And I press "Add"
    And I set the field "newcolumns" to "Event Learner"
    And I press "Add"
    And I set the field "newcolumns" to "Event Learner (linked to profile)"
    And I press "Add"
    And I set the field "newcolumns" to "Event Trainer"
    And I press "Add"
    And I set the field "newcolumns" to "Event Trainer (linked to profile)"
    And I press "Add"
    And I press "Save changes"

    And I click on "Filters" "link"
    And I set the field "newstandardfilter" to "Booking Status"
    And I press "Add"
    And I set the field "newstandardfilter" to "Event Status"
    And I press "Add"
    And I set the field "newstandardfilter" to "Event Learner"
    And I press "Add"
    And I set the field "newstandardfilter" to "Event Trainer"
    And I press "Add"
    And I press "Save changes"

    # 1: (1st activity of C1) Underbooked, upcoming, manager approval
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | course  | intro                           | approvaltype |
      | Test seminar name 1 | C1      | <p>Test seminar description</p> | 4            |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details | capacity | mincapacity | sendcapacityemail | cutoff | normalcost | discountcost |
      | Test seminar name 1 | event 1 | 2        | 1           | 1                 | 90000  | 1.11       | 1.00         |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               |
      | event 1      | 1 Jan next year 11am | 1 Jan next year 12pm |
    And I am on "Test seminar name 1" seminar homepage

  Scenario: Check canceled seminar sessions summary report
    Given I click on the seminar event action "Cancel event" in row "#1"
    And I press "Yes"
    And I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    Then I should see "N/A" in the "1.11" "table_row"

  Scenario: Check name column links to seminar in sessions summary report.

    Given I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    And I click on "Test seminar name 1" "link" in the "Course 1" "table_row"
    Then I should see "Test seminar name 1" in the page title

  Scenario: Check active seminar sessions summary report
    # Prepare 4 sessions in three activities:
    # 2: (2nd activity of C1) Two dates, self approved, overbooked, 1st started, 2nd upcoming
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | course  | intro                           |
      | Test seminar name 2 | C1      | <p>Test seminar description</p> |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details  | capacity | normalcost | discountcost |
      | Test seminar name 2 | event 2a | 1        | 2.22       | 2.10         |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish                     |
      | event 2a     | -1 day               | +30 minutes                |
      | event 2a     | 1 Jan +2 years       | 1 Jan +2 years +30 minutes |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student1 | event 2a     | booked |
      | student3 | event 2a     | booked |

    # 3: (2nd activity of C1) Bookings available, upcoming
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details  | capacity | normalcost | discountcost |
      | Test seminar name 2 | event 2b | 2        | 3.33       | 1.50         |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish                     |
      | event 2b     | 1 Jan next year 11am | 1 Jan next year 12pm       |

    # 4: (1st activity of C2) Fully booked, ended, no one
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | course  | intro                           |
      | Test seminar name 3 | C2      | <p>Test seminar description</p> |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details | capacity | normalcost |
      | Test seminar name 3 | event 3 | 1        | 4.44       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start   | finish |
      | event 3      | -2 days | -1 day |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student6 | event 3      | booked |
    And I am on "Test seminar name 3" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Sam4 Student4" "checkbox"
    And I click on "Sam5 Student5" "checkbox"
    And I press "Save changes"

    # 5: (1st activity of C2) N/A, ended, no one
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                | course  | intro                           |
      | Test seminar name 4 | C2      | <p>Test seminar description</p> |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface          | details | capacity | normalcost |
      | Test seminar name 4 | event 4 | 2        | 5.55       |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start   | finish |
      | event 4      | -2 days | -1 day |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student7 | event 4      | booked |

    And I click on "Reports" in the totara menu
    When I click on "Seminar Summary" "link"
    Then the "report_seminar_summary" table should contain the following:
      | Normal cost | Course Name | Overbooking allowed | Approval Type    | Event Status      | Booking Status | Discount cost | Event Learner | Event Learner |
      | 1.11        | Course 1    | No                  | Manager Approval | Upcoming          | Underbooked    | 1.00          |               |               |
      | 2.22        | Course 1    | No                  | No Approval      | Event in progress | Overbooked     | 2.10          |               |               |
      | 3.33        | Course 1    | No                  | No Approval      | Upcoming          | Booking open   | 1.50          |               |               |
      | 4.44        | Course 2    | No                  | No Approval      | Event over        | Booking full   |               | Sam4 Student4 | Sam5 Student5 |
      | 5.55        | Course 2    | No                  | No Approval      | Event over        | N/A            |               |               |               |

    # Check filters
    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Underbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Overbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Booking open"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "3.33"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Booking full"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I press "Clear"

    When I set the field "Booking Status field limiter" to "isn't equal to"
    And I set the field "Booking Status value" to "Booking open"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "3.33"
    And I should see "1.11"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"

    When I set the field "Event Status field limiter" to "is equal to"
    And I set the field "Event Status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "1.11"
    And I should see "2.22"
    And I should see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Event Status field limiter" to "is equal to"
    And I set the field "Event Status value" to "Event in progress"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Event Status field limiter" to "is equal to"
    And I set the field "Event Status value" to "Event over"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"

    When I set the field "Event Status field limiter" to "isn't equal to"
    And I set the field "Event Status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "1.11"
    And I should not see "3.33"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"

    When I set the field "Event Status field limiter" to "isn't equal to"
    And I set the field "Event Status value" to "Upcoming"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should not see "1.11"
    And I should not see "3.33"
    And I should see "2.22"
    And I should see "4.44"
    And I press "Clear"


    When I set the field "Event Status field limiter" to "isn't equal to"
    And I set the field "Event Status value" to "Upcoming"
    And I set the field "Booking Status field limiter" to "is equal to"
    And I set the field "Booking Status value" to "Overbooked"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2.22"
    And I should not see "1.11"
    And I should not see "3.33"
    And I should not see "4.44"
    And I press "Clear"

    When I set the field "Event Learner field limiter" to "contains"
    And I set the field "Event Learner value" to "Sam"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "4.44"
    And I should not see "1.11"
    And I should not see "2.22"
    And I should not see "3.33"
    And I press "Clear"

    # Disable teacher role, and ensure that column and filter disappeared
    Given "Event Trainer" "link" should exist in the ".reportbuilder-table" "css_element"
    And I click on "Home" in the totara menu
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Trainer" "checkbox_exact" in the "#admin-facetoface_session_roles" "css_element"
    And I press "Save changes"
    And I click on "Reports" in the totara menu
    And I follow "Seminar Summary"
    Then "Event Learner" "link" should exist in the ".reportbuilder-table" "css_element"
    And "Event Trainer" "link" should not exist in the ".reportbuilder-table" "css_element"
    And I press "Edit this report"
    And I follow "Columns"
    And I should see "Event Learner"
    And I should not see "Event Trainer"
    And I follow "Filters"
    And I should see "Event Learner"
    And I should not see "Event Trainer"

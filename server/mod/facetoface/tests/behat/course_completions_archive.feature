@javascript @mod @mod_facetoface @totara
Feature: Course archive completions for seminar sessions
  In order to archive completions in a seminar session
  As a teacher
  I need to set attendance status and archive course completions for attendees

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
      | student8  | Sam8      | Student8 | student8@example.com |
      | student9  | Sam9      | Student9 | student9@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |
      | student7 | C1     | student        |
      | student8 | C1     | student        |
      | student9 | C1     | student        |

  Scenario: Set attendance for individual users
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details |
      | Test seminar name | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start       | finish       |
      | event 1      | -1 week 7am | -1 week 10am |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails | status |
      | student1 | event 1      | booked |
      | student2 | event 1      | booked |
      | student3 | event 1      | booked |
      | student4 | event 1      | booked |

    And I log in as "admin"
    And I set the following administration settings values:
      | Enable restricted access | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I press "Save and display"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    When I click on "Take event attendance" "link" in the "Over" "table_row"
    Then "Select Sam1 Student1" "checkbox" should exist
    And I set the field "Sam1 Student1's attendance" to "Fully attended"
    And I set the field "Sam2 Student2's attendance" to "Partially attended"
    And I press "Save attendance"
    When I switch to "Attendees" tab
    Then I should see "Fully attended" in the "Sam1 Student1" "table_row"
    And I should see "Partially attended" in the "Sam2 Student2" "table_row"
    And I should see "Booked" in the "Sam3 Student3" "table_row"
    And I should see "Booked" in the "Sam4 Student4" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "This action will affect 1 learner(s)"
    When I press "Continue"
    Then I should see "1 users have had their progress and completion archived and reset in this course."
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "View all events" "link"
    When I click on "Take event attendance" "link" in the "Over" "table_row"
    Then I should see "The disabled attendees can not be updated because they hold archived course completion records"
    And "Select Sam1 Student1" "checkbox" should not exist
    And the "Sam1 Student1's attendance" "select" should be disabled

    When I click on "Attendees" "link"
    And I set the field "Attendee actions" to "Remove users"
    Then I should see "The disabled attendees can not be removed because they hold archived course completion records"
    And the "Sam1 Student1, student1@example.com" "option" should be disabled

    And I log out

  Scenario: Course completion archival of any time window
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name              | intro                           | course |
      | Test seminar name | <p>Test seminar description</p> | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface        | details          |
      | Test seminar name | waitlisted event |
      | Test seminar name | upcoming event   |
      | Test seminar name | ongoing event    |
      | Test seminar name | near past event  |
      | Test seminar name | far past event   |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails    | start               | finish              |
      | upcoming event  | 2 Feb next year 2am | 2 Feb next year 3am |
      | ongoing event   | 9 Dec last year 3am | 9 Jan next year 4am |
      | near past event | -2 days 4am         | -2 days 5am         |
      | far past event  | -365 days 5am       | -365 days 6am       |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails     | status     |
      | student1 | waitlisted event | waitlisted |
      | student2 | upcoming event   | booked     |
      | student3 | ongoing event    | booked     |
      | student4 | near past event  | booked     |
      | student5 | far past event   | booked     |
      | student6 | upcoming event   | booked     |
      | student7 | ongoing event    | booked     |
      | student8 | near past event  | booked     |
      | student9 | far past event   | booked     |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Completion tracking | Learners can manually mark the activity as completed |
    And I press "Save and display"
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Test seminar name | 1 |
    And I press "Save changes"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Manual completion of Test seminar name" "checkbox"
    And I log out

    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I click on "Manual completion of Test seminar name" "checkbox"
    And I log out

    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I click on "Manual completion of Test seminar name" "checkbox"
    And I log out

    And I log in as "student4"
    And I am on "Course 1" course homepage
    And I click on "Manual completion of Test seminar name" "checkbox"
    And I log out

    And I log in as "student5"
    And I am on "Course 1" course homepage
    And I click on "Manual completion of Test seminar name" "checkbox"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I press "Unlock completion and keep completion data"
    And I set the following fields to these values:
      | Event attendance          | Unrestricted                                      |
      | Completion tracking       | Show activity as complete when conditions are met |
      | Require grade             | Yes, any grade                                    |
    And I press "Save and display"

    And I click on "Take event attendance" "link" in the "Upcoming" "table_row"
    And I set the field "Sam6 Student6's attendance" to "Fully attended"
    And I press "Save attendance"
    And I follow "View all events"
    And I click on "Take event attendance" "link" in the "In progress" "table_row"
    And I set the field "Sam7 Student7's attendance" to "Partially attended"
    And I press "Save attendance"
    And I follow "View all events"
    And I click on "Take event attendance" "link" in the "4:00 AM - 5:00 AM" "table_row"
    And I set the field "Sam8 Student8's attendance" to "Unable to attend"
    And I press "Save attendance"
    And I follow "View all events"
    And I click on "Take event attendance" "link" in the "5:00 AM - 6:00 AM" "table_row"
    And I set the field "Sam9 Student9's attendance" to "Unable to attend"
    And I press "Save attendance"
    And I follow "View all events"

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should see "Completed" exactly "18" times

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I press "Unlock completion and keep completion data"
    And I set the following fields to these values:
      | id_completiondelayenabled | 1   |
      | completiondelay           | 100 |
    And I press "Save and display"

    When I navigate to "Reset completions" node in "Course administration"
    Then I should see "This action will affect 9 learner(s)"
    When I press "Continue"
    Then I should see "9 users have had their progress and completion archived and reset in this course."

    When I navigate to "Course completion" node in "Course administration > Reports"
    Then I should not see "Completed"
    And I press the "back" button in the browser
    And I follow "View all events"

    When I click on "Take event attendance" "link" in the "Upcoming" "table_row"
    Then "Select Sam2 Student2" "checkbox" should exist
    And the "Sam2 Student2's attendance" "select" should be enabled
    And "Select Sam6 Student6" "checkbox" should exist
    And the "Sam6 Student6's attendance" "select" should be enabled
    And I follow "View all events"

    When I click on "Take event attendance" "link" in the "In progress" "table_row"
    Then "Select Sam3 Student3" "checkbox" should exist
    And the "Sam3 Student3's attendance" "select" should be enabled
    And "Select Sam7 Student7" "checkbox" should exist
    And the "Sam7 Student7's attendance" "select" should be enabled
    And I follow "View all events"

    And I click on "Take event attendance" "link" in the "4:00 AM - 5:00 AM" "table_row"
    And I set the field "Sam8 Student8's attendance" to "Unable to attend"
    Then "Select Sam4 Student4" "checkbox" should exist
    And the "Sam4 Student4's attendance" "select" should be enabled
    And "Select Sam8 Student8" "checkbox" should exist
    And the "Sam8 Student8's attendance" "select" should be enabled
    And I follow "View all events"

    And I click on "Take event attendance" "link" in the "5:00 AM - 6:00 AM" "table_row"
    Then I should see "The disabled attendees can not be updated"
    And "Select Sam5 Student5" "checkbox" should not exist
    And the "Sam5 Student5's attendance" "select" should be disabled
    And "Select Sam9 Student9" "checkbox" should not exist
    And the "Sam9 Student9's attendance" "select" should be disabled
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "(On waitlist)" in the "Wait-listed" "table_row"
    And I log out

    When I log in as "student2"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "Upcoming" "table_row"
    And I log out

    When I log in as "student3"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "In progress" "table_row"
    And I log out

    When I log in as "student4"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I set the field "booking" to "Booked"
    Then I should see "4:00 AM - 5:00 AM" in the "Over" "table_row"
    And I log out

    When I log in as "student5"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I set the field "booking" to "Booked"
    Then I should see "5:00 AM - 6:00 AM" in the "Over" "table_row"
    And I log out

    When I log in as "student6"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "Upcoming" "table_row"
    And I log out

    When I log in as "student7"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "In progress" "table_row"
    And I log out

    When I log in as "student8"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I set the field "booking" to "Booked"
    Then I should see "4:00 AM - 5:00 AM" in the "Over" "table_row"
    And I log out

    When I log in as "student9"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I set the field "booking" to "Booked"
    Then I should see "5:00 AM - 6:00 AM" in the "Over" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    When I click on the seminar event action "Edit event" in row "5:00 AM - 6:00 AM"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[year]    | ## next year ## Y ## |
      | timefinish[year]   | ## next year ## Y ## |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should see "Upcoming" in the "5:00 AM - 6:00 AM" "table_row"
    And I log out

    When I log in as "student5"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "5:00 AM - 6:00 AM" "table_row"
    When I click on "Go to event" "link" in the "5:00 AM - 6:00 AM" "table_row"
    Then I should see "Booked"
    But "Cancel" "link" should not exist
    And I log out

    When I log in as "student9"
    And I am on "Course 1" course homepage
    Then I should see "(Booked)" in the "5:00 AM - 6:00 AM" "table_row"
    When I click on "Go to event" "link" in the "5:00 AM - 6:00 AM" "table_row"
    Then I should see "Booked"
    But "Cancel" "link" should not exist
    And I log out

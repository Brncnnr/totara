@core @core_course
Feature: Managers can create courses
  In order to group users and contents
  As a manager
  I need to create courses and set default values on them

  @javascript
  Scenario: Courses are created with the default announcements forum
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And I log in as "admin"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | C1 |
    And I enrol "Teacher 1" user as "Teacher"
    And I enrol "Student 1" user as "Student"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Announcements"
    And "Add a new topic" "button" should exist
    And "Subscription mode > Forced subscription" "link" should not exist in current page administration
    And "Subscription mode > Forced subscription" "text" should exist in current page administration
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Announcements"
    And "Add a new topic" "button" should not exist
    And "Forced subscription" "text" should exist in current page administration

  @javascript
  Scenario: Create a course from the management interface and return to it
    Given the following "courses" exist:
      | fullname | shortname | idnumber | startdate | enddate   |
      | Course 1 | Course 1  | C1       | 957139200 | 960163200 |
    And I log in as "admin"
    And I go to the courses management page
    And I should see the "Categories" management page
    And I click on category "Miscellaneous" in the management interface
    And I should see the "Course categories and courses" management page
    And I click on "Create new course" "link" in the "#course-listing" "css_element"
    When I set the following fields to these values:
      | Course full name | Course 2 |
      | Course short name | Course 2 |
      | Course summary | Course 2 summary |
      | id_startdate_day | 24 |
      | id_startdate_month | October |
      | id_startdate_year | 2015 |
      | id_enddate_day | 24 |
      | id_enddate_month | October |
      | id_enddate_year | 2016 |
    And I press "Save and return"
    Then I should see the "Course categories and courses" management page
    And I click on "Sort courses" "link"
    And I click on "Sort by Course time created ascending" "link" in the ".course-listing-actions" "css_element"
    And I should see course listing "Course 1" before "Course 2"
    And I click on "Course 2" "link" in the "region-main" "region"
    And I click on "Edit" "link" in the ".course-detail" "css_element"
    And the following fields match these values:
      | Course full name | Course 2 |
      | Course short name | Course 2 |
      | Course summary | Course 2 summary |
      | id_startdate_day | 24 |
      | id_startdate_month | October |
      | id_startdate_year | 2015 |
      | id_enddate_day | 24 |
      | id_enddate_month | October |
      | id_enddate_year | 2016 |

  @javascript
  Scenario: Course shortname can be 255 characters
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname       | shortname            | source  |
      | Courses Report | report_course_report | courses |
    When I log in as "admin"
    And I create a course with:
      | Course full name | Course 1 |
      | Course short name | This is a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long shortname |
    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I follow "Courses Report"
    And I switch to "Columns" tab
    And I add the "Course Shortname" column to the report
    And I follow "View This Report"
    Then I should see "This is a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long shortname"
    And I should see "Course 1"

    When I follow "Course 1"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Course short name | This is still a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long shortname |
    And I press "Save and display"
    And I follow "Reports"
    And I follow "Courses Report"
    Then I should see "This is still a very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very very long shortname"
    And I should see "Course 1"

  # Courses 1 & 3 created by trainer, courses 2 & 4 created by admin
  @javascript
  Scenario: Course default audience visibility setting is set
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | trainer1 | Trainer   | One      | trainer.one@example.com |
    And the following "role assigns" exist:
      | user     | role          | contextlevel | reference |
      | trainer1 | coursecreator | System       |           |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I navigate to "Course default settings" node in "Site administration >  Courses"
    And I set the field "Audience-based visibility" to "No users"
    And I click on "Save changes" "button"
    And I log out
    And I log in as "trainer1"
    And I create a course with:
      | Course full name  | Course 1 |
      | Course short name | course1  |
    And I log out
    And I log in as "admin"
    And I create a course with:
      | Course full name  | Course 2 |
      | Course short name | course2  |
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Course full name" matches value "Course 2"
    And the field "audiencevisible" matches value "No users"
    And I am on "Course 1" course homepage
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Course full name" matches value "Course 1"
    And the field "audiencevisible" matches value "No users"
    And I navigate to "Course default settings" node in "Site administration >  Courses"
    And I set the field "Audience-based visibility" to "Enrolled users only"
    And I click on "Save changes" "button"
    And I log out
    And I log in as "trainer1"
    And I create a course with:
      | Course full name  | Course 3 |
      | Course short name | course3  |
    And I log out
    And I log in as "admin"
    And I create a course with:
      | Course full name  | Course 4 |
      | Course short name | course4  |
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Course full name" matches value "Course 4"
    And the field "audiencevisible" matches value "Enrolled users only"
    And I am on "Course 3" course homepage
    When I navigate to "Edit settings" node in "Course administration"
    Then the field "Course full name" matches value "Course 3"
    And the field "audiencevisible" matches value "Enrolled users only"

  Scenario: Courses can be created with javascript disabled
    Given I log in as "admin"
    And I go to the courses management page
    And I should see the "Categories" management page
    And I click on category "Miscellaneous" in the management interface
    And I should see the "Course categories and courses" management page
    And I click on "Create new course" "link" in the "#course-listing" "css_element"
    When I set the following fields to these values:
      | Course full name  | Course 1 |
      | Course short name | C1       |
    And I press "Save and return"
    Then I should see the "Course categories and courses" management page
    And I should see "Course 1"

  @javascript
  Scenario: On the Create Course page, for Format = the Single Activity Format, the Type of Activity box is filled in for
  a user with Course Creator role assigned
    Given the following "categories" exist:
      | name     | category | idnumber |
      | Science2 | 0        | SCI2     |
    And the following "users" exist:
      | username                     | firstname                      | lastname                       | email             |
      | coursecreatorcategoryuser    | coursecreatorcategoryuser      | coursecreatorcategoryuser      | a1@example.com    |
      | coursecreatorsystemuser      | coursecreatorsystemuser        | coursecreatorsystemuser        | a2@example.com    |
    And the following "system role assigns" exist:
      | user                    | role                |
      | coursecreatorsystemuser | coursecreator       |
    # The CourseCreator role is not normally enabled at Category context initially, but the behat data generator used below sets it automatically.
    And the following "role assigns" exist:
      | user                      | role                | contextlevel | reference |
      | coursecreatorcategoryuser | coursecreator       | Category     | SCI2      |
    # As the 'course creator user', check that the Create Course page 'Type of Activity' box populates with items.
    And I log in as "coursecreatorsystemuser"
    And I am on a totara site
    And I go to the courses management page
    And I click on "Science2" category in the management category listing
    And I follow "Create new course"
    Then I should see "Add a new course"
    And I follow "Course format"
    And I set the following fields to these values:
      | id_format | Single activity format |
    Then "//option[@value='forum']" "xpath_element" should exist in the "//select[@id='id_activitytype']" "xpath_element"
    And I log out
    # As the 'course creator user', check that the Create Course page 'Type of Activity' box populates with items.
    And I log in as "coursecreatorcategoryuser"
    And I am on a totara site
    And I go to the courses management page
    And I click on "Science2" category in the management category listing
    And I follow "Create new course"
    Then I should see "Add a new course"
    And I follow "Course format"
    And I set the following fields to these values:
      | id_format | Single activity format |
    Then "//option[@value='forum']" "xpath_element" should exist in the "//select[@id='id_activitytype']" "xpath_element"

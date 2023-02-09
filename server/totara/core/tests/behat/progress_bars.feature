@javascript @enrol @totara @totara_core @totara_dashboard @totara_courseprogressbar @block @block_last_course_accessed @block_totara_featured_links @block_totara_recent_learning
Feature: Test progress bars
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | P1        |
    And I add a courseset with courses "C1" to "P1":
      | Set name              | set1        |
      | Learner must complete | All courses |

    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Assignment 1 |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Assignment 2 |
    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ALL conditions are met"
    And I set the field "Assignment 1" to "1"
    And I set the field "Assignment 2" to "1"
    And I press "Save changes"

    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Not completed" "link" in the "Student One" "table_row"
    And I set the field "rplinput" to "done"
    And I press key "13" in the field "rplinput"

    When I am on "Program 1" program homepage
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Student Two" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    Then I should see "'Student Two' has been added to the program"

    When I am on "Dashboard" page
    And I press "Manage dashboards"
    And I follow "My Learning"
    And I press "Blocks editing on"
    And I add the "Featured Links" block
    And I follow "Add Tile"
    And I set the field "Tile type" to "Course"
    And I press "Select course"
    And I click on "Miscellaneous" "link" in the "Select course" "totaradialogue"
    And I click on "Course 1" "link" in the "Select course" "totaradialogue"
    And I click on "OK" "button" in the "Select course" "totaradialogue"
    And I set the field "Show progress" to "1"
    And I press "Save changes"
    And I follow "Add Tile"
    And I set the field "Tile type" to "Program"
    And I press "Select program"
    And I click on "Miscellaneous" "link" in the "Select program" "totaradialogue"
    And I click on "Program 1" "link" in the "Select program" "totaradialogue"
    And I click on "OK" "button" in the "Select program" "totaradialogue"
    And I set the field "Show progress" to "1"
    And I press "Save changes"
    And I add the "Recent Learning" block
    And I log out

    # Firstly enrol student2 via program enrolment
    When I log in as "student2"
    And I am on "Program 1" program homepage
    And I follow "Course 1"
    Then I should see "You have been enrolled in course Course 1 via required learning program Program 1"
    And I log out

    # Secondly enrol via manual enrolment
    When I log in as "admin"
    And  I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Enrol users" "button"
    And I click on "Enrol" "button" in the ".user-enroller-panel .user:first-child" "css_element"
    And I click on "Finish enrolling users" "button"
    Then I should see "Manual enrolments" in the "Student Two" "table_row"
    And I should see "Program" in the "Student Two" "table_row"
    # Last but not least, complete student2's assignment1 via RPL
    And I navigate to "Course completion" node in "Course administration > Reports"
    And I click on "Not completed" "link" in the "Student Two" "table_row"
    And I set the field "rplinput" to "done"
    And I press key "13" in the field "rplinput"
    And I log out

  Scenario: Student can see progress bars
    When I log in as "student1"
    And  I am on "Course 1" course homepage
    And  I am on "Dashboard" page
    Then I should see "50%" in the "Last Course Accessed" "block"
    And  I should see "50%" in the ".block_current_learning-course" "css_element"
    And  I should see "50%" in the ".block-totara-featured-links-course" "css_element"
    But  I should not see "50%" in the ".block-totara-featured-links-program" "css_element"
    And  I should see "50%" in the "Recent Learning" "block"
    And  I log out

    When I log in as "student2"
    And  I am on "Course 1" course homepage
    And  I am on "Dashboard" page
    Then I should see "50%" in the "Last Course Accessed" "block"
    And  I should see "50%" in the ".block_current_learning-program" "css_element"
    And  I should see "50%" in the ".block-totara-featured-links-course" "css_element"
    And  I should see "50%" in the ".block-totara-featured-links-program" "css_element"
    And  I should see "50%" in the "Recent Learning" "block"
    And  I log out

    And  I log in as "admin"
    And  I am on "Course 1" course homepage
    And  I navigate to "Enrolled users" node in "Course administration > Users"
    And  I click on "Edit enrolment" "link" in the "Student One" "table_row"
    And  I set the field "Status" to "Suspended"
    And  I press "Save changes"
    And  I click on "Edit enrolment" "link" in the "Student Two" "table_row"
    And  I set the field "Status" to "Suspended"
    And  I press "Save changes"
    When I click on ".enrolment:nth-child(1) > .unenrollink" "css_element" in the "Student Two" "table_row"
    And  I press "Continue"
    Then I should not see "Program enrolled" in the "Student Two" "table_row"
    And  I log out

    When I log in as "student1"
    Then I should not see "50%" in the "Last Course Accessed" "block"
    And  I should not see "Course 1" in the "Current Learning" "block"
    But  I should see "You do not have any current learning" in the "Current Learning" "block"
    And  I should not see "50%" in the "Featured Links" "block"
    And  I should not see "50%" in the "Recent Learning" "block"
    But  I should see "Course 1" in the "Recent Learning" "block"
    And  I log out

    When I log in as "student2"
    Then I should not see "50%" in the "Last Course Accessed" "block"
    And  I should see "50%" in the ".block_current_learning-program" "css_element"
    And  I should not see "50%" in the ".block-totara-featured-links-course" "css_element"
    But  I should see "50%" in the ".block-totara-featured-links-program" "css_element"
    And  I should not see "50%" in the "Recent Learning" "block"
    But  I should see "Course 1" in the "Recent Learning" "block"
    And  I log out

    And  I log in as "admin"
    And  I am on "Course 1" course homepage
    And  I navigate to "Enrolled users" node in "Course administration > Users"
    And  I click on "Unenrol" "link" in the "Student One" "table_row"
    And  I press "Continue"
    And  I click on "Unenrol" "link" in the "Student Two" "table_row"
    And  I press "Continue"
    And  I log out

    When I log in as "student1"
    Then I should not see "Last Course Accessed"
    And  I should not see "50%"
    But  I should see "You are not enrolled in any courses" in the "Recent Learning" "block"
    And  I log out

    When I log in as "student2"
    Then I should not see "Last Course Accessed"
    And  I should see "50%" in the ".block_current_learning-program" "css_element"
    But  I should see "You are not enrolled in any courses" in the "Recent Learning" "block"
    And  I should see "50%" in the ".block_current_learning-program" "css_element"
    And  I log out

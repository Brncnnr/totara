@enrol @javascript @totara @enrol_totara_facetoface @mod @mod_assign @mod_facetoface @core_grades
Feature: Guest users enrol themselves in courses where seminar direct enrolment is allowed
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | One       | Uno      | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name                  | course |
      | Wananga Whakamatautau | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface            | details |
      | Wananga Whakamatautau | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                            | finish                                   |
      | event 1      | first day of September next year | first day of September next year +1 hour |
    And the following "activities" exist:
      | activity | name            | course | idnumber | submissiondrafts |
      | assign   | Kawenga Taumaha | C1     | assign   | 0                |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"

    And I am on "Wananga Whakamatautau" seminar homepage
    And I follow "Kawenga Taumaha"
    And I navigate to "Edit settings" node in "Assignment administration"
    And I set the field "File submissions" to "1"
    And I press "Save and display"

    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Whakauru Tika |

    And I click on "Edit" "link" in the "Guest access" "table_row"
    And I set the following fields to these values:
      | Allow guest access | Yes |
    And I press "Save changes"
    And I log out

  Scenario: Guest seminar direct enrolment from course page without auto sign-up
    When I log in as "user1"
    And I am on "Course 1" course homepage
    When I click on "Go to event" "link" in the "1 September" "table_row"
    And I press "Sign-up"
    Then I should see "Your" in the "#user-notifications" "css_element"
    When I navigate to "Grades" node in "Course administration"

    Then I should see "-" in the "Kawenga Taumaha" "table_row"
    And  I should see "-" in the "Wananga Whakamatautau" "table_row"
    And I click on "Kawenga Taumaha" "link"
    And I press "Add submission"
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/submission.pdf" file to "File submissions" filemanager
    And I press "Save changes"
    And I should see "Submitted"
    And I should see "submission.pdf"
    And I should see "Not graded"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "0.00 %" in the "Kawenga Taumaha" "table_row"
    And  I should see "0.00 %" in the "Wananga Whakamatautau" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Wananga Whakamatautau" seminar homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Whakauru Tika" in the "One Uno" "table_row"

  Scenario: Guest seminar direct enrolment from course page with auto sign-up
    # Turn on auto sign-up as admin
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Whakauru Tika" node in "Course administration > Users > Enrolment methods"
    And I set the following fields to these values:
      | Automatically sign users up to seminar events | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I am on "Wananga Whakamatautau" seminar homepage
    Then I should see "Wananga Whakamatautau"
    When I click on "Go to event" "link" in the "1 September" "table_row"
    And I press "Sign-up"
    Then I should see "Your" in the "#user-notifications" "css_element"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "-" in the "Kawenga Taumaha" "table_row"
    And  I should see "-" in the "Wananga Whakamatautau" "table_row"
    And I click on "Kawenga Taumaha" "link"
    And I press "Add submission"
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/submission.pdf" file to "File submissions" filemanager
    And I press "Save changes"
    And I should see "Submitted"
    And I should see "submission.pdf"
    And I should see "Not graded"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "0.00 %" in the "Kawenga Taumaha" "table_row"
    And  I should see "0.00 %" in the "Wananga Whakamatautau" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Wananga Whakamatautau" seminar homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Whakauru Tika" in the "One Uno" "table_row"

  Scenario: Guest seminar direct enrolment from enrolment options page without auto sign-up
    When I log in as "user1"
    And I am on "Course 1" course homepage
    Then I should see "Wananga Whakamatautau"
    And I navigate to "Enrolment options" node in "Course administration"
    When I click on "Go to event" "link" in the "1 September" "table_row"
    And I press "Sign-up"
    Then I should see "Your" in the "#user-notifications" "css_element"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "-" in the "Kawenga Taumaha" "table_row"
    And  I should see "-" in the "Wananga Whakamatautau" "table_row"
    And I click on "Kawenga Taumaha" "link"
    And I press "Add submission"
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/submission.pdf" file to "File submissions" filemanager
    And I press "Save changes"
    And I should see "Submitted"
    And I should see "submission.pdf"
    And I should see "Not graded"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "0.00 %" in the "Kawenga Taumaha" "table_row"
    And  I should see "0.00 %" in the "Wananga Whakamatautau" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Wananga Whakamatautau" seminar homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Whakauru Tika" in the "One Uno" "table_row"

  Scenario: Guest seminar direct enrolment from enrolment options page with auto sign-up
    # Turn on auto sign-up as admin
    And I log in as "admin"
    And I am on "Wananga Whakamatautau" seminar homepage
    And I navigate to "Whakauru Tika" node in "Course administration > Users > Enrolment methods"
    And I set the following fields to these values:
      | Automatically sign users up to seminar events | 1 |
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    Then I should see "Wananga Whakamatautau"
    When I navigate to "Enrolment options" node in "Course administration"
    And I press "Sign-up"
    Then I should see "Your" in the "#user-notifications" "css_element"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "-" in the "Kawenga Taumaha" "table_row"
    And  I should see "-" in the "Wananga Whakamatautau" "table_row"
    And I click on "Kawenga Taumaha" "link"
    And I press "Add submission"
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/submission.pdf" file to "File submissions" filemanager
    And I press "Save changes"
    And I should see "Submitted"
    And I should see "submission.pdf"
    And I should see "Not graded"
    When I navigate to "Grades" node in "Course administration"
    Then I should see "0.00 %" in the "Kawenga Taumaha" "table_row"
    And  I should see "0.00 %" in the "Wananga Whakamatautau" "table_row"
    And I log out

    And I log in as "admin"
    And I am on "Wananga Whakamatautau" seminar homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Whakauru Tika" in the "One Uno" "table_row"

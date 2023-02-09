@totara @totara_customfield @totara_completion_upload @totara_evidence @javascript @_file_upload
Feature: Verify the case insensitive shortnames for course completion imports works as expected
  As an admin
  I import course completions with case mismatches
  In order to test the case insensitive shortname setting

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username  | firstname  | lastname  | email                |
      | learner01 | Bob1       | Learner1  | learner01@example.com |
      | learner02 | Bob2       | Learner2  | learner02@example.com |
      | learner03 | Bob3       | Learner3  | learner03@example.com |
      | learner04 | Bob4       | Learner4  | learner04@example.com |
      | learner05 | Bob5       | Learner5  | learner05@example.com |
      | learner06 | Bob6       | Learner6  | learner06@example.com |
      | learner07 | Bob7       | Learner7  | learner07@example.com |
      | learner08 | Bob8       | Learner8  | learner08@example.com |

    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | CP101     | c1       |
      | Course 2 | CP102     | c2       |

    And the following "course enrolments" exist:
      | user      | course    | role    |
      | learner01 | CP101     | student |
      | learner02 | CP101     | student |
      | learner03 | CP101     | student |
      | learner04 | CP101     | student |
      | learner05 | CP101     | student |
      | learner06 | CP101     | student |
      | learner07 | CP101     | student |
      | learner08 | CP101     | student |
      | learner01 | CP102     | student |
      | learner02 | CP102     | student |
      | learner03 | CP102     | student |
      | learner04 | CP102     | student |
      | learner05 | CP102     | student |
      | learner06 | CP102     | student |
      | learner07 | CP102     | student |
      | learner08 | CP102     | student |

  Scenario: Basic course completion import case insensitive is turned on
    When I log in as "admin"
    And I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_mismatch_fields_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I set the field "Upload course Case insensitive shortnames" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "12 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    And "1" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "6" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "7" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "9" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob1 Learner1: All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "100%"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "100%"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4: All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "100%"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP102"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob8 Learner8: All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP101"

  Scenario: Basic course completion import case insensitive is turned off
    When I log in as "admin"
    And I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_mismatch_fields_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "12 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    And "1" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "6" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "7" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "9" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"

    And "1" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "3" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "4" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "5" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "6" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "8" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "9" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "10" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "11" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob1 Learner1: All Courses"
    And I should not see "Other Evidence" in the ".tabtree" "css_element"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4: All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP102"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob8 Learner8: All Courses"
    And I should not see "Other Evidence" in the ".tabtree" "css_element"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

  Scenario: Basic course completion import case insensitive is turned on and has only one element
    When I log in as "admin"
    And I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_mismatch_fields_2.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "0"
    And I set the field "Upload course Case insensitive shortnames" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    And "1" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "1" row "Errors" column of "completionimport_course" table should not contain "No matching course"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob1 Learner1"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "100%"

@totara @totara_completion_upload @totara_courseprogressbar @javascript @_file_upload
Feature: Verify course completion data can be successfully uploaded.

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |

    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | C1        | 1        |

  Scenario: Verify an empty course completion upload fails.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    And I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    And I follow "Course import report"
    Then I should see "1 record shown"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank user name"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank completion date"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank grade"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank course shortname and course ID number"


  Scenario: Verify an course completion with no username fails.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1a.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    And I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    And I follow "Course import report"
    Then I should see "1 record shown"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank user name"

  Scenario: Verify a successful course completion with no courseshortname.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1b.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

  Scenario: Verify a successful course completion with no courseidnumber.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1c.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

  Scenario: Verify an course completion with no completiondate fails.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1d.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    And I follow "Course import report"
    And I should see "1 record shown"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank completion date"

  Scenario: Verify an course completion with no grade fails.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1e.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    When I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    And I follow "Course import report"
    Then I should see "1 record shown"
    And "1" row "Errors" column of "completionimport_course" table should contain "Blank grade"

  Scenario: Verify a successful course completion upload.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_2.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

    When I follow "Other Evidence"
    And I follow "Completed course : thisisevidence"
    Then I should see "Completed course : thisisevidence"
    And I should see the evidence item fields contain:
      | Course short name | thisisevidence |
      | Course ID number  | notacourse     |
      | Completion date   | 1 January 2015 |
      | Grade             | 100            |
      | Import ID         | 2              |

    # As admin I am able to edit the evidence.
    And "Edit this item" "link" should exist
    When I click on "Edit this item" "link"
    Then I should see "Edit Completed course : thisisevidence"
    And I log out

    # As the learner I should not be able to edit the evidence.
    When I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    And I follow "Other Evidence"
    And I click on "Completed course : thisisevidence" "link" in the "tbody" "css_element"

  Scenario: Verify a successful course completion upload specifying that no evidence should be created.
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_2.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "0"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"
    And I should not see "Other Evidence" in the ".tabtree" "css_element"

  Scenario: Course completions can be successfully uploaded with a file that uses CR for line endings
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_CR_line_endings.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

    When I follow "Other Evidence"
    And I follow "Completed course : thisisevidence"
    And I should see the evidence item fields contain:
      | Course short name | thisisevidence |
      | Course ID number  | notacourse     |
      | Completion date   | 1 January 2015 |
      | Grade             | 100            |
      | Import ID         | 2              |

  Scenario: Course completions can not be uploaded via a directory if config setting completionimportdir is not set
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I click on "Alternatively upload CSV files via a directory on the server" "link"
    Then I should see "Additional configuration settings are required to specify a file location on the server. Please contact your system administrator."
    When I click on "Alternatively upload CSV files via a form" "link"
    Then I should see "CSV file to upload"

  Scenario: Verify a course completion import csv with incorrect columns shows an error
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_badcolumns.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "There were errors while importing the courses"
    And I should see "Unknown column 'badcolumn'"
    And I should see "Missing required column 'courseidnumber'"
    And I should see "No records were imported"

  Scenario: Verify a successful course completion with User participation in course is suspended
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I enrol "Bob1 Learner1" user as "Learner"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I click on "Edit enrolment" "link"
    And I set the field "Status" to "Suspended"
    And I press "Save changes"

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_1b.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

  Scenario: Verify long field values are handled in the course completion upload
    Given I log in as "admin"
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_long_fields.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload course Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then I should see "2 records shown"
    And "1" row "Errors" column of "completionimport_course" table should contain "Field 'username' is too long. The maximum length is 100"
    And "1" row "Errors" column of "completionimport_course" table should contain "Field 'courseshortname' is too long. The maximum length is 255"
    And "1" row "Errors" column of "completionimport_course" table should contain "Field 'courseidnumber' is too long. The maximum length is 100"
    And "1" row "Errors" column of "completionimport_course" table should contain "Field 'completiondate' is too long. The maximum length is 10"
    And "1" row "Errors" column of "completionimport_course" table should contain "Field 'grade' is too long. The maximum length is 10"
    And "1" row "Username to import" column of "completionimport_course" table should contain "101charsintheusernamefieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Course Shortname" column of "completionimport_course" table should contain "256charsinthecourseshortnamefieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Course ID Number" column of "completionimport_course" table should contain "101charsinthecourseidnumberfieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Completion date" column of "completionimport_course" table should contain "11chars..."
    And "1" row "Grade" column of "completionimport_course" table should contain "11chars..."
    And "2" row "Errors" column of "completionimport_course" table should contain ""
    And "2" row "Username to import" column of "completionimport_course" table should contain "learner1"
    And "2" row "Course Shortname" column of "completionimport_course" table should contain "test course 1"
    And "2" row "Course ID Number" column of "completionimport_course" table should contain "testcourse1"
    And "2" row "Completion date" column of "completionimport_course" table should contain "2015-01-01"
    And "2" row "Grade" column of "completionimport_course" table should contain "77"

  Scenario: Disabling completion import feature in admin settings
    When I log in as "admin"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    Then I should see "Upload completion records"
    When I disable the "completionimport" advanced feature
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    Then I should not see "Upload completion records"

  Scenario: Verify course completion upload with custom fields
    Given the following "types" exist in "totara_evidence" plugin:
      | name          | idnumber  |fields | location |
      | System Type 1 | system1   |0      | 1        |
      | Bank Type 1   | bank1     |0      | 0        |
    And the following "type fields" exist in "totara_evidence" plugin:
      | evidence_type | datatype  | fullname          | shortname       |
      | System Type 1 | text      | Custom text1      | Customtext1     |
      | System Type 1 | checkbox  | Custom cb11       | Customcb11      |
      | Bank Type 1   | text      | Custom bank text1 | Custombanktext1 |

    When I log in as "admin"
    And I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    Then the "Evidence type" "field" should be disabled

    When I set the field "Upload course Create evidence" to "1"
    Then the "Evidence type" "field" should be enabled
    And "System Type 1" "option" should exist in the "Evidence type" "select"
    And "Bank Type 1" "option" should exist in the "Evidence type" "select"

    When I set the field "Evidence type" to "System Type 1"
    Then I should see "The additional columns below can also be included in the CSV file:"
    And I should see "customfield_Customtext1,customfield_Customcb11"

    # Unknown customfield
    When I upload "totara/completionimport/tests/behat/fixtures/course_completion_cf_bank1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "There were errors while importing the courses"
    And I should see "No records were imported"
    And I should see "Unknown column 'customfield_Custombanktext1'"

    # Previous values restored
    When I follow "Upload course records"
    Then the "Evidence type" "field" should be enabled
    And the following fields match these values:
      | Create evidence   | 1             |
      | Evidence type     | System Type 1 |

    # No customfields allowed when not creating evidence
    When I set the field "Upload course Create evidence" to "0"
    When I upload "totara/completionimport/tests/behat/fixtures/course_completion_cf_system1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "There were errors while importing the courses"
    And I should see "No records were imported"
    And I should see "Unknown column 'customfield_Customtext1'"
    And I should see "Unknown column 'customfield_Customcb11'"

    # Type contains customfields, csv file doesn't
    When I follow "Upload course records"
    Then the "Evidence type" "field" should be disabled
    When I set the field "Upload course Create evidence" to "1"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_2.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "100%" in the "Course 1" "table_row"

    When I follow "Other Evidence"
    And I follow "Completed course : thisisevidence"
    Then I should see "Completed course : thisisevidence"
    And I should see the evidence item fields contain:
      | Course short name | thisisevidence |
      | Course ID number  | notacourse     |
      | Completion date   | 1 January 2015 |
      | Grade             | 100            |
      | Import ID         | 2              |
    And I should not see "Custom text1"

    # Type and csv file contain customfields
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_cf_system1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"
    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I follow "Other Evidence"
    Then I should see "Completed course : thisisevidence"
    And I should see "Completed course : this is evidence for system1"

    When I follow "Completed course : this is evidence for system1"
    And I wait for pending js
    Then I should see "Completed course : this is evidence for system1"
    And I should see the evidence item fields contain:
      | Course short name | this is evidence for system1 |
      | Course ID number  | system1 course               |
      | Completion date   | 1 January 2020               |
      | Grade             | 100                          |
      | Import ID         | 3                            |
      | Custom text1      | Some custom evidence text    |
      | Custom cb11       | Yes                          |

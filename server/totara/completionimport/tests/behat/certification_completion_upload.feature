@totara @perform @totara_completion_upload @totara_evidence @javascript @_file_upload
Feature: Verify certification completion data can be successfully uploaded.

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |

    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | idnumber |
      | Certification 1 | Cert1     | 1        |

  Scenario: Verify a successful simple certification completion upload.
    Given I log in as "admin"
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload certification Create evidence" to "1"
    And I set the field "Upload certification Import action" to "Certify uncertified users"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I am on homepage
    Then I should see "Certification completion import successfully completed"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I switch to "Certifications" tab
    Then I should see "Expired" in the "Certification 1" "table_row"

    When I follow "Other Evidence"
    And I follow "Completed certification : thisisevidence"
    Then I should see the evidence item fields contain:
      | Certification short name | thisisevidence    |
      | Certification ID number  | notacertification |
      | Completion date          | 1 January 2015    |
      | Due date                 | 1 January 2016    |
      | Import ID                | 2                 |

  Scenario: Verify a successful certification completion upload specifying that no evidence should be created.
    Given I log in as "admin"
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload certification Create evidence" to "0"
    And I set the field "Upload certification Import action" to "Certify uncertified users"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    And I switch to "Certifications" tab
    Then I should see "Expired" in the "Certification 1" "table_row"
    And I should not see "Other Evidence" in the ".tabtree" "css_element"
    When I click on "Record of Learning" in the totara menu
    Then I should see "There are no records"

  Scenario: Verify a certification completion import csv with incorrect columns shows an error
    Given I log in as "admin"
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_badcolumns.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload certification Import action" to "Certify uncertified users"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "There were errors while importing the certifications"
    And I should see "Unknown column 'badcolumn'"
    And I should see "Missing required column 'duedate'"
    And I should see "No records were imported"

  Scenario: Verify long field values are handled in the certification completion upload
    Given I log in as "admin"
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_long_fields.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I am on homepage
    Then I should see "Certification completion import completed with errors"
    And I click on "View all alerts" "link"
    And I should see "was processed but contains 2 error(s)"

    When I follow "Certification import report"
    Then I should see "2 records shown"
    And "1" row "Errors" column of "completionimport_certification" table should contain "Field 'username' is too long. The maximum length is 100"
    And "1" row "Errors" column of "completionimport_certification" table should contain "Field 'certificationshortname' is too long. The maximum length is 255"
    And "1" row "Errors" column of "completionimport_certification" table should contain "Field 'certificationidnumber' is too long. The maximum length is 100"
    And "1" row "Errors" column of "completionimport_certification" table should contain "Field 'completiondate' is too long. The maximum length is 10"
    And "1" row "Errors" column of "completionimport_certification" table should contain "Field 'duedate' is too long. The maximum length is 10"
    And "1" row "Username to import" column of "completionimport_certification" table should contain "101charsintheusernamefieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Certification Shortname" column of "completionimport_certification" table should contain "256charsinthecertificationshortnamefieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Certification ID Number" column of "completionimport_certification" table should contain "101charsinthecertificationidnumberfieldsshouldfailxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx..."
    And "1" row "Completion date" column of "completionimport_certification" table should contain "11chars..."
    And "1" row "Due date" column of "completionimport_certification" table should contain "11chars..."
    And "2" row "Errors" column of "completionimport_certification" table should contain ""
    And "2" row "Username to import" column of "completionimport_certification" table should contain "learner1"
    And "2" row "Certification Shortname" column of "completionimport_certification" table should contain "Certification 1"
    And "2" row "Certification ID Number" column of "completionimport_certification" table should contain "Cert1"
    And "2" row "Completion date" column of "completionimport_certification" table should contain "2020-01-01"
    And "2" row "Due date" column of "completionimport_certification" table should contain "2020-07-01"

  Scenario: Verify certification completion upload with custom fields
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
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    Then the "Evidence type" "field" should be disabled

    And I set the field "Upload certification Create evidence" to "1"
    Then the "Evidence type" "field" should be enabled
    And "System Type 1" "option" should exist in the "Evidence type" "select"
    And "Bank Type 1" "option" should exist in the "Evidence type" "select"

    When I set the field "Evidence type" to "System Type 1"
    Then I should see "The additional columns below can also be included in the CSV file:"
    And I should see "customfield_Customtext1"
    And I should see "customfield_Customcb11"

    # Unknown customfield
    When I upload "totara/completionimport/tests/behat/fixtures/certification_completion_cf_bank1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "There were errors while importing the certifications"
    And I should see "No records were imported"
    And I should see "Unknown column 'customfield_Custombanktext1'"

    # Previous values restored
    When I follow "Upload certification records"
    Then the "Evidence type" "field" should be enabled
    And the following fields match these values:
      | Create evidence   | 1             |
      | Evidence type     | System Type 1 |

    # No customfields allowed when not creating evidence
    When I set the field "Upload certification Create evidence" to "0"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_cf_system1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "There were errors while importing the certifications"
    And I should see "No records were imported"
    And I should see "Unknown column 'customfield_Customtext1'"
    And I should see "Unknown column 'customfield_Customcb11'"

    # Type contains customfields, csv file doesn't
    When I follow "Upload certification records"
    Then the "Evidence type" "field" should be disabled
    When I set the field "Upload certification Create evidence" to "1"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I am on homepage
    Then I should see "Certification completion import successfully completed"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Completed certification : thisisevidence"
    When I follow "Completed certification : thisisevidence"
    Then I should see the evidence item fields contain:
      | Certification short name | thisisevidence    |
      | Certification ID number  | notacertification |
      | Completion date          | 1 January 2015    |
      | Due date                 | 1 January 2016    |
      | Import ID                | 2                 |
    And I should not see "Custom text1"

    # Type and csv file contain customfields
    When I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_cf_system1.csv" file to "CSV file to upload" filemanager
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "1 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Completed certification : thisisevidence"
    And I should see "Completed certification : this is evidence for system1"

    When I follow "Completed certification : this is evidence for system1"
    Then I should see "Completed certification : this is evidence for system1"
    And I should see the evidence item fields contain:
      | Certification short name | this is evidence for system1 |
      | Certification ID number  | system1 certification        |
      | Completion date          | 1 January 2020               |
      | Due date                 | 31 January 2020              |
      | Import ID                | 3                            |
      | Custom text1             | Some custom evidence text    |
      | Custom cb11              | Yes                          |

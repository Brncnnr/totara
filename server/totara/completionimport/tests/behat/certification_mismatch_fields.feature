@totara @totara_customfield @totara_completion_upload @totara_evidence @javascript @_file_upload
Feature: Verify the case insensitive shortnames for certification completion imports works as expected
  As an admin
  I import certification completions with case mismatches
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

    And the following "certifications" exist in "totara_program" plugin:
      | fullname        | shortname | idnumber |
      | Certification 1 | CP101     | c1       |
      | Certification 2 | CP102     | c2       |

    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user      |
      | CP101    | learner01 |
      | CP101    | learner02 |
      | CP101    | learner03 |
      | CP101    | learner04 |
      | CP101    | learner05 |
      | CP101    | learner06 |
      | CP101    | learner07 |
      | CP101    | learner08 |
      | CP102    | learner01 |
      | CP102    | learner02 |
      | CP102    | learner03 |
      | CP102    | learner04 |
      | CP102    | learner05 |
      | CP102    | learner06 |
      | CP102    | learner07 |
      | CP102    | learner08 |

  Scenario: Basic certification completion import case insensitive is turned on
    When I log in as "admin"
    And I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_mismatch_fields_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload certification Create evidence" to "1"
    And I set the field "Upload certification Case insensitive shortnames" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "12 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I follow "Certification import report"
    And "1" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"
    And "6" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"
    And "7" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"
    And "9" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should not see "Other Evidence" in the ".tabtree" "css_element"
    And "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "1"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "1"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4: Other Evidence"
    And I should see "Completed certification : CP102"

    When I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "1"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "0"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob8 Learner8: Other Evidence"
    And I should see "Completed certification : CP101"

    When I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "0"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "0"

  Scenario: Basic certification completion import case insensitive is turned off
    When I log in as "admin"
    And I navigate to "Upload certification records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_mismatch_fields_1.csv" file to "CSV file to upload" filemanager
    And I set the field "Upload certification Create evidence" to "1"
    And I click on "Save" "button" in the ".totara_completionimport__uploadcertification_form" "css_element"
    Then I should see "Certification completion file successfully imported"
    And I should see "12 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_certification_completions_task"

    When I follow "Certification import report"
    And "1" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "6" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "7" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "9" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_certification" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_certification" table should contain "Yes"

    And "1" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "3" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "4" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "5" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "6" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "8" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "9" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "10" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"
    And "11" row "Errors" column of "completionimport_certification" table should contain "Duplicate ID Number"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should not see "Other Evidence" in the ".tabtree" "css_element"
    And "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "0"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "0"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4: Other Evidence"
    And I should see "Completed certification : CP102"

    When I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "0"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "0"

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then I should not see "Other Evidence" in the ".tabtree" "css_element"
    And "Certification 1" row "Previous completions" column of "plan_certifications" table should contain "0"
    And "Certification 2" row "Previous completions" column of "plan_certifications" table should contain "0"

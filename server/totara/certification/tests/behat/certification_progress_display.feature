@totara @totara_certification
Feature: Course progress display for certifications
  As I view a certification
  As a user
  I should see only relevant progress for courses

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
    And the following "courses" exist:
      | fullname         | shortname | format | enablecompletion |
      | Certify Course   | CC1       | topics | 1                |
      | Recertify Course | RC1       | topics | 1                |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime   | 0       |
      | enableprograms | Disable |
    And I set self completion for "Certify Course" in the "Miscellaneous" category
    And I set self completion for "Recertify Course" in the "Miscellaneous" category
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I press "Add new certification"
    And I set the following fields to these values:
      | Full name  | Test Certification |
      | Short name | testcert           |
    And I press "Save changes"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Certify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Recertify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Certification" tab
    And I set the following fields to these values:
      | activenum | 6 |
      | windownum | 2 |
    And I set the field "activeperiod" to "Month(s)"
    And I set the field "windowperiod" to "Month(s)"
    And I set the field "recertifydatetype" to "Use certification completion date"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user    |
      | testcert | user001 |

  @javascript
  Scenario: Check course progress display as user
    Given I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Certify Course"
    And I should see "0%" in the "Certify Course" "table_row"
    And I should not see "Recertify Course"

    When I click on "Certify Course" "link" in the ".display-program" "css_element"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    And I click on "Test Certification" "link"
    Then I should see "Test Certification"
    And I should see "You are currently certified - you do not need to work on this certification"
    And I should see "100%" in the "Certify Course" "table_row"
    And I should see "Recertify Course"
    And I should not see "0%" in the "Recertify Course" "table_row"

    When I wind back certification dates by 5 months
    And I run the scheduled task "\totara_certification\task\update_certification_task"
    And I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    And I click on "Test Certification" "link"
    Then I should see "Recertification window open"
    And I should not see "Certification path"
    And I should not see "Certify Course"
    And I should see "Recertification path"
    And I should see "Recertify Course"
    And I should see "0%" in the "Recertify Course" "table_row"

    When I click on "Recertify Course" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"

    Then I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    And I click on "Test Certification" "link"
    Then I should see "You are currently certified - you do not need to work on this certification"
    And I should see "Certify Course"
    And I should not see "0%" in the "Certify Course" "table_row"
    And I should not see "100%" in the "Certify Course" "table_row"

    And I should see "Recertify Course"
    And I should see "100%" in the "Recertify Course" "table_row"

    When I wind back certification dates by 7 months
    And I run the scheduled task "\totara_certification\task\update_certification_task"
    Then I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    And I click on "Test Certification" "link"

    Then I should see "Your certification has expired, you need to complete the original certification"
    And I should see "Original certification path"
    And I should see "0%" in the "Certify Course" "table_row"
    And I should not see "Recertification path"

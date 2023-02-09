@totara @totara_plan @report @javascript
Feature: Tests for programs using recurring course content

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "courses" exist:
      | fullname      | shortname   | enablecompletion |
      | Test Course 1 | testcourse1 | 1                |
      | Test Course 2 | testcourse2 | 1                |
      | Test Course 3 | testcourse3 | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname            | shortname |
      | Test program 1      | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user    |
      | program1 | user001 |
      | program1 | user002 |

  Scenario: Recurring courses can be added and emended within a program
    Given I log in as "admin"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Test program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I set the field "contenttype_ce" to "Recurring course"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I should see "Add course"
    And I click on "Miscellaneous" "link" in the "addrecurringcourse" "totaradialogue"
    And I click on "Test Course 1" "link" in the "addrecurringcourse" "totaradialogue"
    And I click on "Ok" "button" in the "addrecurringcourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    Then I should see "Test Course 1"
    And I log out

    # Check as learner.
    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Test program 1" "link"
    Then I should see "Recurring course set"
    And I should see "Test Course 1"
    And I log out

    # Amend the course.
    When I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Test program 1" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    Then I should see "Test Course 1"

    When I click on "//a[@id='amendrecurringcourselink']" "xpath_element"
    Then I should see "Change course" in the "//span[@class='ui-dialog-title']/span/h2" "xpath_element"
    And I should see "Test Course 1" in the "//span[@class='ui-dialog-title']/span/h2" "xpath_element"

    When I click on "Miscellaneous" "link"
    And I click on "Test Course 2" "link" in the "amendrecurringcourse" "totaradialogue"
    And I click on "Ok" "button" in the "amendrecurringcourse" "totaradialogue"
    Then I should see "Test Course 2"

    When I press "Save changes"
    And I click on "Save all changes" "button"
    Then I should see "Test Course 2"

    # Change the course again, before saving.
    When I click on "//a[@id='amendrecurringcourselink']" "xpath_element"
    Then I should see "Change course" in the "//span[@class='ui-dialog-title']/span/h2" "xpath_element"
    And I should see "Test Course 2" in the "//span[@class='ui-dialog-title']/span/h2" "xpath_element"

    When I click on "Miscellaneous" "link"
    And I click on "Test Course 3" "link" in the "amendrecurringcourse" "totaradialogue"
    And I click on "Ok" "button" in the "amendrecurringcourse" "totaradialogue"
    Then I should see "Test Course 3"

    # Save content page.
    When I press "Save changes"
    And I click on "Save all changes" "button"
    Then I should see "Test Course 3"
    And I log out

    When I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Test program 1" "link"
    Then I should see "Recurring course set"
    And I should see "Test Course 3"

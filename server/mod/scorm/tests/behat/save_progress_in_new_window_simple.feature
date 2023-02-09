@mod @mod_scorm @_file_upload @_switch_iframe @_alert
Feature: Confirm progress gets saved in new window - simple mode
  In order to let students access a scorm package
  As a teacher
  I need to add scorm activity to a course
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Progress data gets saved correctly
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name            | Large progress test |
      | Description     | Description         |
      | Display package | New window (simple) |
    And I upload "mod/scorm/tests/packages/large_progress_data.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Large progress test"
    And I log out
    And I log in as "student1"

    # Test medium amount (under 64kb) of progress data gets saved correctly when
    # closing tab without manually committing.

    And I am on "Course 1" course homepage
    And I follow "Large progress test"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_content_1" window
    And I press "Set 1000 records"
    And I close the current window
    And I switch to the main window

    And I am on "Course 1" course homepage
    And I follow "Large progress test"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_content_1" window
    And the "Set 1000 records" "button" should be disabled

    # Test large amount of progress data (over 64kb) gets saved correctly when
    # manually commiting.

    And I press "Set 2000 records"
    # manually commit:
    And I press "Exit"
    And I close the current window
    And I switch to the main window

    And I am on "Course 1" course homepage
    And I follow "Large progress test"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_content_1" window
    And the "Set 2000 records" "button" should be disabled
    And I close the current window

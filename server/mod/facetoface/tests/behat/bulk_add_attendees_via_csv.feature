@mod @mod_facetoface @mod_facetoface_attendees_add @totara @javascript @_file_upload
Feature: Add seminar attendees in bulk via csv file
  In order to test the bulk add attendees information result
  As admin
  I need to create an event, upload attendees through the bulk add attendees options.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | idnumber   |email                 |
      | student1 | John1     | Smith1   | I1         | student1@example.com |
      | student2 | John2     | Smith2   | I2         | student2@example.com |
      | student3 | John3     | Smith3   | I3         | student3@example.com |
      | student4 | John4     | Smith4   |            | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name    | course | idnumber |
      | facetoface | Seminar | C1     | seminar  |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

  Scenario: Upload csv file using 'Automatic' csv delimiter option
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Comma' csv delimiter option
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Comma (,)"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Semi-colon' csv delimiter option
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_semicolon.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Semi-colon (;)"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Colon' csv delimiter option
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_colon.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Colon (:)"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Tab' csv delimiter option
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_tab.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Tab (\t)"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Pipe' csv delimiter, which is not supported
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_pipe.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    When I press "Continue"
    Then I should see "The column called 'username', 'email', or 'idnumber' was not detected."

  Scenario: Upload csv file using 'Automatic' delimiter option with email address and no required custom fields
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_email_no_customfields.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Booked" in the "John1 Smith1" "table_row"
    And I should see "Booked" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Automatic' delimiter option with username and no required custom fields
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_username_no_customfields.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Booked" in the "John1 Smith1" "table_row"
    And I should see "Booked" in the "John2 Smith2" "table_row"

  Scenario: Upload csv file using 'Automatic' delimiter option with idnumber and no required custom fields
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_idnumber_no_customfields.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Automatic"
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Booked" in the "John1 Smith1" "table_row"
    And I should see "Booked" in the "John2 Smith2" "table_row"
    And I should see "Booked" in the "John3 Smith3" "table_row"

  Scenario: Upload csv file using 'Comma' delimiter option with case insensitive idnumber and no required custom fields
    Given I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_invalid_idnumber.csv" file to "CSV text file" filemanager
    And I set the field "delimiter" to "Comma (,)"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Bulk add attendees error"
    And I follow "View results"
    Then I should see "No user was found with the following user ID number: i2"

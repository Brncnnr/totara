@mod @mod_facetoface @mod_facetoface_attendees_add @totara @javascript @totara_customfield @_file_upload
Feature: Add seminar attendees from csv file with custom fields
  In order to test the bulk add attendees from file
  As a site manager
  I need to create an event, create sign-up custom fields and upload csv file with custom fields using bulk add attendees from file.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | John1     | Smith1   | student1@example.com |
      | student2 | John2     | Smith2   | student2@example.com |
      | student3 | John3     | Smith3   | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9159 | C1     | seminar  |
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Sign-up" "link"

    And I set the field "Create a new custom field" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | Signup checkbox |
      | Short name (must be unique) | checkbox        |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | Signup date/time |
      | Short name (must be unique) | datetime         |
      | Start year                  | 2020             |
    # ^^^ do not change the start year ^^^
    And I press "Save changes"

  Scenario: Login as manager, upload csv file with custom fields using bulk add attendees from file and check the result.

    And I set the field "Create a new custom field" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | Signup menu of choices |
      | Short name (must be unique) | menuofchoices          |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Signup multi select |
      | Short name (must be unique) | multiselect         |
      | multiselectitem[0][option]  | Tui                 |
      | multiselectitem[1][option]  | Moa                 |
      | multiselectitem[2][option]  | Tuatara             |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Signup text area |
      | Short name (must be unique) | textarea         |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Signup text input |
      | Short name (must be unique) | textinput         |
    And I press "Save changes"

    And I set the field "Create a new custom field" to "URL"
    And I set the following fields to these values:
      | Full name                   | Signup address |
      | Short name (must be unique) | url            |
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields.csv" file to "CSV text file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Uploaded via csv file" in the "John1 Smith1" "table_row"
    And I should see "Yes" in the "John1 Smith1" "table_row"
    And I should see "2 Mar 2035" in the "John1 Smith1" "table_row"
    And I should see "Apple" in the "John1 Smith1" "table_row"
    And I should see "Tui, Moa" in the "John1 Smith1" "table_row"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John1 Smith1" "table_row"
    And I should see "Lorem ipsum dolor sit amet" in the "John1 Smith1" "table_row"
    And I should see "http://www.totaralearning.com" in the "John1 Smith1" "table_row"

    And I should see "Also uploaded via csv file" in the "John2 Smith2" "table_row"
    And I should see "Yes" in the "John2 Smith2" "table_row"
    And I should see "3 Apr 2036" in the "John2 Smith2" "table_row"
    And I should see "Orange" in the "John2 Smith2" "table_row"
    And I should see "Moa, Tuatara" in the "John2 Smith2" "table_row"
    And I should see "Consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John2 Smith2" "table_row"
    And I should see "Consectetur adipisicing elit" in the "John2 Smith2" "table_row"
    And I should see "https://google.com" in the "John2 Smith2" "table_row"

    And I should see "More uploaded via csv file" in the "John3 Smith3" "table_row"
    And I should see "No" in the "John3 Smith3" "table_row"
    And I should see "4 May 2037" in the "John3 Smith3" "table_row"
    And I should see "Banana" in the "John3 Smith3" "table_row"
    And I should see "Tuatara" in the "John3 Smith3" "table_row"
    And I should see "Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua." in the "John3 Smith3" "table_row"
    And I should see "Sed do eiusmod tempor incididunt" in the "John3 Smith3" "table_row"
    And I should see "/mod/facetoface/view.php?id=1" in the "John3 Smith3" "table_row"

  Scenario: Valid CSV format, but where header and columns are missed

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields_columns.csv" file to "CSV text file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Booked" in the "John1 Smith1" "table_row"
    And I should see "Booked" in the "John2 Smith2" "table_row"
    And I should see "Booked" in the "John3 Smith3" "table_row"

  Scenario: Invalid CSV format, one of the custom field values is missed

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields_invalid_columns2.csv" file to "CSV text file" filemanager
    When I press "Continue"
    Then I should see "Invalid CSV file format - number of columns is not constant!"

  Scenario: Login as manager, upload csv file with required multi-select custom field using bulk add attendees from file and check the result.

    And I set the field "Create a new custom field" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | Beer        |
      | Short name (must be unique) | multiselect |
      | This field is required      | Yes         |
      | multiselectitem[0][option]  | Tui         |
      | multiselectitem[1][option]  | Moa         |
      | multiselectitem[2][option]  | Tuatara     |
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_required_customfields.csv" file to "CSV text file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "Tui, Moa" in the "John1 Smith1" "table_row"
    And I should see "Moa, Tuatara" in the "John2 Smith2" "table_row"
    And I should see "Tuatara" in the "John3 Smith3" "table_row"

  Scenario: Add users via file upload with customfield hidden

    And I click on "Hide" "link" in the "Requests for session organiser" "table_row"
    And I click on "Hide" "link" in the "Signup date/time" "table_row"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add event"
    And I press "Save changes"

    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users via file upload"
    And I upload "mod/facetoface/tests/fixtures/f2f_attendees_customfields_hidden.csv" file to "CSV text file" filemanager
    And I press "Continue"
    When I press "Confirm"
    Then I should see "John1 Smith1"
    And I should see "John2 Smith2"
    And I should see "John3 Smith3"

@mod @mod_facetoface @totara @javascript
Feature: Select from more than four available facetoface event roles

  Scenario: Select facetoface event roles using multiselect
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname |
      | trainer1 | Tahi      | Trainer  |
      | trainer2 | Rua       | Trainer  |
      | trainer3 | Toru      | Trainer  |
      | trainer4 | Whā       | Trainer  |
      | trainer5 | Rima      | Trainer  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | course1   | 0        |
    And the following "course enrolments" exist:
      | user     | course  | role           |
      | trainer1 | course1 | editingteacher |
      | trainer2 | course1 | editingteacher |
      | trainer3 | course1 | editingteacher |
      | trainer4 | course1 | editingteacher |
      | trainer5 | course1 | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | course  |
      | Test Seminar | course1 |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_3" to "1"
    And I press "Save changes"
    And I log out

    And I log in as "trainer1"
    And I am on "Test Seminar" seminar homepage
    And I follow "Add event"
    And I set the field "Editing Trainer" to "Tahi Trainer, Rua Trainer, Whā Trainer"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "Booking open"
    And I follow "Event details"
    Then I should see "Editing Trainer"
    And I should see "Tahi Trainer, Rua Trainer, Whā Trainer"

    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the field "Editing Trainer" to "Tahi Trainer"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "Booking open"
    And I follow "Event details"
    Then I should see "Editing Trainer"
    And I should see "Tahi Trainer" in the "#region-main" "css_element"
    And I should not see "Rua Trainer"
    And I should not see "Whā Trainer"

    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Edit event" in row "#1"
    And I set the field "Editing Trainer" to "None"
    And I press "Save changes"
    When I click on the seminar event action "Attendees" in row "Booking open"
    And I follow "Event details"
    Then I should not see "Editing Trainer"
    And I should not see "Tahi Trainer" in the "#region-main" "css_element"

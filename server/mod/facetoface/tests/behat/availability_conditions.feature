@mod @mod_facetoface @availability @totara @javascript
Feature: Seminar availability based on activity completion
  In order to check if a Seminar activity is available
  As a teacher
  I need to see if there is any condition prior to the Seminar activity

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username | email            |
      | teacher1 | teacher1@example.com |
      | student1 | student1@example.com |
      | student2 | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following config values are set as admin:
      | enableavailability  | 1 |
      | enablecompletion    | 1 |

    # Add an activity with manual completion.
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Certificate" to section "1" and I fill the form with:
      | Name             | Certificate 1 |

    # Create a Seminar activity and add restriction so it won't be available until the Certificate is marked as completed
    And I add a "Seminar" to section "1"
    And I set the following fields to these values:
      | Name             | Test seminar 1 |
      | Description      | Test seminar 1 |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And "Add restriction..." "dialogue" should be visible
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "Certificate 1"
    And I press "Save and return to course"
    And I should see "Not available unless: The activity Certificate 1 is marked complete"
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I log out

  Scenario: Signup link is not available until the completion restriction is met
    Given I log in as "student1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    Then I should see "Not available unless: The activity Certificate 1 is marked complete"
    And I should not see "Sign-up"
    And I should not see "Go to event"

    When I set the field "Manual completion of Certificate 1" to "1"
    And I click on "Go to event" "link" in the "1 January" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I log out

  Scenario: Join Waitlist link is not available until the completion restriction is met
    Given I log in as "teacher1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I click on the seminar event action "Edit event" in row "0 / 10"
    And I click on "Delete" "link" in the "1 January" "table_row"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    Then I should see "Not available unless: The activity Certificate 1 is marked complete"
    And I should not see "Join waitlist"
    And I should not see "Go to event"

    When I set the field "Manual completion of Certificate 1" to "1"
    And I click on "Go to event" "link" in the "Wait-listed" "table_row"
    Then I should see "Join waitlist" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I log out

  Scenario: Signup link is only available for users that meets the user's profile restriction
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "Delete" "link" in the ".availability-item" "css_element"
    And I click on "Add restriction..." "button"
    And I click on "User profile" "button" in the "Add restriction..." "dialogue"
    And I set the field "User profile field" to "Email address"
    And I set the field "Value to compare against" to "student1@example.com"
    And I set the field "Method of comparison" to "is equal to"
    And I press "Save and display"
    And I log out

    When I log in as "student1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I click on "Go to event" "link" in the "1 January" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I log out

    When I log in as "student2"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    Then I should not see "Sign-up"
    And I should not see "Go to event"
    And I log out

  Scenario: Join Waitlist link is only available for users that meets the user's profile restriction
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "Delete" "link" in the ".availability-item" "css_element"
    And I click on "Add restriction..." "button"
    And I click on "User profile" "button" in the "Add restriction..." "dialogue"
    And I set the field "User profile field" to "Email address"
    And I set the field "Value to compare against" to "student1@example.com"
    And I set the field "Method of comparison" to "is equal to"
    And I press "Save and display"
    And I click on the seminar event action "Edit event" in row "0 / 10"
    And I click on "Delete" "link" in the "1 January" "table_row"
    And I press "Save changes"
    And I log out

    When I log in as "student1"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I click on "Go to event" "link" in the "Wait-listed" "table_row"
    Then I should see "Join waitlist" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"
    And I log out

    When I log in as "student2"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    Then I should not see "Join waitlist"
    And I should not see "Go to event"
    And I log out

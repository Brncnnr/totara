@totara @totara_program @totara_notification @javascript
Feature: Check program notifications

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |
      | Program Two | prog2     | prog2    |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "users" exist:
      | username | firstname     | lastname | email                |
      | authuser | Authenticated | User     | authuser@example.com |
      | progman  | Program       | Manager  | progman@example.com  |
      | john     | John          | Smith    | john@example.com     |
      | mary     | Mary          | Jones    | mary@example.com     |
    And the following "roles" exist:
      | shortname   |
      | progmanager |
    And the following "role assigns" exist:
      | user    | role        | contextlevel  | reference |
      | progman | progmanager | Program       | prog1     |

  Scenario: program manager not allows to edit program notification
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuredetails      | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    When I press "Edit program details"
    Then I should not see "Notifications"
    And I log out

  Scenario: totara/program:configuremessages allows a user to edit program notification
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuremessages     | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    When I press "Edit program details"
    And I switch to "Notifications" tab
    Then I should see "Notifications"
    And I log out

  Scenario: program manager can configure program notification at the admin level
    Given
    And the following "role assigns" exist:
      | user    | role        | contextlevel  | reference |
      | progman | progmanager | System        | prog2     |
    And the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel  | reference |
      | totara/program:configuremessages     | Allow      | progmanager   | System        | prog2     |
    And I log in as "progman"
    When I navigate to system notifications page
    Then I should see "Notifications"
    And I log out

  Scenario: program manager should not see messages tab
    Given the following "permission overrides" exist:
      | capability                           | permission | role          | contextlevel | reference |
      | totara/program:configuremessages     | Allow      | progmanager   | Program      | prog1     |
    And I log in as "progman"
    And I am on "Program One" program homepage
    When I press "Edit program details"
    Then I should not see "Messages"

  @javascript
  Scenario: A notification gets sent with the correctly formatted subject line when triggered
    When I log in as "admin"
    # Create a custom notification fpr 'Learner assigned in program'
    And I navigate to system notifications page
    Then I should see "Program"
    When I click on "Expand all" "button"
    And I click on "Learner assigned in program" "button"
    When I click on "Actions for Learner assigned in program event" "button"
    And I should see "Create notification"
    And I click on "//a[@aria-label='Create notification for event Learner assigned in program']" "xpath_element"
    And I set the field "Name" to "Custom notification one"
    And I click on the "totara_notification\recipient\subject" tui checkbox in the "recipients" tui checkbox group
    And I set the weka editor with css ".tui-notificationPreferenceForm__subjectEditor" to "Test custom notification subject 'single quote'"
    And I set the weka editor with css ".tui-notificationPreferenceForm__bodyEditor" to "Test custom notification body"
    And I click on the "enabled[value]" tui checkbox
    And I click on "Save" "button"
    Then I should see "Custom notification one"
    # Assign a user as Learner to the program
    When I am on "Program One" program homepage
    And I press "Edit program details"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "John Smith (john@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds
    # Run cron
    When I reset the email sink
    And I trigger cron
    # A Totara notification & an email should have been sent with the correct subject
    Then the message "Test custom notification subject 'single quote'" contains "Test custom notification body" for "john" user
    And the following emails should have been sent:
      | To                  | Subject                                        | Body                          |
      | john@example.com    | Test custom notification subject 'single quote'| Test custom notification body |

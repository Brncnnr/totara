@javascript @mod @mod_facetoface @mod_facetoface_notification @totara
Feature: Check seminar session date/time changed notification
  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname |                email | alternatename |
      |  trainer |       The |  Trainer | trainer@example.com  |               |
      | learner1 |       Uno |  Learner | learner1@example.com |               |
      | learner2 |       Dos |  Learner | learner2@example.com |               |
      | learner3 |      Tres |  Learner | learner3@example.com |               |
      | learner4 |    Cuatro |  Learner | learner4@example.com |               |
      | learner5 |     Cinco |  Learner | learner5@example.com |               |
      | learner6 |      Seis |  Learner | learner6@example.com |               |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      |   name | allowconflicts |
      | Room 1 |              1 |
      | Room 2 |              1 |
    And the following "global assets" exist in "mod_facetoface" plugin:
      |    name | allowconflicts |
      | Asset 1 |              1 |
      | Asset 2 |              1 |
    And the following "global facilitators" exist in "mod_facetoface" plugin:
      |          name | allowconflicts |
      | Facilitator 1 |              1 |
      | Facilitator 2 |              1 |
    And the following "seminars" exist in "mod_facetoface" plugin:
      |         name | intro | course |
      | Test seminar |       | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      |   facetoface | details |
      | Test seminar | Event 1 |
      | Test seminar | Event 2 |
      | Test seminar | Event 3 |
    And the following "course enrolments" exist:
      |     user | course |    role |
      | trainer  | C1     | teacher |
      | learner1 | C1     | student |
      | learner2 | C1     | student |
      | learner3 | C1     | student |
      | learner4 | C1     | student |
      | learner5 | C1     | student |
      | learner6 | C1     | student |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails |   start |  finish |  rooms |  assets |  facilitators |      sessiontimezone |   starttimezone |  finishtimezone |
      |      Event 1 | -4 hour | -3 hour | Room 1 | Asset 1 | Facilitator 1 |     Indian/Christmas | Australia/Perth | Australia/Perth |
      |      Event 2 | -1 hour | +3 hour | Room 1 | Asset 1 | Facilitator 1 | Antarctica/Troll     | Australia/Perth | Australia/Perth |
      |      Event 3 | +4 hour | +5 hour | Room 1 | Asset 1 | Facilitator 1 |       Asia/Oral      | Australia/Perth | Australia/Perth |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      |     user | eventdetails |     status |
      | learner1 |      Event 1 |     booked |
      | learner2 |      Event 1 | waitlisted |
      | learner3 |      Event 2 |     booked |
      | learner4 |      Event 2 | waitlisted |
      | learner5 |      Event 3 |     booked |
      | learner6 |      Event 3 | waitlisted |

  Scenario Outline: Check notification when resource is asssigned
    Given I log in as "trainer"
    And I am on "Test seminar" seminar homepage
    When I click on the seminar event action "Edit event" in row "Christmas"
    And I click on "Select <plural>" "link" in the "Christmas" "table_row"
    And I click on "<type> 2" "text" in the "Choose <plural>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <plural>" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Troll"
    And I click on "Select <plural>" "link" in the "Troll" "table_row"
    And I click on "<type> 2" "text" in the "Choose <plural>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <plural>" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Oral"
    And I click on "Select <plural>" "link" in the "Oral" "table_row"
    And I click on "<type> 2" "text" in the "Choose <plural>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <plural>" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

    Given I run all adhoc tasks

    And I log in as "learner1"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Christmas"
    And I log out
    And I log in as "learner2"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Christmas"
    And I log out
    And I log in as "learner3"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Troll"
    And I log out
    And I log in as "learner4"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Troll"
    And I log out
    And I log in as "learner5"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Oral"
    And I log out
    And I log in as "learner6"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Oral"
    And I log out

    Examples:
      |        type |       plural | should_or_not |                   view |
      |        Room |        rooms |        should | View full notification |
      | Facilitator | facilitators |        should | View full notification |
      |       Asset |       assets |    should not |                See all |

  Scenario Outline: Check notification when resource is unasssigned
    Given I log in as "trainer"
    And I am on "Test seminar" seminar homepage
    When I click on the seminar event action "Edit event" in row "Christmas"
    And I click on "Remove <singular> <type> 1 from session" "link" in the "Christmas" "table_row"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Troll"
    And I click on "Remove <singular> <type> 1 from session" "link" in the "Troll" "table_row"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Oral"
    And I click on "Remove <singular> <type> 1 from session" "link" in the "Oral" "table_row"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

    Given I run all adhoc tasks

    And I log in as "learner1"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Christmas"
    And I log out
    And I log in as "learner2"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Christmas"
    And I log out
    And I log in as "learner3"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Troll"
    And I log out
    And I log in as "learner4"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Troll"
    And I log out
    And I log in as "learner5"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Oral"
    And I log out
    And I log in as "learner6"
    And I open the notification popover
    And I wait for pending js
    Then I <should_or_not> see "Seminar date/time changed: Test seminar"
    When I click on "<view>" "link_exact" in the ".popover-region-notifications" "css_element"
    Then I <should_or_not> see "Oral"
    And I log out

    Examples:
      |        type |    singular | should_or_not |                   view |
      |        Room |        room |        should | View full notification |
      | Facilitator | facilitator |        should | View full notification |
      |       Asset |       asset |    should not |                See all |

  Scenario: Check notification when date/time is changed
    Given I log in as "trainer"
    And I am on "Test seminar" seminar homepage
    When I click on the seminar event action "Edit event" in row "Christmas"
    And I click on "Edit session" "link" in the "Christmas" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[timezone]  | Australia/Perth |
      | timestart[hour]      | -5 |
      | timefinish[timezone] | Australia/Perth |
      | timefinish[hour]     | -4 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Troll"
    And I click on "Edit session" "link" in the "Troll" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[timezone]  | Australia/Perth |
      | timestart[hour]      | -2 |
      | timefinish[timezone] | Australia/Perth |
      | timefinish[hour]     | +2 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    When I click on the seminar event action "Edit event" in row "Oral"
    And I click on "Edit session" "link" in the "Oral" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[timezone]  | Australia/Perth |
      | timestart[hour]      | +5 |
      | timefinish[timezone] | Australia/Perth |
      | timefinish[hour]     | +6 |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    Then I should not see "Editing event in"
    And I log out

    Given I run all adhoc tasks

    And I log in as "learner1"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Christmas"
    And I log out
    And I log in as "learner2"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Christmas"
    And I log out
    And I log in as "learner3"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Troll"
    And I log out
    And I log in as "learner4"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Troll"
    And I log out
    And I log in as "learner5"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Oral"
    And I log out
    And I log in as "learner6"
    And I open the notification popover
    And I wait for pending js
    Then I should see "Seminar date/time changed: Test seminar"
    When I follow "View full notification"
    Then I should see "Oral"
    And I log out

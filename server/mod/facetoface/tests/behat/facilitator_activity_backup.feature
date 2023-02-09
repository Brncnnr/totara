@mod @mod_facetoface @totara @core_backup
# This test use backup functionality, if it works then backup/restore should work too
Feature: Test facilitator conflicts through backup/restore
  In order to test Face to face facilitator conflicts
  As a site manager
  I need to create facetoface, add sessions, add facilitator to each session with different facilitator conflict settings

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course |
      | seminar 1 | C1     |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface | details |
      | seminar 1  | event 1 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start       | finish                  |
      | event 1      | now         | now +60 minutes         |
      | event 1      | now +2 days | now +2 days +60 minutes |

    And I log in as "admin"
    And I navigate to "Facilitators" node in "Site administration > Seminars"
    And I press "Add a new facilitator"
    And I set the following fields to these values:
      | Name                    | facilitator 1 |
      | Facilitator type        | External      |
      | Allow booking conflicts | 0             |
    And I press "Add a facilitator"

    And I press "Add a new facilitator"
    And I set the following fields to these values:
      | Name                    | facilitator 2 |
      | Facilitator type        | External      |
      | Allow booking conflicts | 1             |
    And I press "Add a facilitator"

  @javascript
  Scenario: Add sessions with different facilitators and duplicate facetoface
    And I am on "Course 1" course homepage
    And I follow "seminar 1"

    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Select facilitator" "link"
    And I click on "facilitator 1" "text" in the "Choose facilitators" "totaradialogue"
    And I click on "OK" "button" in the "Choose facilitators" "totaradialogue"
    And I press "Save changes"

    And I click on the seminar event action "Edit event" in row "#1"
    And I click on "Select facilitator" "link"
    And I click on "facilitator 2" "text" in the "Choose facilitators" "totaradialogue"
    And I click on "OK" "button" in the "Choose facilitators" "totaradialogue"
    And I press "Save changes"

    And I am on "Course 1" course homepage with editing mode on
    And I open "seminar 1" actions menu

    When I click on "Duplicate" "link" in the "seminar 1" activity
    And I turn editing mode off
    Then I should see "facilitator 2" in the ".activity.facetoface:first-child" "css_element"
    And I should see "facilitator 1" in the ".activity.facetoface:first-child" "css_element"
    # The facilitator with prevent conflict should not appear.
    And I should see "facilitator 2" in the ".activity.facetoface:last-child" "css_element"
    And I should not see "facilitator 1" in the ".activity.facetoface:last-child" "css_element"


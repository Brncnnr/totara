@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation status
  After seminar events have been cancelled
  As admin
  I need to check the status for each user associated with it

  Background:
    Given I am on a totara site
    And I am using legacy seminar notifications
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
      | learner2 | Learner   | Two      | learner2@example.com |
      | learner3 | Learner   | Three    | learner3@example.com |
      | learner4 | Learner   | Four     | learner4@example.com |
      | learner5 | Learner   | Five     | learner5@example.com |
      | manager1 | Manager   | One      | manager1@example.com |

    And the following job assignments exist:
      | user     | manager  |
      | learner1 | manager1 |
      | learner2 | manager1 |
      | learner3 | manager1 |
      | learner4 | manager1 |
      | learner5 | manager1 |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    Given the following "course enrolments" exist:
      | user     | course | role            |
      | learner3 | C1     | student         |
      | learner4 | C1     | student         |
      | manager1 | C1     | editingteacher  |

    And the following "seminars" exist in "mod_facetoface" plugin:
      | name         | intro               | course  |
      | Test Seminar | <p>Test Seminar</p> | C1      |

    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface   | details |
      | Test Seminar | event 1 |

    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | start                | finish               | starttimezone    | finishtimezone   | sessiontimezone  |
      | event 1      | 10 Feb next year 9am | 10 Feb next year 3pm | Pacific/Auckland | Pacific/Auckland | Pacific/Auckland |

    Given I log in as "admin"
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I expand "Enrolments" node
    And I follow "Manage enrol plugins"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"

  # -------------------------------------------------------------------------------------
  Scenario: Event cancellation in a Seminar with manager approval required.
    Given I am on "Test Seminar" seminar homepage
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"

    Then I navigate to "Enrolment methods" node in "Course administration > Users"
    And I set the field "Add method" to "Seminar direct enrolment"
    And I press "Add method"
    And I log out

#    Users requesting approval
    Given I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I log out

    Given I log in as "learner2"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I log out

#   Learner Five requesting approval and immediately withdrawing his pending request
    Given I log in as "learner5"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Manager Approval"
    And I press "Request approval"
    And I should see "Your request was sent to your manager for approval."
    Then I am on "Course 1" course homepage
    And I should see "It is not possible to sign up for these events (manager request already pending)."
    And I should see "Withdraw pending request"
    And I click on "Withdraw pending request" "link"
    And I press "Confirm"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I should see "Request approval"
    And I log out

#   Manager adding Learners 3 and 4 as attendees, approving Learner 1 and declining request for Learner 2
    Given I log in as "manager1"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner Three, learner3@example.com,Learner Four, learner4@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"

    When I follow "Approval required"
    Then I should see "Learner One"
    And I should see "Learner Two"
    And I should see "Learner Three"
    And I should see "Learner Four"
    And I should not see "Learner Five"

    And I set the following fields to these values:
      | Approve Learner Three for this event | 1 |
      | Approve Learner Four for this event  | 1 |
      | Approve Learner One for this event   | 1 |
      | Decline Learner Two for this event   | 1 |
    And I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should see "No pending approvals"

#   Checking users status as a manager
    When I follow "Attendees"
    Then I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Four" in the "#facetoface_sessions" "css_element"
    And I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Two" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Five" in the "#facetoface_sessions" "css_element"
    When I follow "Cancellations"
    Then I should see "Learner Five" in the "User Cancelled" "table_row"
    And I should not see "Learner Two" in the "User Cancelled" "table_row"
    And I run all adhoc tasks
    And I log out

#  Checking status as learners
    Given I log in as "learner1"
    And I am on "Dashboard" page
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I log out

    Given I log in as "learner2"
    And I am on "Dashboard" page
    Then I should see "Seminar booking decline"
    And I am on "Dashboard" page
    And I should not see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner3"
    And I am on "Dashboard" page
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I am on "Dashboard" page
    And I should see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner4"
    And I am on "Dashboard" page
    Then I should see "Seminar booking confirmation: Test Seminar"
    And I should see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

    Given I log in as "learner5"
    And I am on "Dashboard" page
    Then I should see "Seminar booking request: Test Seminar"
    And I should not see "Seminar booking confirmation: Test Seminar"
    And I should not see "Seminar booking decline"
    And I am on "Dashboard" page
    And I should not see "Course 1" in the "div.block_current_learning" "css_element"
    And I log out

#  Cancel the event and check status again. Cancelled users should remain in the cancellation tab and declined users
#  should not appear anywhere
    Given I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    When I click on the seminar event action "Cancel event" in row "3 / 10"
    And I should see "Cancelling event in"
    And I should see "Cancelling this event will remove all of its booking, attendance and grade records. All attendees will be notified."
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    When I click on the seminar event action "Attendees" in row "3 / 10"
    And I should see "Cancellations" in the "li.active" "css_element"
    Then I should see "Event Cancelled" in the "Learner One" "table_row"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"
    And I should see "Event Cancelled" in the "Learner Four" "table_row"
    And I should see "User Cancelled" in the "Learner Five" "table_row"
    And I should see "Declined" in the "Learner Two" "table_row"

  # -------------------------------------------------------------------------------------
  Scenario: Event cancellation in a Seminar with users that have cancelled their session.
    Given I log out
    When I log in as "learner3"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    And I press "Sign-up"
    Then I should see "Your request was accepted"
    And I follow "View all events"
    When I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Booked" in the ".mod_facetoface__eventinfo__sidebars" "css_element"
    And I should see "Cancel booking" in the ".mod_facetoface__eventinfo__sidebar__cancellation" "css_element"
    And I log out

    Given I log in as "manager1"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "potential users" to "Learner One, learner1@example.com,Learner Five, learner5@example.com"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Five" in the "#facetoface_sessions" "css_element"
    And I log out

    When I log in as "learner5"
    And I am on "Course 1" course homepage
    And I click on "Go to event" "link" in the "(Booked)" "table_row"
    And I click on "Cancel booking" "link_or_button" in the seminar event sidebar "Booked"
    And I wait "1" seconds
    And I click on "Cancel booking" "button" in the seminar event sidebar "Cancel booking"
    Then I should see "Your booking has been cancelled."
    And I log out

    When I log in as "manager1"
    And I am on "Test Seminar" seminar homepage
    And I click on the seminar event action "Attendees" in row "#1"
    Then I should see "Learner One" in the "#facetoface_sessions" "css_element"
    And I should see "Learner Three" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Two" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Four" in the "#facetoface_sessions" "css_element"
    And I should not see "Learner Five" in the "#facetoface_sessions" "css_element"
    When I follow "Cancellations"
    Then I should see "Learner Five" in the "User Cancelled" "table_row"
    And I should not see "Learner Two" in the "User Cancelled" "table_row"
    And I log out

    When I log in as "admin"
    And I am on "Test Seminar" seminar homepage
    When I click on the seminar event action "Cancel event" in row "#1"
    And I should see "Cancelling event in"
    And I should see "Cancelling this event will remove all of its booking, attendance and grade records. All attendees will be notified."
    And I press "Yes"
    Then I should see "Event cancelled" in the ".alert-success" "css_element"
    When I click on the seminar event action "Attendees" in row "#1"
    And I should see "Cancellations" in the "li.active" "css_element"
    Then I should see "Event Cancelled" in the "Learner One" "table_row"
    And I should see "Event Cancelled" in the "Learner Three" "table_row"
    And I should see "User Cancelled" in the "Learner Five" "table_row"
    And I should not see "Learner Two"

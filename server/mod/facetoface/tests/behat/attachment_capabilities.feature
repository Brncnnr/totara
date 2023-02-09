@javascript @mod @mod_facetoface @totara
Feature: Check asset/facilitator actions are performed by users with the right permissions
  In order to check users with the right permission could perform action on the asset/facilitator mange/edit pages
  As Admin
  I need to set users with different capabilities and perform asset/facilitator actions as the users

  Background:
    Given I am on a totara site
    And the following "permission overrides" exist:
      | capability            | permission | role    | contextlevel | reference |
      | totara/core:modconfig | Allow      | manager | System       |           |
    And the following "users" exist:
      | username  | firstname | lastname | email                |
      | learner1  | learner   | 1        | learner1@example.com |
      | trainer1  | Trainer   | One      | trainer1@example.com |
      | trainer2  | Trainer   | Two      | trainer2@example.com |
      | manager   | Site      | Manager  | manager@example.com  |
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
      | eventdetails | start        | finish       |
      | event 1      | now +2 hours | now +4 hours |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | learner1 | C1     | student        |
      | trainer1 | C1     | editingteacher |
      | trainer2 | C1     | teacher        |
      | manager  | C1     | manager        |
    And the following "seminar signups" exist in "mod_facetoface" plugin:
      | user     | eventdetails |
      | learner1 | event 1      |

    And I log in as "admin"
    And I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I follow "Site Manager"
    And I set the field "Potential users" to "Site Manager (manager@example.com)"
    And I press "Add"
    And I log out

  Scenario Outline: Check manageadhoc capability for editingteacher role
    Given I log in as "trainer1"
    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Create" in the "Choose <collection_type>" "totaradialogue"
    And I click on "Create" "link" in the "Choose <collection_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name | <name> |
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then "Remove <item_type> <name> from session" "link" should exist
    And "Edit custom <item_type> <name> in session" "link" should exist
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I log out

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

  Scenario Outline: Check editingteacher role permission with removed manageadhoc capability
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                                  | permission | role           | contextlevel | reference |
      | mod/facetoface:manageadhoc<collection_type> | Prohibit   | editingteacher | Course       | C1        |
    And I log out
    And I log in as "trainer1"
    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should not see "Create" in the "Choose <collection_type>" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Cancel"
    And I log out

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

  Scenario Outline: Check manageadhoc capability for teacher role
    Given I log in as "trainer2"
    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Create" in the "Choose <collection_type>" "totaradialogue"
    When I click on "Create" "link" in the "Choose <collection_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name | <name> |
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then "Remove <item_type> <name> from session" "link" should exist
    And "Edit custom <item_type> <name> in session" "link" should exist
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I log out

    Examples:
      | name        | item_type   | collection_type | column_or_node |
      | Asset       | asset       | assets          | Assets         |
      | Facilitator | facilitator | facilitators    | Facilitators   |

  Scenario Outline: Check teacher role permission with removed manageadhoc capability
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                                  | permission | role    | contextlevel | reference |
      | mod/facetoface:manageadhoc<collection_type> | Prohibit   | teacher | Course       | C1        |
    And I log out
    And I log in as "trainer2"
    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should not see "Create" in the "Choose <collection_type>" "totaradialogue"
    And I click on "Cancel" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Cancel"
    And I log out

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

  Scenario Outline: Check manageadhoc/managesitewide capabilities for manager
    Given I log in as "manager"
    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Create" in the "Choose <collection_type>" "totaradialogue"
    When I click on "Create" "link" in the "Choose <collection_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name | <name> Zero |
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then "Remove <item_type> <name> Zero from session" "link" should exist
    And "Edit custom <item_type> <name> Zero in session" "link" should exist
    And I press "Save changes"
    Then I should see "Upcoming events"

    # TL-23000 made impossible to create a facilitator with the same steps as an asset
    Given the following "global <collection_type>" exist in "mod_facetoface" plugin:
      | name       |
      | <name> One |

    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Create" in the "Choose <collection_type>" "totaradialogue"
    And I click on "<name> One" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    Then "Remove <item_type> <name> One from session" "link" should exist
    But "Edit custom <item_type> <name> One in session" "link" should not exist
    And I press "Save changes"
    Then I should see "Upcoming events"

    # TL-23000 made impossible to create a facilitator with the same steps as an asset
    Given the following "global <collection_type>" exist in "mod_facetoface" plugin:
      | name       |
      | <name> Two |

    And I am on "seminar 1" seminar homepage
    And I click on the seminar event action "Edit event" in row "Upcoming"
    When I click on "Select <collection_type>" "link"
    Then I should see "Browse" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Search" in the "Choose <collection_type>" "totaradialogue"
    And I should see "Create" in the "Choose <collection_type>" "totaradialogue"
    And I click on "<name> Two" "text" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    Then "Remove <item_type> <name> Two from session" "link" should exist
    But "Edit custom <item_type> <name> Two in session" "link" should not exist
    And I press "Save changes"
    Then I should see "Upcoming events"
    And I log out

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |

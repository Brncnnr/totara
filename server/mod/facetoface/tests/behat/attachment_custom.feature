@javascript @mod @mod_facetoface @totara
Feature: Manage custom assets/facilitators by non-admin user
  In order to test that non-admin user
  As a editing teacher
  I need to create and edit custom assets/facilitators

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name              | course | intro                           |
      | Test seminar name | C1     | <p>Test seminar description</p> |
    And I log in as "teacher1"
    And I am on "Test seminar name" seminar homepage
    And I follow "Add event"

  Scenario Outline: Add edit seminar custom item as editing teacher
    And I click on "Select <collection_type>" "link"
    And I click on "Create" "link"
    And I should see "Create new <item_type>" in the "Create new <item_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name                     | <name> 1 |
      | Allow booking conflicts  | 1        |
      | description_editor[text] | Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. |
    And I should not see "Add to sitewide list"
    When I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then I should see "<name> 1"

    # Edit
    And I click on "Edit custom <item_type> <name> 1 in session" "link"
    And I should see "Edit <item_type>" in the "Edit <item_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name | <name> updated |
    And I should not see "Add to sitewide list"
    When I click on "OK" "button" in the "Edit <item_type>" "totaradialogue"
    Then I should see "<name> updated"

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

  Scenario Outline: Confirm images work when adding an item through a totaradialogue
    And I click on "Select <collection_type>" "link"
    And I click on "Create" "link"
    And I should see "Create new <item_type>" in the "Create new <item_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name                     | <name> 1 |
      | Allow booking conflicts  | 1        |
      | description_editor[text] | Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. |
    And I click on "Image" "button" in the "Create new <item_type>" "totaradialogue"
    And I click on "Browse repositories..." "button"
    And I click on "Wikimedia" "link"
    And I set the field "Search for:" to "dog"
    And I press "Submit"
    And I click on "dog" "text"
    And I press "Select this file"
    And I set the field "Describe this image for someone who cannot see it" to "hello"
    And I press "Save image"
    And I set the field "Name" to "woof"
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then I should see "woof"

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

  Scenario Outline: Confirm images load when viewing added items
    And I click on "Select <collection_type>" "link"
    And I click on "Create" "link"
    And I should see "Create new <item_type>" in the "Create new <item_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name                     | <name> 1 |
      | Allow booking conflicts  | 1        |
      | description_editor[text] | Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. |
    And I click on "Image" "button" in the "Create new <item_type>" "totaradialogue"
    And I click on "Browse repositories..." "button"
    And I click on "Wikimedia" "link"
    And I set the field "Search for:" to "dog"
    And I press "Submit"
    And I click on "dog" "text"
    And I press "Select this file"
    And I set the field "Describe this image for someone who cannot see it" to "hello"
    And I press "Save image"
    And I set the field "Name" to "woof"
    And I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then I should see "woof"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    When I navigate to "<column_or_node>" node in "Site administration > Seminars"
    Then I should see "There are no records that match your selected criteria"
    When I set the field "Sitewide" to "No"
    And I click on "#id_submitgroupstandard_addfilter" "css_element"
    And I click on "woof" "link" in the "facetoface_<collection_type>" "table"
    Then I should see image with alt text "hello"
    When I press "Back to <collection_type>"
    Then I should see "There are no records that match your selected criteria"

    Examples:
      | name        | item_type   | collection_type | column_or_node |
      | Asset       | asset       | assets          | Assets         |
      | Facilitator | facilitator | facilitators    | Facilitators   |

  Scenario Outline: Confirm the custom item is available after event cancellation
    And I click on "Select <collection_type>" "link"
    And I click on "Create" "link"
    And I should see "Create new <item_type>" in the "Create new <item_type>" "totaradialogue"
    And I set the following fields to these values:
      | Name                     | <name> 1 |
      | description_editor[text] | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
    And I should not see "Add to sitewide list"
    When I click on "OK" "button" in the "Create new <item_type>" "totaradialogue"
    Then I should see "<name> 1"
    And I press "Save changes"
    And I should see the seminar event action "Cancel event" in row "Upcoming"

    When I click on the seminar event action "Cancel event" in row "Upcoming"
    Then I should see "Cancelling event in Test seminar name"
    And I press "Yes"

    And I follow "Add event"
    And I click on "Select <collection_type>" "link"
    And I click on "<name> 1" "link" in the "Choose <collection_type>" "totaradialogue"
    And I click on "OK" "button" in the "Choose <collection_type>" "totaradialogue"
    And I press "Save changes"
    And I click on "Go to event" "link" in the "Upcoming" "table_row"
    Then I should see "Sign-up" in the ".mod_facetoface__eventinfo__sidebar__signup" "css_element"

    Examples:
      | name        | item_type   | collection_type |
      | Asset       | asset       | assets          |
      | Facilitator | facilitator | facilitators    |

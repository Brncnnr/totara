@javascript @mod @mod_facetoface @totara @totara_customfield @totara_reportbuilder
Feature: Manage custom rooms by admin and non-admin user
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
      | name              | course |
      | Test seminar name | C1     |

  Scenario: Add edit seminar custom room as admin
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Add event" "link"

    # Create a custom room
    When I click on "Select rooms" "link"
    And I click on "Create" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    When I set the following fields to these values:
      | Name         | Room created    |
      | Building     | That house      |
      | Address      | 123 here street |
      | Capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Room created"
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I expand all fieldsets
    When I set the field "Sitewide" to "Yes"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "There are no records that match your selected criteria"
    And "facetoface_rooms" "table" should not exist
    When I set the field "Sitewide" to "No"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "There are no records that match your selected criteria"
    And the "facetoface_rooms" table should contain the following:
      | Name         | Building   | Location        | Capacity | Visible | Sitewide |
      | Room created | That house | 123 here street | 5        | Yes     | No       |

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on the seminar event action "Edit event" in row "#1"

    # Edit
    When I click on "Edit custom room Room created in session" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    When I set the following fields to these values:
      | Name         | Room edited |
      | Capacity     | 10          |
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room edited"
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I expand all fieldsets
    When I set the field "Sitewide" to "Yes"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "There are no records that match your selected criteria"
    And I should not see "Room created"
    And I should not see "Room edited"
    And "facetoface_rooms" "table" should not exist
    And I expand all fieldsets
    When I set the field "Sitewide" to "No"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "There are no records that match your selected criteria"
    And I should not see "Room created"
    And the "facetoface_rooms" table should contain the following:
      | Name        | Building   | Location        | Capacity | Visible | Sitewide |
      | Room edited | That house | 123 here street | 10       | Yes     | No       |

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on the seminar event action "Edit event" in row "#1"

    # Publish a custom room i.e. make it a site-wide room
    When I click on "Edit custom room Room edited in session" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    When I set the following fields to these values:
      | Name         | Room published |
      | Capacity     | 15             |
    And I set the field "notcustom" to "1"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room published"
    When I should not see "Edit custom room Room published in session" in the "Room published" "table_row"
    # No need to submit a form here; the room is published as soon as the totaradialogue is closed

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I expand all fieldsets
    When I set the field "Sitewide" to "Yes"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "There are no records that match your selected criteria"
    And I should not see "Room created"
    And I should not see "Room edited"
    And the "facetoface_rooms" table should contain the following:
      | Name           | Building   | Location        | Capacity | Visible | Sitewide |
      | Room published | That house | 123 here street | 15       | Yes     | Yes      |
    And I expand all fieldsets
    When I set the field "Sitewide" to "No"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "There are no records that match your selected criteria"
    And "facetoface_rooms" "table" should not exist

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Add event" "link"

    # Create a site-wide room
    When I click on "Select rooms" "link"
    And I click on "Create" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    When I set the following fields to these values:
      | Name         | Site-wide room      |
      | Building     | This building       |
      | Address      | 456 there boulevard |
      | Capacity     | 20                  |
    And I set the field "notcustom" to "1"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Site-wide room"
    And I should not see "Edit room" in the "Site-wide room" "table_row"
    # No need to submit a form here; the room is published as soon as the totaradialogue is closed

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I expand all fieldsets
    When I set the field "Sitewide" to "Yes"
    And I press "id_submitgroupstandard_addfilter"
    Then I should not see "There are no records that match your selected criteria"
    And I should not see "Room created"
    And I should not see "Room edited"
    And the "facetoface_rooms" table should contain the following:
      | Name           | Building      | Location            | Capacity | Visible | Sitewide |
      | Site-wide room | This building | 456 there boulevard | 20       | Yes     | Yes      |

  Scenario: Add edit seminar custom room as editing teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "Add event" "link"
    When I click on "Select rooms" "link"
    And I click on "Create" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    And I should not see "Add to sitewide list"
    When I set the following fields to these values:
      | Name         | Room 1          |
      | Building     | That house      |
      | Address      | 123 here street |
      | Capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Room 1"

    # Edit
    When I click on "Edit custom room Room 1 in session" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    And I should not see "Add to sitewide list"
    When I set the following fields to these values:
      | Name         | Room edited |
      | Capacity     | 10          |
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room edited"

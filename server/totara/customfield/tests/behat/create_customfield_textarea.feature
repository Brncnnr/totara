@totara @totara_customfield @javascript
Feature: Administrators can add a custom text area field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the text area custom field and add the relevant settings

  Scenario: Create a custom text area
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Text area"
    Then I should see "Editing custom field: Text area"

    When I set the following fields to these values:
      | fullname                 | Custom Text Area Field |
      | shortname                | textarea               |
      | param1                   | 15                     |
      | param2                   | 4                      |
      | defaultdata_editor[text] | Some default text      |
    And I press "Save changes"
    Then I should see "Custom Text Area Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Text Area Field"
    And the field "customfield_textarea_editor[text]" matches value "Some default text"
    And the "cols" attribute of "customfield_textarea_editor[text]" "field" should contain "15"
    And the "rows" attribute of "customfield_textarea_editor[text]" "field" should contain "4"

    When I set the following fields to these values:
      | fullname                          | Course One                    |
      | shortname                         | course1                       |
      | customfield_textarea_editor[text] | Different words in this field |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textarea_editor[text]" matches value "Different words in this field"

    When I set the field "customfield_textarea_editor[text]" to "%Some 0ther: ch@racters now.,;#"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the field "customfield_textarea_editor[text]" matches value "%Some 0ther: ch@racters now.,;#"
    And I log out

  Scenario: Language filter should work on textarea custom field
    And I log in as "admin"
    # Enabling multi-language filters for headings and content.
    And I navigate to "Manage filters" node in "Site administration > Plugins > Filters"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='activemultilang']//select[@name='newstate']" to "1"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='applytomultilang']//select[@name='stringstoo']" to "1"

      # Create new customfield with multilang
    When I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Room" "link"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Text area"
    When I set the following fields to these values:
      | fullname                 | Custom Text Area Field |
      | shortname                | textarea               |
      | param1                   | 15                     |
      | param2                   | 4                      |
    And I press "Save changes"
    Then I should see "Custom Text Area Field"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                    | Room 1          |
      | Building                | That house      |
      | Address                 | Address         |
      | Capacity           | 5               |
      | Custom Text Area Field  | <span lang="de" class="multilang">German text</span><span lang="en" class="multilang">English text</span>|
    And I press "Add a room"

    Given I press "Edit this report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "Custom Text Area Field"
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"

    Then I should see "That house" in the "Room 1" "table_row"
    And I should see "English text"
    And I should not see "German text"
    When I click on "Room 1" "link"
    And I should see "English text"
    And I should not see "German text"

    Then I log out


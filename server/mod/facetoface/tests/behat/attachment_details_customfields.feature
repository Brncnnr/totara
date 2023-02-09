@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Check asset/facilitator details with all possible custom fields
  In order to test asset/facilitator details page
  As a site manager
  I need to create an event and asset/facilitator, add custom fields, login as admin and check asset/facilitator details page

  Scenario Outline: View item details page and check all custom fields are properly displayed.
    Given I am on a totara site
    And I log in as "admin"

    # Add images to the private files block to use later
    When I am on "Dashboard" page
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "mod/facetoface/tests/fixtures/test.jpg" file to "Files" filemanager
    And I upload "mod/facetoface/tests/fixtures/leaves-green.png" file to "Files" filemanager
    Then I should see "test.jpg"
    And I should see "leaves-green.png"

    When I navigate to "Custom fields" node in "Site administration > Seminars"
    And I switch to "<name>" tab

    And I set the field "datatype" to "Checkbox"
    And I set the following fields to these values:
      | Full name                   | <name> checkbox |
      | Short name (must be unique) | checkbox        |
    And I press "Save changes"

    And I set the field "datatype" to "Date/time"
    And I set the following fields to these values:
      | Full name                   | <name> date/time |
      | Short name (must be unique) | datetime         |
      | Include time?               | 1                |
    And I press "Save changes"

    And I set the field "datatype" to "File"
    And I set the following fields to these values:
      | Full name                   | <name> file |
      | Short name (must be unique) | file        |
    And I press "Save changes"

    And I set the field "datatype" to "Location"
    And I set the following fields to these values:
      | Full name                   | <name> location |
      | Short name (must be unique) | location        |
    And I press "Save changes"

    And I set the field "datatype" to "Menu of choices"
    And I set the following fields to these values:
      | Full name                   | <name> menu of choices |
      | Short name (must be unique) | menuofchoices          |
    And I set the field "Menu options (one per line)" to multiline:
      """
      Apple
      Orange
      Banana
      """
    And I press "Save changes"

    And I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | Full name                   | <name> multi select |
      | Short name (must be unique) | multiselect         |
      | multiselectitem[0][option]  | Tui                 |
      | multiselectitem[1][option]  | Moa                 |
      | multiselectitem[2][option]  | Tuatara             |
    And I press "Save changes"

    And I set the field "datatype" to "Text area"
    And I set the following fields to these values:
      | Full name                   | <name> text area |
      | Short name (must be unique) | textarea         |
    And I press "Save changes"

    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | Full name                   | <name> text input |
      | Short name (must be unique) | textinput         |
    And I press "Save changes"

    And I set the field "datatype" to "URL"
    And I set the following fields to these values:
      | Full name                   | <name> address |
      | Short name (must be unique) | url            |
    And I press "Save changes"

    Then I should see "<name> checkbox"
    And I should see "<name> date/time"
    And I should see "<name> file"
    And I should see "<name> location"
    And I should see "<name> menu of choices"
    And I should see "<name> multi select"
    And I should see "<name> text area"
    And I should see "<name> text input"
    And I should see "<name> address"

    # Create an item
    # TL-23000 made impossible to create a facilitator with the same steps as an asset
    Given the following "global <collection_type>" exist in "mod_facetoface" plugin:
      | name                        |
      | <name>_created_by_generator |
    When I navigate to "<column_or_node>" node in "Site administration > Seminars"
    And I click on "Edit <item_type>" "link" in the "<name>_created_by_generator" "table_row"
    # Set the basic fields.
    And I set the following fields to these values:
      | Name                          | <name> 1 |
      | <name> checkbox               | 1 |
      | <name> menu of choices        | Orange |
      | <name> text area              | Lorem ipsum dolor sit amet, consectetur adipisicing elit |
      | <name> text input             | Duis aute irure dolor in reprehenderit in voluptate |
      | customfield_datetime[enabled] | 1 |
      | customfield_datetime[day]     | 2 |
      | customfield_datetime[month]   | 3 |
      | customfield_datetime[year]    | ## next year ## Y ## |
      | customfield_datetime[hour]    | 10 |
      | customfield_datetime[minute]  | 30 |
      | customfield_datetime[enabled] | 1 |
      | customfield_multiselect[2]    | 1 |
      | customfield_url[url]          | http://totaralearning.com |
      | customfield_url[text]         | Totara LMS |
    # Set a location.
    And I set the field "Address" to multiline:
      """
      Level 8, Totara
      Catalyst House
      150 Willis street
      Te Aro
      Wellington 6011
      """
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_both" "css_element"

    # Add a file to the file custom field.
    And I click on "//div[@id='fitem_id_customfield_file_filemanager']//a[@title='Add...']" "xpath_element"
    And I click on "test.jpg" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"

    # Image in the textarea custom field
    And I click on "//button[@class='atto_image_button']" "xpath_element" in the "//div[@id='fitem_id_customfield_textarea_editor']" "xpath_element"
    And I click on "Browse repositories..." "button"
    And I click on "leaves-green.png" "link" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I click on "Select this file" "button" in the "//div[@aria-hidden='false' and @class='moodle-dialogue-base']" "xpath_element"
    And I set the field "Describe this image for someone who cannot see it" to "Green leaves on customfield text area"
    And I click on "Save image" "button"
    # Create the item.
    And I press "Save changes"

    # Verify that the item was created correctly.
    When I click on "<name> 1" "link" in the "facetoface_<collection_type>" "table"
    Then I should see "<name> 1"
    And I should see "150 Willis street"
    # "Yes" for checkbox
    And I should see "Yes"
    And I should see date "2 March next year 10:30 AM" formatted "%A, %d %B %Y, %I:%M %p"
    And I should see "test.jpg"
    And I should see "Orange"
    And I should see "Tuatara"
    And I should see "Lorem ipsum dolor sit amet, consectetur adipisicing elit"
    And I should see "Duis aute irure dolor in reprehenderit in voluptate"
    And I should see "Totara LMS"
    And I should see the "Green leaves on customfield text area" image in the "//dd[preceding-sibling::dt[1][. = '<name> text area']]" "xpath_element"
    And I should see image with alt text "Green leaves on customfield text area"

    Examples:
      | name        | item_type   | collection_type | an_item_type  | column_or_node |
      | Asset       | asset       | assets          | an asset      | Assets         |
      | Facilitator | facilitator | facilitators    | a facilitator | Facilitators   |

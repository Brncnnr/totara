@totara @editor @editor_weka @weka @javascript @totara_mobile @vuejs
Feature: Convert from HTML to Weka editor test
  As an admin
  I convert HTML to mobile-friendly JSON content in necessary places

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category | summaryformat | summary                                      |
      | course1  | course1   | 0        | 1             | <p>Hello <a href="#test">World</a> Test.</p> |
    And the following "programs" exist in "totara_program" plugin:
      | fullname | shortname | category | summary                                      | endnote                                            |
      | program1 | program1  | 0        | <p>Hello <a href="#test">World</a> Test.</p> | <p>Goodbye <a href="#test">Endnote</a>, Later.</p> |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | category | summary                                      | endnote                                            |
      | cert1    | cert1     | 0        | <p>Hello <a href="#test">World</a> Test.</p> | <p>Goodbye <a href="#test">Endnote</a>, Later.</p> |
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Details content | 1 |
    And I click on "Save" "button"
    And I navigate to "Courses > Configure catalogue" in site administration
    And I wait for pending js
    When I follow "Details"
    And I set the following Totara form fields to these values:
      | rich_text__course                               | Summary         |
      | rich_text__program                              | Summary         |
      | rich_text__certification                        | Summary         |
    And I click on "Save" "button"

  Scenario: Convert course summary, update it, and test that JSON is rendered as HTML
    When I am on "course1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save and display" "button"
    Then I should see "Please check mobile-friendly content and save when ready"
    And I activate the weka editor with css "#uid-1"
    And I select the text "Test" in the weka editor
    And I replace the selection with "Update" in the weka editor
    And I click on "Save and display" "button"
    Then I should not see "Edit course settings"
    And I click on "Find Learning" in the totara menu
    And I click on "course1" "text"
    Then I should see "Hello World Update"
    And I click on "World" "link"

  Scenario: Convert program summary and endnote, update them, and test that JSON is rendered as HTML
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "program1" "link"
    And I click on "Edit program details" "button"
    And I click on "Details" "link"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I set the field with xpath "//select[@name='endnote_editor[format]']" to "5"
    And I click on "Save changes" "button"
    Then I should see "Please check mobile-friendly content and save when ready" exactly "2" times
    And I activate the weka editor with css "#uid-1"
    And I select the text "Test" in the weka editor
    And I replace the selection with "Update" in the weka editor
    And I activate the weka editor with css "#uid-2"
    And I select the text "Later" in the weka editor
    And I replace the selection with "See Ya" in the weka editor
    And I click on "Save changes" "button"
    Then I should not see "Define the program name, availability and description"
    And I should see "Hello World Update"
    And I click on "World" "link"
    Then I should see "Goodbye Endnote, See Ya"
    And I click on "Endnote" "link"
    And I click on "Find Learning" in the totara menu
    And I click on "program1" "text"
    Then I should see "Hello World Update"
    And I click on "World" "link"
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "program1" "link"
    Then I should see "Hello World Update"
    And I click on "World" "link"

  Scenario: Convert label, update it, and test that JSON is rendered as HTML
    When I am on "course1" course homepage with editing mode on
    When I add a "label" to section "1" and I fill the form with:
      | Label text | <p>Hello <a href="#test">World</a> Test.</p> |
    And I open "Hello World Test" actions menu
    And I click on "Edit settings" "link" in the "Hello World Test" activity
    And I set the field with xpath "//select[@name='introeditor[format]']" to "5"
    And I click on "Save and return to course" "button"
    Then I should see "Please check mobile-friendly content and save when ready"
    And I activate the weka editor with css "#uid-1"
    And I select the text "Test" in the weka editor
    And I replace the selection with "Update" in the weka editor
    And I click on "Save and return to course" "button"
    Then I should not see "Updating Label in Topic 1"
    And I should see "Hello World Update"
    And I click on "World" "link"

  Scenario: Convert topic, update it, and test that JSON is rendered as HTML
    When I am on "course1" course homepage with editing mode on
    And I edit the section "1" and I fill the form with:
      | Summary | <p>Hello <a href="#test">World</a> Test.</p> |
    And I should see "Hello World Test"
    And I edit the section "1"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save changes" "button"
    Then I should see "Please check mobile-friendly content and save when ready"
    And I activate the weka editor with css "#uid-1"
    And I select the text "Test" in the weka editor
    And I replace the selection with "Update" in the weka editor
    And I click on "Save changes" "button"
    Then I should not see "Summary of Topic 1"
    And I should see "Hello World Update"
    And I click on "World" "link"

  Scenario: Convert course summary without updating it and test that JSON is rendered as HTML
    When I am on "course1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field with xpath "//select[@name='summary_editor[format]']" to "5"
    And I click on "Save and display" "button"
    Then I should see "Please check mobile-friendly content and save when ready"
    And I click on "Save and display" "button"
    Then I should not see "Edit course settings"
    And I click on "Find Learning" in the totara menu
    And I click on "course1" "text"
    Then I should see "Hello World Test"
    And I click on "World" "link"

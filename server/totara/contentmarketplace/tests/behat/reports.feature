@totara @totara_contentmarketplace @mod_contentmarketplace @contentmarketplace_linkedin @totara_reportbuilder @javascript
Feature: Check that the content provider column and filter in reports work as expected

  Background:
    Given I set up the "goone" content marketplace plugin
    And the following "courses" exist:
      | fullname        | shortname |
      | Internal Course | c1        |
      | LinkedIn Course | c2        |
      | Go1 Course      | c3        |
      | Multi Course    | c4        |
    And the following "content marketplace" exist in "mod_contentmarketplace" plugin:
      | name     | course | marketplace_component       |
      | urn:1234 | c2     | contentmarketplace_linkedin |
      | urn:5678 | c4     | contentmarketplace_linkedin |
      | 29271    | c3     | contentmarketplace_goone    |
      | 1868492  | c4     | contentmarketplace_goone    |
    And the following "activities" exist:
      | activity | course | idnumber |
      | assign   | c4     | a5       |
      | assign   | c4     | a6       |

  Scenario: Content provider column and filter test
    Given the "linkedin" content marketplace plugin is disabled
    And the "goone" content marketplace plugin is disabled
    When I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "course"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "Settings" "link" in the "Find Courses" "table_row"
    And I switch to "Columns" tab
    Then I should not see "Content provider" in the "Column" "select"
    When I switch to "Filters" tab
    Then I should not see "Content provider" in the "field" "select"

    # Enable the linkedin & go1 content marketplaces and add the columns and filters
    Given the "linkedin" content marketplace plugin is enabled
    And the "goone" content marketplace plugin is enabled
    When I switch to "Columns" tab
    And I add the "Content provider" column to the report
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "Content provider"
    And I click on "Save changes" "button"
    And I follow "View This Report"

    # Check the values of the content provider column.
    Then I should see "4 records shown"
    And I should see "Internal" in the "course_provider" report column for "Internal Course"
    And I should not see "External content marketplace" in the "course_mods" report column for "Internal Course"
    And I should see "LinkedIn Learning" in the "course_provider" report column for "LinkedIn Course"
    And I should not see "Internal" in the "course_provider" report column for "LinkedIn Course"
    And I should see "External content marketplace" in the "course_mods" report column for "LinkedIn Course"
    And I should see "Go1" in the "course_provider" report column for "Go1 Course"
    And I should not see "Internal" in the "course_provider" report column for "Go1 Course"
    And I should see "Internal, Go1, LinkedIn Learning" in the "course_provider" report column for "Multi Course"
    And I should not see "Internal, Internal" in the "course_provider" report column for "Multi Course"

    # Check the results of the content provider filter.
    When I click on "Internal" "checkbox"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2 records shown"
    And I should see "Internal Course"
    And I should see "Multi Course"
    And I should not see "LinkedIn Course"
    And I should not see "Go1 Course"
    When I click on "LinkedIn Learning" "checkbox"
    And I click on "Internal" "checkbox"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2 records shown"
    And I should not see "Internal Course"
    And I should see "LinkedIn Course"
    And I should not see "Go1 Course"
    And I should see "Multi Course"

    # Disabling the plugin should hide the provider columns and filters
    When the "linkedin" content marketplace plugin is disabled
    And the "goone" content marketplace plugin is disabled
    And I reload the page
    Then I should not see "Content provider"

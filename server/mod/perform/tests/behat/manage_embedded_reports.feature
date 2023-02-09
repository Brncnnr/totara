@totara @perform @mod_perform @javascript @vuejs
Feature: Test performance activity embedded reports

  Background:
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | description      | activity_type |
      | My Activity   | My Test Activity | feedback      |

  Scenario: I can not see performance activity embedded report without ids
    Given I log in as "admin"

    And I navigate to the manage perform activities page
    And I click on "Participation reporting" "link"
    And I should see "My Activity"

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "subject instances"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "View" "link" in the "Performance Subject Instance" "table_row"
    Then I should see "This report can only be accessed from the performance reporting interface - go to the performance reporting page, and select some data to report on."

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "participant instances"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "View" "link" in the "Performance Participant Instance" "table_row"
    Then I should see "This report page can only show details for a single subject instance at a time - to select which subject instance's report to view, go to Manage performance activities, click on the reporting icon of the activity to which the subject instance belongs. From the report's list of subject instances, navigate to the relevant one's participant instance report by clicking on its participant count."

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "subject instances"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "View" "link" in the "Performance Subject Instance Manage Participation" "table_row"
    Then I should see "This report page can only show details for a single activity at a time - to select which activity's report to view, go to Manage performance activities, and click on the relevant activity's reporting icon."

    When I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "Report Name value" to "participant instances"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I click on "View" "link" in the "Performance Participant Instance Manage Participation" "table_row"
    Then I should see "This report page can only show details for a single activity at a time - to select which activity's report to view, go to Manage performance activities, and click on the relevant activity's reporting icon."
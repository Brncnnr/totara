@totara @perform @mod_perform @totara_reportbuilder @javascript
Feature: Test view and filter performance response data report with Section title by activity filter

  Background:
    Given the following "users" exist:
      | username          | firstname | lastname | email                              |
      | john              | John      | One      | john.one@example.com               |
      | david             | David     | Two      | david.two@example.com              |
      | harry             | Harry     | Three    | harry.three@example.com            |
      | manager-appraiser | combined  | Three    | manager-appraiser.four@example.com |
    And the following job assignments exist:
      | user | manager           | appraiser         |
      | john | manager-appraiser | manager-appraiser |
      | john | david             | harry             |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name                                   | activity_type | create_section | create_track | activity_status | anonymous_responses |
      | Anonymous responses - Multiple section Activity | appraisal     | false          | false        | Active          | true                |
      | Multiple section Activity                       | appraisal     | false          | false        | Active          | false               |
    And the following "activity settings" exist in "mod_perform" plugin:
      | activity_name                                   | close_on_completion | multisection |
      | Anonymous responses - Multiple section Activity | yes                 | yes          |
      | Multiple section Activity                       | yes                 | yes          |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name                                   | section_name   |
      | Anonymous responses - Multiple section Activity | Section anon 1 |
      | Anonymous responses - Multiple section Activity | Section anon 2 |
      | Anonymous responses - Multiple section Activity | Section anon 3 |
      | Multiple section Activity                       | Section 1      |
      | Multiple section Activity                       | Section 2      |
      | Multiple section Activity                       | Section 3      |
    And the following "cohorts" exist:
      | name | idnumber | description | contextlevel | reference | cohorttype |
      | aud1 | aud1     | Audience 1  | System       | 0         | 1          |
    And the following "cohort members" exist:
      | user  | cohort |
      | john  | aud1   |
      | david | aud1   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name                                   | track_description | due_date_offset |
      | Anonymous responses - Multiple section Activity | track anon 1      | 1, DAY          |
      | Multiple section Activity                       | track 1           | 1, DAY          |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |
      | track anon 1      | cohort          | aud1            |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name   | relationship |
      | Section anon 1 | manager      |
      | Section anon 1 | appraiser    |
      | Section anon 2 | manager      |
      | Section anon 3 | subject      |
      | Section 1      | subject      |
      | Section 1      | manager      |
      | Section 1      | appraiser    |
      | Section 2      | manager      |
      | Section 3      | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name   | element_name | title      |
      | Section anon 1 | short_text   | Question 1 |
      | Section anon 2 | short_text   | Question 2 |
      | Section anon 3 | short_text   | Question 3 |
      | Section 1      | short_text   | Question 1 |
      | Section 2      | short_text   | Question 2 |
      | Section 3      | short_text   | Question 3 |
    And I run the scheduled task "mod_perform\task\expand_assignments_task"
    And I run the scheduled task "mod_perform\task\create_subject_instance_task"

  Scenario: I can view response data report and filter with Section title by activity filter
    Given I log in as "admin"

    # First check the optional questions activity.
    When I navigate to the mod perform response data report for "Anonymous responses - Multiple section Activity" activity
    Then the following should exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 1 |
      | Section anon 2 |
      | Section anon 3 |
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Section title |
      | Section 1     |
      | Section 2     |
      | Section 3     |

    When I set the field "section-id_op" to "is equal to"
    And I click on "Choose section titles" "button"
    And I click on "Section anon 1" "link" in the "Choose section titles" "totaradialogue"
    And I click on "Save" "button" in the "Choose section titles" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 1 |
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 2 |
      | Section anon 3 |

    When I click on "Choose section titles" "button"
    And I click on "Section anon 3" "link" in the "Choose section titles" "totaradialogue"
    And I click on "Save" "button" in the "Choose section titles" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 1 |
      | Section anon 3 |
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 2 |

    When I set the field "section-id_op" to "isn't equal to"
    Then the following should not exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 1 |
      | Section anon 3 |
    And the following should exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 2 |

    When I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "perform_response_element_by_activity" table:
      | Section title  |
      | Section anon 1 |
      | Section anon 2 |
      | Section anon 3 |
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Section title |
      | Section 1     |
      | Section 2     |
      | Section 3     |
@totara @perform @mod_perform @perform_element @totara_reportbuilder @javascript
Feature: Test view and filter performance response data report

  Background:
    Given the following "users" exist:
      | username    | firstname   | lastname | email                   |
      | user1       | User1       | Last1    | user1@example.com       |
      | user2       | User2       | Last2    | user2@example.com       |
      | user3       | User3       | Last3    | user3@example.com       |
      | user4       | User4       | Last4    | user4@example.com       |
      | user5       | User5       | Last5    | user5@example.com       |
      | manager     | manager     | user     | manager.one@example.com |
      | sitemanager | sitemanager | user     | sitemanager@example.com |
    And the following "role assigns" exist:
      | user        | role    | contextlevel | reference |
      | sitemanager | manager | System       |           |
    And the following job assignments exist:
      | user  | manager | appraiser |
      | user1 | manager |           |
      | user2 | manager |           |
      | user3 |         | manager   |
    And the following "permission overrides" exist:
      | capability                                   | permission | role         | contextlevel | reference |
      | mod/perform:report_on_subject_responses      | Allow      | staffmanager | System       |           |
      | mod/perform:report_on_all_subjects_responses | Allow      | manager      | System       |           |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name                      | subject_username | subject_is_participating | include_questions | include_required_questions | activity_status |
      | Simple optional questions activity | user1            | true                     | true              |                            | Active          |
      | Simple required questions activity | user1            | true                     | true              | true                       | Active          |
      | Simple activity                    | user2            | true                     | true              | true                       | Active          |
      | Simple activity                    | user4            | true                     | true              | true                       | Active          |

  Scenario: I can view response data report and filter with Element type filter
    Given I log in as "manager"

    # First check the optional questions activity.
    When I navigate to the mod perform response data report for "Simple optional questions activity" activity
    Then I should see "2" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    And the following should exist in the "perform_response_element_by_activity" table:
      | Question text  | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one   | Part one      | Text: Short response | 1                        | No       |              |
      | Question two   | Part one      | Text: Short response | 1                        | No       |              |

    When I set the field "element-type_op" to "is equal to"
    And I click on "Choose element types" "button"
    And I click on "Text: Long response" "link" in the "Choose element types" "totaradialogue"
    And I click on "Save" "button" in the "Choose element types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "There are no records that match your selected criteria"
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Question text  | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one   | Part one      | Text: Short response | 1                        | No       |              |
      | Question two   | Part one      | Text: Short response | 1                        | No       |              |

    When I click on "Choose element types" "button"
    And I click on "Text: Short response" "link" in the "Choose element types" "totaradialogue"
    And I click on "Save" "button" in the "Choose element types" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"

    Then I should see "2" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    And the following should exist in the "perform_response_element_by_activity" table:
      | Question text  | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one   | Part one      | Text: Short response | 1                        | No       |              |
      | Question two   | Part one      | Text: Short response | 1                        | No       |              |

    When I set the field "element-type_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "There are no records that match your selected criteria"
    And the following should not exist in the "perform_response_element_by_activity" table:
      | Question text  | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one   | Part one      | Text: Short response | 1                        | No       |              |
      | Question two   | Part one      | Text: Short response | 1                        | No       |              |

    When I set the field "element-type_op" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "2" in the ".tui-elementResponseReportingActionCard__record-count" "css_element"
    And the following should exist in the "perform_response_element_by_activity" table:
      | Question text  | Section title | Element type         | Responding relationships | Required | Reporting ID |
      | Question one   | Part one      | Text: Short response | 1                        | No       |              |
      | Question two   | Part one      | Text: Short response | 1                        | No       |              |

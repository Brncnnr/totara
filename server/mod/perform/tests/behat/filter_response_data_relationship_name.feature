@totara @perform @mod_perform @totara_reportbuilder @javascript
Feature: Test view and filter performance response data report with relationship name filter

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email                  |
      | user1     | User      | One      | user.one@example.com   |
      | user2     | User      | Two      | user.two@example.com   |
      | user3     | User      | Three    | user.three@example.com |
      | user4     | User      | Four     | user.four@example.com  |
      | user5     | User      | Five     | user.five@example.com  |
      | user6     | User      | Six      | user.six@example.com   |
      | manager1  | manager   | One      | manager1@example.com   |
      | manager2  | manager   | Two      | manager2@example.com   |
      | appraiser | appraiser | User     | appraiser@example.com  |
      | other     | other     | User     | other@example.com      |
    And the following job assignments exist:
      | user     | idnumber | manager  | managerjaidnumber | appraiser |
      | manager1 | manage1  |          |                   |           |
      | manager1 | manage2  |          |                   |           |
      | manager2 | manage   |          |                   |           |
      | user1    | job      |          |                   |           |
      | user2    | job      | manager1 | manage1           |           |
      | user3    | job      |          |                   | appraiser |
      | user4    | job      | manager1 | manage1           | appraiser |
      | user5    | job      | manager1 | manage2           |           |
      | user5    | job      | manager2 | manage            |           |
      | user6    | job      | manager1 | manage1           | appraiser |

    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name          | activity_status | subject_username | subject_is_participating | other_participant_username | third_participant_username | number_repeated_instances | relationships_can_answer |
      | 3 participants         | 1               | user1            | true                     |                            |                            | 3                         |                          |
      | 3 participants         | 1               | user2            | true                     | manager1                   |                            | 3                         |                          |
      | 3 participants         | 1               | user3            | true                     | appraiser                  |                            | 3                         |                          |
      | 3 participants         | 1               | user4            | true                     | manager1                   | appraiser                  | 3                         |                          |
      | for manager1           | 1               | user2            | false                    | manager1                   |                            | 1                         |                          |
      | for manager1           | 1               | user3            | false                    | manager1                   |                            | 1                         |                          |
      | for manager1           | 1               | user4            | false                    | manager1                   |                            | 1                         |                          |
      | for manager1           | 1               | user5            | false                    | manager1                   |                            | 1                         |                          |
      | for manager2 appraiser | 1               | user1            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user2            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user3            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user4            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user5            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | view only appraiser    | 1               | user6            | true                     | manager1                   | appraiser                  | 1                         | subject, manager         |

  Scenario: I can view response data report and filter with Relationship name filter
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    And I set the field with xpath "//input[@id='search_input']" to "Performance Participant Instance"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I wait for pending js
    And I click on "Performance Participant Instance" "text"
    And I click on "Create and view" "button"

    And the following should exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Subject           |
      | Manager           |
      | Appraiser         |

    When I set the field "participant_instance-relationship_id_op" to "is equal to"
    And I click on "Choose relationship names" "button"
    And I click on "Subject" "link" in the "Choose relationship names" "totaradialogue"
    And I click on "Save" "button" in the "Choose relationship names" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Subject           |
    And the following should not exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Manager           |
      | Appraiser         |
    And I click on "Choose relationship names" "button"
    And I click on "Manager" "link" in the "Choose relationship names" "totaradialogue"
    And I click on "Save" "button" in the "Choose relationship names" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Subject           |
      | Manager           |
    And the following should not exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Appraiser         |

    And I click on "Clear" "button" in the ".fitem_actionbuttons" "css_element"

    When I set the field "participant_instance-relationship_id_op" to "is equal to"
    And I click on "Choose relationship names" "button"
    And I click on "Peer" "link" in the "Choose relationship names" "totaradialogue"
    And I click on "Save" "button" in the "Choose relationship names" "totaradialogue"
    And I wait until ".fitem_actionbuttons" "css_element" exists
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should not exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Subject           |
      | Manager           |
      | Appraiser         |

    When I set the field "participant_instance-relationship_id_op" to "isn't equal to"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "rb_source_perform_participation_participant_instance" table:
      | Relationship name |
      | Subject           |
      | Manager           |
      | Appraiser         |

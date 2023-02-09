@totara @perform @mod_perform @javascript @vuejs
Feature: Test management of activity participation as a manager

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
    And the following "permission overrides" exist:
      | capability                                    | permission | role         | contextlevel | reference |
      | mod/perform:manage_subject_user_participation | Allow      | staffmanager | System       |           |
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
      | for manager1           | 1               | user2            | true                     | user1                      |                            | 1                         | subject, manager         |
      | for manager1           | 1               | user3            | true                     | user1                      |                            | 1                         | subject, manager         |
      | for manager1           | 1               | user4            | true                     | user1                      |                            | 1                         | subject, manager         |
      | for manager1           | 1               | user5            | false                    | user1                      |                            | 1                         | subject, manager         |
      | for manager2 appraiser | 1               | user1            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user3            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | for manager2 appraiser | 1               | user5            | false                    | manager2                   | appraiser                  | 2                         |                          |
      | view only appraiser    | 1               | user6            | true                     | manager1                   | appraiser                  | 1                         | subject, manager         |

  Scenario: Manage participant tables contain the correct rows
    Given I log in as "manager1"
    And I navigate to the outstanding perform activities list page
    And I click on "Manage participation" "link_or_button"
    Then I should see "Select activity"
    And the following fields match these values:
      | manage-participation-activity-select | 3 participants |
    And the "manage-participation-activity-select" select box should contain "3 participants"
    And the "manage-participation-activity-select" select box should contain "for manager1"
    And the "manage-participation-activity-select" select box should contain "view only appraiser"
    And the "manage-participation-activity-select" select box should not contain "for manager2 appraiser"

    When I click on "Continue" "link"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Four    | 3               | 3            |
      | User Four    | 2               | 3            |
      | User Four    | 1               | 3            |
      | User Two     | 3               | 2            |
      | User Two     | 2               | 2            |
      | User Two     | 1               | 2            |

    When I navigate to the outstanding perform activities list page
    And I click on "Manage participation" "link_or_button"
    And I select "for manager1" from the "manage-participation-activity-select" singleselect
    And I click on "Continue" "link"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Four    | 1               | 2            |
      | User Two     | 1               | 2            |

    When I navigate to the outstanding perform activities list page
    And I click on "Manage participation" "link_or_button"
    And I select "view only appraiser" from the "manage-participation-activity-select" singleselect
    And I click on "Continue" "link"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Six     | 1               | 3            |


    When I click on "3 instances" "link" in the "User Six" "table_row"
    Then I should see "view only appraiser - Participant instances"
    And the following should exist in the "perform_manage_participation_participant_instance" table:
      | Participant name | Subject name | Relationship name |
      | appraiser User   | User Six     | Appraiser         |
      | manager One      | User Six     | Manager           |
      | User Six         | User Six     | Subject           |

    When I click on "1 section" "link" in the "appraiser User" "table_row"
    Then I should see "view only appraiser - Participant sections"
    And the following should exist in the "perform_manage_participation_participant_section" table:
      | Participant's name | Section title | Subject name | Relationship name |
      | appraiser User     | Part one      | User Six     | Appraiser         |

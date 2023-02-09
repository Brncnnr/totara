@totara @perform @mod_perform @javascript @vuejs
Feature: Test management of activity participation

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

  Scenario: Can close all instances
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Availability | Progress    |
      | User Four           | Open         | Not started |
      | User Four           | Open         | Not started |
      | User Four           | Open         | Not started |
      | User One            | Open         | Not started |
      | User One            | Open         | Not started |
      | User One            | Open         | Not started |
      | User Three          | Open         | Not started |
      | User Three          | Open         | Not started |
      | User Three          | Open         | Not started |
      | User Two            | Open         | Not started |
      | User Two            | Open         | Not started |
      | User Two            | Open         | Not started |

    When I click on "Close all instances" "button"
    And I should see "Close all instances" in the tui modal
    And I should see "This will close all the subject instances that are currently open to prevent any further submission of responses from all participants, regardless of their progress." in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "A task has been scheduled to close all instances." in the tui success notification toast

    When I run the adhoc scheduled tasks "mod_perform\task\close_activity_subject_instances_task"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Availability | Progress      |
      | User Four           | Closed       | Not submitted |
      | User Four           | Closed       | Not submitted |
      | User Four           | Closed       | Not submitted |
      | User One            | Closed       | Not submitted |
      | User One            | Closed       | Not submitted |
      | User One            | Closed       | Not submitted |
      | User Three          | Closed       | Not submitted |
      | User Three          | Closed       | Not submitted |
      | User Three          | Closed       | Not submitted |
      | User Two            | Closed       | Not submitted |
      | User Two            | Closed       | Not submitted |
      | User Two            | Closed       | Not submitted |

  Scenario: Manage participant tables contain the correct rows
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    Then I should see "3 participants"
    And I should see "for manager1"
    And I should see "for manager2 appraiser"

    When I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Four    | 3               | 3            |
      | User Four    | 2               | 3            |
      | User Four    | 1               | 3            |
      | User One     | 3               | 1            |
      | User One     | 2               | 1            |
      | User One     | 1               | 1            |
      | User Three   | 3               | 2            |
      | User Three   | 2               | 2            |
      | User Three   | 1               | 2            |
      | User Two     | 3               | 2            |
      | User Two     | 2               | 2            |
      | User Two     | 1               | 2            |

    When I click on "Back to all performance activities" "link"
    And I click on "Manage participation" "link" in the tui datatable row with "for manager1" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Five    | 1               | 1            |
      | User Four    | 1               | 1            |
      | User Three   | 1               | 1            |
      | User Two     | 1               | 1            |

    When I click on "Back to all performance activities" "link"
    And I click on "Manage participation" "link" in the tui datatable row with "for manager2 appraiser" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject name | Instance number | Participants |
      | User Five    | 1               | 2            |
      | User Five    | 2               | 2            |
      | User Four    | 1               | 2            |
      | User Four    | 2               | 2            |
      | User One     | 1               | 2            |
      | User One     | 2               | 2            |
      | User Three   | 1               | 2            |
      | User Three   | 2               | 2            |
      | User Two     | 1               | 2            |
      | User Two     | 2               | 2            |
    When I click on "2" "link" in the "User Three 99999997" "table_row"
    Then I should see "for manager2 appraiser - Participant instances"
    And the following should exist in the "perform_manage_participation_participant_instance" table:
      | Participant name | Subject name | Relationship name |
      | appraiser User   | User Three   | Appraiser         |
      | manager Two      | User Three   | Manager           |

    When I click on "Show all" "link"
    Then I should see "for manager2 appraiser - Participant instances"

    When I click on "Subject instances" "link"
    And I click on "2" "link" in the "User Three 99999997" "table_row"
    And I click on "1" "link" in the "appraiser User User Three 99999997" "table_row"
    Then I should see "for manager2 appraiser - Participant sections"
    And the following should exist in the "perform_manage_participation_participant_section" table:
      | Participant name | Section title | Subject name | Relationship name |
      | appraiser User   | Part one      | User Three   | Appraiser         |

    When I click on "Show all" "link"
    Then I should see "for manager2 appraiser - Participant sections"

  Scenario: open/close action on participant management reports
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Instance number | Participants |
      | User Four           | 1               | 3            |
      | User Four           | 2               | 3            |
      | User Four           | 3               | 3            |
      | User One            | 1               | 1            |
      | User One            | 2               | 1            |
      | User One            | 3               | 1            |
      | User Three          | 1               | 2            |
      | User Three          | 2               | 2            |
      | User Three          | 3               | 2            |
      | User Two            | 1               | 2            |
      | User Two            | 2               | 2            |
      | User Two            | 3               | 2            |
    #subject instance open/close action

    And I click on "Actions" "button" in the "User Three 99999997" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Close subject instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Subject instance and all its participant instances closed"
    And I click on "Actions" "button" in the "User Three 99999997" "table_row"
    When I click on "Reopen" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Reopen subject instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "Subject instance and all its participant instances reopened"
    #participant instance open/close action
    When I click on "Participant instances" "link"
    And I click on "Actions" "button" in the "appraiser User" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Close participant instance" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Participant instance (and any sections within) closed"
    And I click on "Actions" "button" in the "appraiser User" "table_row"
    And I click on "Reopen" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Reopen participant instance" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Participant instance (and any sections within) reopened"
    #participant section open/close action
    When I click on "Participant sections" "link"
    And I click on "Actions" "button" in the "appraiser User" "table_row"
    And I click on "Close" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Close participant section" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Participant section closed"
    And I click on "Actions" "button" in the "appraiser User" "table_row"
    And I click on "Reopen" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Reopen participant section" in the tui modal
    When I confirm the tui confirmation modal
    Then I should see "Participant section reopened"

  Scenario: open/close action not shown for view-only participants in participant management reports
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "view only appraiser" "Name"
    Then the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Instance number | Participants |
      | User Six            | 1               | 3 instances  |
    And I click on "Actions" "button" in the "User Six" "table_row"
    And "Close" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    And I switch to "Participant instances" tab
    Then the following should exist in the "perform_manage_participation_participant_instance" table:
    | Participant's name | Subject name | Relationship name | Sections  | Progress        | Availability   |
    | appraiser User     | User Six     | Appraiser         | 1 section | n/a (view only) | Not applicable |
    | manager One        | User Six     | Manager           | 1 section | Not started     | Open           |
    | User Six           | User Six     | Subject           | 1 section | Not started     | Open           |
    And I click on "Actions" "button" in the "appraiser User" "table_row"
    And "Close" "button" should not exist in the ".tui-dropdown__menu--open" "css_element"
    And I click on "Actions" "button" in the "manager One" "table_row"
    And "Close" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    # Not possible to uniquely identify the subject row - so not testing the close button for the subject

    When I switch to "Participant sections" tab
    Then the following should exist in the "perform_manage_participation_participant_section" table:
    | Participant's name | Section title | Subject name | Relationship name | Progress       | Availability   |
    | appraiser User     | Part one      | User Six     | Appraiser         | Not applicable | Not applicable |
    | manager One        | Part one      | User Six     | Manager           | Not started    | Open           |
    | User Six           | Part one      | User Six     | Subject           | Not started    | Open           |
    And "Actions" "button" should not exist in the "appraiser User" "table_row"
    And I click on "Actions" "button" in the "manager One" "table_row"
    And "Close" "button" should exist in the ".tui-dropdown__menu--open" "css_element"
    # Not possible to uniquely identify the subject row - so not testing the close button for the subject


  Scenario: Manage participants top level instance/section filtering
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"

    # Click on the participants count for user one, instance 3 (99999999 - 3 = 99999996), row 4
    When I click on "1" "link" in the "User One 99999996" "table_row"
    Then I should see "Showing results for 1 subject instance only"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should see "User One" in the "Subject full name" line of the perform activities instance info card
    And I should see "" in the "Job assignment" line of the perform activities instance info card
    And I should see "3" in the "Instance count" line of the perform activities instance info card
    And I should see "##today##j F Y##" in the "Creation date" line of the perform activities instance info card

    When I click on "Show all" "link"
    Then I should not see "Showing results for 1 subject instance only"
    And I should see "User Two"
    And I should see "User Three"

    When I click on "Subject instances" "link"
    And I click on "1" "link" in the "User One 99999996" "table_row"
    And I click on "1" "link" in the "User One User One 99999998" "table_row"
    Then I should see "Showing results for 1 participant instance only"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should see "User One" in the "Participant full name" line of the perform activities instance info card
    And I should see "User One" in the "Subject full name" line of the perform activities instance info card
    And I should see "Subject" in the "Relationship" line of the perform activities instance info card
    And I should see "##today##j F Y##" in the "Creation date" line of the perform activities instance info card

    When I click on "Show all" "link"
    Then I should not see "Showing results for 1 participant instance only"
    And I should see "User Two"
    And I should see "User Three"

  Scenario: manually delete participant in participant management reports
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "view only appraiser" "Name"
    And I switch to "Participant instances" tab
    Then the following should exist in the "perform_manage_participation_participant_instance" table:
      | Participant's name | Subject name | Relationship name | Sections  | Progress        | Availability   |
      | appraiser User     | User Six     | Appraiser         | 1 section | n/a (view only) | Not applicable |
      | manager One        | User Six     | Manager           | 1 section | Not started     | Open           |
      | User Six           | User Six     | Subject           | 1 section | Not started     | Open           |

    # Deleting normal participant.
    When I click on "Actions" "button" in the "manager One" "table_row"
    And I click on "Delete" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Delete participant instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "The participant instance and all associated records have been successfully deleted"
    And the following should not exist in the "perform_manage_participation_participant_instance" table:
      | Subject name |
      | manager One  |
    And the following should exist in the "perform_manage_participation_participant_instance" table:
      | Participant's name | Subject name | Relationship name | Sections  | Progress        | Availability   |
      | appraiser User     | User Six     | Appraiser         | 1 section | n/a (view only) | Not applicable |
      | User Six           | User Six     | Subject           | 1 section | Not started     | Open           |

    # Deleting view only participant.
    When I click on "Actions" "button" in the "appraiser User" "table_row"
    And I click on "Delete" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Delete participant instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "The participant instance and all associated records have been successfully deleted"
    And the following should not exist in the "perform_manage_participation_participant_instance" table:
      | Participant's name |
      | appraiser User     |
    And the following should exist in the "perform_manage_participation_participant_instance" table:
      | Participant's name | Subject name | Relationship name | Sections  | Progress        | Availability   |
      | User Six           | User Six     | Subject           | 1 section | Not started     | Open           |

  Scenario: manually delete subject instance
    Given I log in as "admin"
    And I navigate to the manage perform activities page
    And I click on "Manage participation" "link" in the tui datatable row with "3 participants" "Name"
    And the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Instance number | Participants |
      | User Four           | 1               | 3            |
      | User Four           | 2               | 3            |
      | User Four           | 3               | 3            |
      | User One            | 1               | 1            |
      | User One            | 2               | 1            |
      | User One            | 3               | 1            |
      | User Three          | 1               | 2            |
      | User Three          | 2               | 2            |
      | User Three          | 3               | 2            |
      | User Two            | 1               | 2            |
      | User Two            | 2               | 2            |
      | User Two            | 3               | 2            |
    And I click on "Actions" "button" in the "User Three 99999997" "table_row"
    When I click on "Delete" "button" in the ".tui-dropdown__menu--open" "css_element"
    Then I should see "Delete subject instance" in the tui modal
    And I confirm the tui confirmation modal
    Then I should see "The subject instance and all associated records have been successfully deleted"
    And the following should not exist in the "perform_manage_participation_subject_instance" table:
      | Subject name        |
      | User Three 99999997 |
    And the following should exist in the "perform_manage_participation_subject_instance" table:
      | Subject's full name | Instance number | Participants |
      | User Four           | 1               | 3            |
      | User Four           | 2               | 3            |
      | User Four           | 3               | 3            |
      | User One            | 1               | 1            |
      | User One            | 2               | 1            |
      | User One            | 3               | 1            |
      | User Three          | 1               | 2            |
      | User Three          | 2               | 2            |
      | User Two            | 1               | 2            |
      | User Two            | 2               | 2            |
      | User Two            | 3               | 2            |

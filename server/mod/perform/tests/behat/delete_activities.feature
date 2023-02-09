@totara @perform @mod_perform @javascript @vuejs
Feature: Deleting perform activities

  Background:
    Given I log in as "admin"

  Scenario: Deleting a draft activity
    # There is some issue with clicking the delete button in the drop down when it is not in the first row
    # that is why the activity setup is repeated with just the insertion order swapped.
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name   | description        | activity_type | activity_status |
      | Active activity | An active activity | feedback      | Active          |
      | Draft activity  | A draft activity   | feedback      | Draft           |

    When I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name            | Type     | Status |
      | Draft activity  | Feedback | Draft  |
      | Active activity | Feedback | Active |

    When I open the dropdown menu in the tui datatable row with "Draft activity" "Name"
    And I click on "Delete" "link"
    Then I should see "It will not affect assigned users" in the tui modal
    And I should see "Are you sure you would like to delete this activity?" in the tui modal

    When I click on "Delete" "button"
    Then I should see "Draft activity successfully deleted." in the tui success notification toast

    And I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Name            | Type     | Status |
      | Active activity | Feedback | Active |

  Scenario: Deleting an active activity
    # There is some issue with clicking the delete button in the drop down when it is not in the first row
    # that is why the activity setup is repeated with just the insertion order swapped.
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name   | description        | activity_type | activity_status |
      | Draft activity  | A draft activity   | feedback      | Draft           |
      | Active activity | An active activity | feedback      | Active          |

    When I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name            | Type     | Status |
      | Active activity | Feedback | Active |
      | Draft activity  | Feedback | Draft  |

    When I open the dropdown menu in the tui datatable row with "Active activity" "Name"
    And I click on "Delete" "link"
    Then I should see "delete all content created" in the tui modal
    And I should see "Deleted data cannot be recovered." in the tui modal
    And I should see "Are you sure you would like to delete this activity?" in the tui modal

    When I click on "Delete" "button"
    Then I should see "Activity and all associated user records successfully deleted." in the tui success notification toast
    And I should not see "Active activity"
    And I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Name           | Type     | Status |
      | Draft activity | Feedback | Draft  |

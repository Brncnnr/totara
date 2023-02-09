@totara @perform @totara_hierarchy @totara_competency @totara_customfield @javascript
Feature: Test competency type changes in hierarchies

  Scenario: Change type of competency in a hierarchy
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Site      | Manager  | manager@example.com |
    And the following "role assigns" exist:
      | user    | role    | contextlevel | reference |
      | manager | manager | System       |           |

    And the following hierarchy types exist:
      | hierarchy  | idnumber  | fullname          |
      | competency | comptype1 | Competency type 1 |
      | competency | comptype2 | Competency type 2 |

    And the following hierarchy type custom fields exist:
      | hierarchy  | typeidnumber | type | fullname         | shortname | value |
      | competency | comptype1    | text | Custom field 1_1 | CF1_1     |       |
      | competency | comptype1    | text | Custom field 1_2 | CF1_2     |       |
      | competency | comptype2    | text | Custom field 2_1 | CF2_1     |       |

    And I log in as "manager"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I press "Add new competency framework"
    And I set the following fields to these values:
      | Full Name | My competency frameworkd 1 |
    And I press "Save changes"
    And I follow "My competency frameworkd 1"
    And I press "Add new competency"
    And I set the following fields to these values:
      | Name | My competency 1   |
      | Type | Competency type 1 |
    And I press "Save changes"
    And I click on "Edit" "link" in the ".tui-competencySummaryGeneral" "css_element"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 1 |
      | Custom field 1_2 | Some text 2 |
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"

    And I press "Add new competency"
    And I set the following fields to these values:
      | Name | My competency 2   |
      | Type | Competency type 1 |
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"
    And I click on "Edit" "link" in the "My competency 2" "table_row"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 3 |
      | Custom field 1_2 | Some text 4 |
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"
    And I press "Add new competency"
    And I set the following fields to these values:
      | Name | My competency 3   |
      | Type | Competency type 1 |
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"
    And I click on "Edit" "link" in the "My competency 3" "table_row"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 5 |
      | Custom field 1_2 | Some text 6 |
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"
    And I should see "Type: Competency type 1" in the "My competency 1" "table_row"
    And I should see "Type: Competency type 1" in the "My competency 2" "table_row"
    And I should see "Type: Competency type 1" in the "My competency 3" "table_row"

    # Change type of single item
    When I click on "Edit" "link" in the "My competency 1" "table_row"
    And I press "Change type"
    And I click on "Choose" "button" in the "Competency type 2" "table_row"
    And I set the following fields to these values:
      | Data in Custom field 1_1 (Text input): | Transfer to Custom field 2_1 (Text input) |
      | Data in Custom field 1_2 (Text input): | Delete this data                          |
    And I press "Reclassify items and transfer/delete data"
    Then the field "Custom field 2_1" matches value "Some text 1"
    And I press "Save changes"
    And I click on "Back to My competency frameworkd 1" "link"
    And I should see "Type: Competency type 2" in the "My competency 1" "table_row"
    And I should see "Type: Competency type 1" in the "My competency 2" "table_row"
    And I should see "Type: Competency type 1" in the "My competency 3" "table_row"

    # Bulk change types
    When I navigate to "Manage types" node in "Site administration > Competencies"
    And I set the following fields to these values:
      | Reclassify of all items from the type: | Competency type 1 |
    And I click on "Choose" "button" in the "Competency type 2" "table_row"
    And I set the following fields to these values:
      | Data in Custom field 1_1 (Text input): | Transfer to Custom field 2_1 (Text input) |
      | Data in Custom field 1_2 (Text input): | Delete this data                          |
    When I press "Reclassify items and transfer/delete data"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I follow "My competency frameworkd 1"
    And I should see "Type: Competency type 2" in the "My competency 1" "table_row"
    And I should see "Type: Competency type 2" in the "My competency 2" "table_row"
    And I should see "Type: Competency type 2" in the "My competency 3" "table_row"

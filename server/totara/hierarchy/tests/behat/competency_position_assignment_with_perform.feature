@totara @perform @totara_hierarchy @totara_competency @javascript
Feature: Test if linking competency with position or organization is disabled when Perform is enabled.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                        | role         | context |
      | bilbo    | Bilbo     | Baggins  | bilbo.baggins@example.com    |              |         |
      | gandalf  | Gandalf   | the Grey | gandalf.the.grey@example.com | staffmanager | system  |
    And the following "competency" frameworks exist:
      | fullname                    | idnumber | description                               |
      | Reclaim the Lonely Mountain | CFW001   | The mountain in the north of Rhovanion... |
    And the following "competency" hierarchy exists:
      | framework | fullname        | idnumber | description                                        |
      | CFW001    | Kill the Smaug  | COMP001  | The dragon who invaded the Dwarf kingdom of Erebor |

    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | idnumber | fullname    |
      | fw1      | framework 1 |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | idnumber | fullname | pos_framework |
      | 1        | Pos 1    | fw1           |

    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber |
      | Org frame    | oframe   |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber | org_framework |
      | Organisation One | org1     | oframe        |

  Scenario: Competency assignment for organization/position hierarchy is not shown with perform enabled
    Given I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "Org frame" "link"
    And I click on "Organisation One" "link"
    Then I should not see "Link Competencies"

    When I navigate to "Manage positions" node in "Site administration > Positions"
    And I click on "framework 1" "link"
    And I click on "Pos 1" "link"
    Then I should not see "Link Competencies"

  Scenario: Competency assignment for organization/position hierarchy is visible with perform disabled
    Given I log in as "admin"
    And I disable the "competency_assignment" advanced feature
    When I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "Org frame" "link"
    When I click on "Organisation One" "link"
    Then I should see "Linked Competencies"
    And I click on "#show-linkedcompetencies-dialog" "css_element"
    Then I should see "Link competencies" in the "#page-admin-totara-hierarchy-framework-index [tabindex] .title" "css_element"

    When I am on homepage
    And I navigate to "Manage positions" node in "Site administration > Positions"
    And I click on "framework 1" "link"
    And I click on "Pos 1" "link"
    Then I should see "Linked Competencies"
    And I click on "#show-linkedcompetencies-dialog" "css_element"
    Then I should see "Link competencies" in the "#page-admin-totara-hierarchy-framework-index [tabindex] .title" "css_element"

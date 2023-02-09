@totara @perform @totara_hierarchy @totara_competency @javascript
Feature: Test use of multi language in competency attributes
  Background:
    Given I am on a totara site
    And the multi-language content filter is enabled

  Scenario: Framework type, scale and name contains multi-lang text
    When I log in as "admin"
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | <span lang="en" class="multilang">English Comp Type</span><span lang="nl" class="multilang">Nederlandse Comp Type</span> |
    And I press "Save changes"
    Then I should see "The competency type \"English Comp Type\" has been created"
    And I should not see "Nederlandse"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I press "Add a new competency scale"
    And I set the field "Name" to "<span lang=\"en\" class=\"multilang\">English Scale</span><span lang=\"nl\" class=\"multilang\">Nederlandse Schaal</span>"
    And I set the field "Scale values" to multiline:
"""
3
2
1
"""
    And I press "Save changes"
    Then I should see "Competency scale \"English Scale\" added"
    When I follow "All competency scales"
    Then I should see "English Scale"
    And I should not see "Nederlandse"

    When I press "Add new competency framework"
    And I set the field "Full Name" to "<span lang=\"en\" class=\"multilang\">English FW</span><span lang=\"nl\" class=\"multilang\">Nederlandse FW</span>"
    And I set the field "Scale" to "English Scale"
    And I press "Save changes"
    Then I should see "English FW"
    And I should not see "Nederlandse"

    When I follow "English FW"
    And I press "Add new competency"
    Then I should see "English FW"
    And I should see "English Scale"
    And I should not see "Nederlandse"

    When I set the field "Name" to "<span lang=\"en\" class=\"multilang\">English Competency</span><span lang=\"nl\" class=\"multilang\">Nederlandse Competentie</span>"
    And I set the field "Type" to "English Comp Type"
    And I press "Save and continue editing"
    Then I should see "The competency \"English Competency\" has been added"
    And I should not see "Nederlandse"

    Then I should see "English FW"
    And I should see "English Comp Type"
    And I should see "English Scale"
    And I should not see "Nederlandse FW"
    And I should not see "Nederlandse Comp Type"
    And I should not see "Nederlandse Schaal"
    # The name is editable and therefore shoes the multilang string
    And the field "Name" matches value "<span lang=\"en\" class=\"multilang\">English Competency</span><span lang=\"nl\" class=\"multilang\">Nederlandse Competentie</span>"

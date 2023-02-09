@javascript @totara @totara_reportbuilder
Feature: Disabled components disappear from reportbuilder filter options
  As an admin
  I should be able to disable components and do not see them in filter options

  Background:
    Given I am on a totara site

  Scenario Outline: Disable of competencies, learning plan or evidence advanced features will hide them from filter values
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Alerts"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "View" "link" in the "Alerts" "table_row"
    And I should see "<name>"
    When I set the following administration settings values:
      | <setting>     | 0 |
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Alerts"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "View" "link" in the "Alerts" "table_row"
    Then I should not see "<name>"
    Examples:
      | setting               | name          |
      | Enable Competencies   | Competency    |
      | Enable Learning Plans | Learning plan |
      | Enable Evidence       | Evidence      |

  Scenario: Disable of both programs and certifications advanced feature will hide program from filter values
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Alerts"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "View" "link" in the "Alerts" "table_row"
    And I should see "Program"
    # Disable one
    When I disable the "programs" advanced feature
    And I reload the page
    Then I should see "Program"
    # Disable both
    When I disable the "certifications" advanced feature
    And I reload the page
    Then I should not see "Program"

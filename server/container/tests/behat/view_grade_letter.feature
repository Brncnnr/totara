@container @totara @core_container
Feature: User view grade letter page
  Scenario: Admin is able to view grade letter page at system context level
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to "Grades > Letters" in site administration
    Then I should see "Grade letters"
    And I should see "Edit grade letters"
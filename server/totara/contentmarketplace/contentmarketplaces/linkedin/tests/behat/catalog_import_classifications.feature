@totara @contentmarketplace_linkedin @totara_contentmarketplace @javascript
Feature: Catalog import learning objects classifications.
  Background:
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn          | title    |
      | urn:course:1 | Course C |
      | urn:course:2 | Course B |
      | urn:course:3 | Course A |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn            | name                 | type    |
      | urn:category:1 | Software development | Library |
      | urn:category:2 | Java                 | Subject |
      | urn:category:3 | C++                  | Subject |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn     | child_urn      |
      | urn:category:1 | urn:category:2 |
      | urn:category:1 | urn:category:3 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | urn:course:1        | urn:category:2     |
      | urn:course:1        | urn:category:3     |
      | urn:course:2        | urn:category:3     |
    And I set up the "linkedin" content marketplace plugin

  Scenario: Filter the catalog learning objects by subjects
    Given I am on a totara site
    And I log in as "admin"
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then I should see "C++, Java" on row "1" of the tui select table
    And I should see "C++" on row "2" of the tui select table
    And I should see "Software development"
    # Course C, B but not the filter
    And I should see "C++" exactly "2" times
    When I click on "Software development" "button"
    # Course C, B and the filter
    Then I should see "C++" exactly "3" times
    And I should see "Course C"
    And I should see "Course B"
    And I should see "Course A"
    When I set the field "C++" to "1"
    Then I should not see "Course A"
    And I should see "Course C"
    And I should see "Course B"
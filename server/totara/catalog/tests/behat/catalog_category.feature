@course @totara @totara_catalog @javascript @tenant
Feature: Check category visibility
  Background:
    Given I am on a totara site

    And the following "categories" exist:
      | name  | category | idnumber | visible |
      | Cat1  | 0        | cat1     |    1    |
      | Cat2  | 0        | cat2     |    1    |
      | Cat1a | cat1     | cat1a    |    1    |
      | Cat3  | 0        | cat3     |    0    |

    And tenant support is enabled without tenant isolation
    And the following "tenants" exist:
      | name     | idnumber | suspended | categoryname      |
      | Tenant1  | t1       | 0         | Tenant1 Category  |
      | Tenant2  | t2       | 0         | Tenant2 Category  |

    And the following "users" exist:
      | username | firstname | lastname | email          | tenantmember |
      | user1    | User      | One      | user1@test.com |     t1       |
      | user2    | User      | Two      | user2@test.com |              |

  Scenario: List categories with visibility check
    Given I am on homepage
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    When I click on "All" "button"
    Then I should see "Cat1"
    And I should see "Cat2"
    And I should see "Cat3"
    And I should see "Tenant1 Category"
    And I should see "Tenant2 Category"
    And I log out

    And I log in as "user1"
    And I click on "Find Learning" in the totara menu
    When I click on "All" "button"
    Then I should see "Cat1"
    And I should see "Cat2"
    And I should not see "Cat3"
    And I should not see "Tenant2 Category"
    And I should see "Tenant1 Category"
    And I log out

    And I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | moodle/category:viewhiddencategories | Allow |
    Then I log out

    And I log in as "user2"
    And I click on "Find Learning" in the totara menu
    When I click on "All" "button"
    Then I should see "Cat1"
    And I should see "Cat2"
    And I should see "Cat3"
    And I should see "Tenant1 Category"
    And I should see "Tenant2 Category"
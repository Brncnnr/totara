@engage @block @javascript @totara @block_totara_recently_viewed
Feature: Card titles have multi lang filters applied correctly
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname                                                                                         | shortname | category |
      | <span class="multilang" lang="en">CourseA</span><span class="multilang" lang="nl">CourseB</span> | C1        | 0        |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                                                                                           | shortname |
      | <span class="multilang" lang="en">ProgramA</span><span class="multilang" lang="nl">ProgramB</span> | P1        |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname                                                                                     | shortname |
      | <span class="multilang" lang="en">CertA</span><span class="multilang" lang="nl">CertB</span> | CT1       |

  Scenario: Test that the course name filters languages correctly
    When I log in as "admin"
    And the multi-language content filter is enabled
    And I am on "<span class=\"multilang\" lang=\"en\">CourseA</span><span class=\"multilang\" lang=\"nl\">CourseB</span>" course homepage
    And I am on "<span class=\"multilang\" lang=\"en\">ProgramA</span><span class=\"multilang\" lang=\"nl\">ProgramB</span>" program homepage
    And I am on "<span class=\"multilang\" lang=\"en\">CertA</span><span class=\"multilang\" lang=\"nl\">CertB</span>" certification homepage
    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block to the "main" region

    # Confirm we see only the one language tag, not both
    Then I should see "CourseA" in the ".block-totara-recently-viewed" "css_element"
    And I should see "ProgramA" in the ".block-totara-recently-viewed" "css_element"
    And I should see "CertA" in the ".block-totara-recently-viewed" "css_element"
    And I should not see "CourseB" in the ".block-totara-recently-viewed" "css_element"
    And I should not see "ProgramB" in the ".block-totara-recently-viewed" "css_element"
    And I should not see "CertB" in the ".block-totara-recently-viewed" "css_element"

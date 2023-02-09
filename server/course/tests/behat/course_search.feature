@core @core_course
Feature: Courses can be searched for and moved in bulk.
  In order to manage a large number of courses
  As a Moodle Administrator
  I need to be able to search courses in bulk and move them around

  Background:
    Given the following "categories" exist:
      | name | category | idnumber |
      | Science | 0 | SCI |
      | English | 0 | ENG |
      | Miscellaneous | 0 | MISC |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Biology Y1 | BIO1 | MISC |
      | Biology Y2 | BIO2 | MISC |
      | English Y1 | ENG1 | ENG |
      | English Y2 | ENG2 | MISC |

  Scenario: Search courses finds correct results
    Given I log in as "admin"
    And I go to the courses management page
    When I set the field "Search courses" to "Biology"
    And I press "Go"
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should not see "English Y1"
    And I should not see "English Y2"

  @javascript
  Scenario: Search courses and move results in bulk
    Given I log in as "admin"
    And I go to the courses management page
    And I set the field "Search courses" to "Biology"
    And I press "Go"
    When I select course "Biology Y1" in the management interface
    And I select course "Biology Y2" in the management interface
    And I set the field "menumovecoursesto" to "Science"
    And I press "Move"
    Then I should see "Successfully moved 2 courses into Science"
    And I wait to be redirected
    And I click on category "Science" in the management interface
    And I should see "Biology Y1"
    And I should see "Biology Y2"

  Scenario: Prevent switching off search result pagination for non-logged in users
    Given I log in as "admin"
    # Disable forcelogin, so the search can be accessed by non-logged in users.
    And I set the following administration settings values:
      | forcelogin     | 0 |
      | coursesperpage | 2 |
    When I search courses for search term "Y" with "3" results per page
    # Default sort order is by display name.
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should see "English Y1"
    And I should not see "English Y2"
    And I should see "Show all"

    # Logged in user can switch off pagination using "perpage=all".
    When I search courses for search term "Y" with "all" results per page
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should see "English Y1"
    And I should see "English Y2"
    And I should not see "Show all"

    When I log out
    And I search courses for search term "Y" with "3" results per page
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should not see "English Y1"
    And I should not see "English Y2"
    And I should not see "Show all"

    # Non-logged in user is not allowed to switch off pagination. Fall back to default - 2 courses per page.
    When I search courses for search term "Y" with "all" results per page
    Then I should see "Biology Y1"
    And I should see "Biology Y2"
    And I should not see "English Y1"
    And I should not see "English Y2"
    And I should not see "Show all"

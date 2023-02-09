@core @core_course
Feature: On the course management page, users with a certain role can view search results correctly.

  Background:
    Given the following "categories" exist:
      | name     | category | idnumber |
      | Science  | 0        | SCI      |
      | English  | 0        | ENG      |
      | Science2 | 0        | SCI2     |
    And the following "courses" exist:
      | fullname   | shortname | category |
      | Biology Y1 | BIO1      | SCI2     |
      | Biology Y2 | BIO2      | SCI2     |
      | English Y1 | ENG1      | ENG      |
    And the following "users" exist:
      | username             | firstname              | lastname               | email             |
      | coursecreatoruser    | coursecreatoruser      | coursecreatoruser      | a1@example.com    |
    # The Editing Trainer role may not normally enabled at Category context initially, but the behat data generator used below sets it automatically.
    And the following "role assigns" exist:
      | user                      | role                | contextlevel | reference |
      | coursecreatoruser         | editingteacher      | Category     | SCI2      |
      | coursecreatoruser         | coursecreator       | Category     | SCI2      |

  @javascript
  Scenario: Can as a user with course_creator role see course search results with edit icons displayed alongside.
    Given I log in as "coursecreatoruser"
    And I am on a totara site
    And I go to the courses management page
    And I set the field "Search courses" to "Biology"
    And I press "Go"
    Then "//span[contains(@class,'course-item-actions')]" "xpath_element" should exist in the "//div[@id='course-listing']" "xpath_element"

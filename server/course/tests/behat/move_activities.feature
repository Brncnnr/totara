@core @core_course
Feature: Activities can be moved between sections
  In order to rearrange my course contents
  As a teacher
  I need to move activities between sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1 | topics | 0 | 5 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Delete Recent activity block"
    And I press "Yes"
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Description | Test forum description |

  Scenario: Move activities in a single page course
    When I move "Test forum name" activity to section "2"
    Then I should see "Test forum name" in the "Topic 2" "section"
    And I should not see "Test forum name" in the "Topic 1" "section"

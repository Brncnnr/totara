@mod @mod_facetoface @totara
Feature: Add a seminar event and session
  In order to run a seminar
  As a teacher
  I need to create a seminar activity and add a session to it

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | Terry1    | Teacher1 | teacher1@moodle.com |
      | student1 | Sam1      | Student1 | student1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Add and configure a seminar activity with a single session
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | ## next year ## Y ## |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | ## next year ## Y ## |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I should see date "1 Jan next year" formatted "%d %B %Y"

  @javascript
  Scenario: Add a seminar activity with multi language support
    Given I log in as "admin"
        # Enabling multi-language filters for headings and content.
    And I navigate to "Manage filters" node in "Site administration > Plugins > Filters"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='activemultilang']//select[@name='newstate']" to "1"
    And I set the field with xpath "//table[@id='filterssetting']//form[@id='applytomultilang']//select[@name='stringstoo']" to "1"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | <span lang="de" class="multilang">German seminar name</span><span lang="en" class="multilang">English seminar name</span> |
      | Description | <span lang="de" class="multilang">German seminar description</span><span lang="en" class="multilang">English seminar description</span> |
    When I follow "View all events"
    Then I should see "English seminar name"
    And I should not see "German seminar name"
    And I should see "English seminar description"
    And I should not see "German seminar description"


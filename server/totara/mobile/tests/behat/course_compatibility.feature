@totara @core @core_course @javascript @totara_mobile
Feature: The in-app compatibility setting works as expected.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    When I log in as "admin"

  Scenario: Mobile app is not enabled, so I should not see the in-app compatibility setting.
    Given I am on "Course 1" course homepage
    When I navigate to "Edit settings" node in "Course administration"
    Then I should not see "Mobile-friendly course"

  Scenario: Mobile app is enabled, but course default in-app compatibility setting is unset.
    Given I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app | 1 |
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Mobile-friendly course"
    And the field "Mobile-friendly course" matches value "No"
    When I navigate to "Courses and categories" node in "Site administration >  Courses"
    And I follow "Create new course"
    Then I should see "Mobile-friendly course"
    And the field "Mobile-friendly course" matches value "Yes"
    And I set the following fields to these values:
      | Course full name  | Course 2 |
      | Course short name | C2       |
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Mobile-friendly course" matches value "Yes"

  Scenario: Mobile app is enabled, and course default setting in-app compatibility is set.
    Given I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Enable mobile app      | 1  |
      | Mobile-friendly course | No |
    And I press "Save changes"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    Then I should see "Mobile-friendly course"
    And the field "Mobile-friendly course" matches value "No"
    When I navigate to "Courses and categories" node in "Site administration >  Courses"
    And I follow "Create new course"
    Then I should see "Mobile-friendly course"
    And the field "Mobile-friendly course" matches value "No"
    And I set the following fields to these values:
      | Course full name       | Course 2 |
      | Course short name      | C2       |
      | Mobile-friendly course | Yes      |
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Mobile-friendly course" matches value "Yes"
    When I navigate to "Plugins > Mobile > Mobile settings" in site administration
    And I set the following fields to these values:
      | Mobile-friendly course | Yes |
    And I press "Save changes"
    When I navigate to "Courses and categories" node in "Site administration >  Courses"
    And I follow "Create new course"
    Then I should see "Mobile-friendly course"
    And the field "Mobile-friendly course" matches value "Yes"
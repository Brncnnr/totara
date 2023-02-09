@core @core_course @totara @javascript
Feature: Course with collapsible topics
  In order to test a course with collapsible topics
  As an admin
  I will configure a course using topics format with an activity in each

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | trainer1 | trainer1  | trainer1 | trainer1@example.com |
      | learner1 | learner1  | learner1 | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format   | numsections |
      | Course 1 | C1        | topics   | 4           |
    And the following "course enrolments" exist:
      | user     | course | role            |
      | trainer1 | C1     | editingteacher  |
      | learner1 | C1     | student         |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1" and I fill the form with:
      | Name                | Test Page for Topic 1    |
      | Description         | Description for Topic 1  |
      | Page content        | Content for Topic 1      |
    And I add a "Page" to section "2" and I fill the form with:
      | Name                | Test Page for Topic 2    |
      | Description         | Description for Topic 2  |
      | Page content        | Content for Topic 2      |
    And I add a "Page" to section "3" and I fill the form with:
      | Name                | Test Page for Topic 3    |
      | Description         | Description for Topic 3  |
      | Page content        | Content for Topic 3      |
    And I add a "Page" to section "4" and I fill the form with:
      | Name                | Test Page for Topic 4    |
      | Description         | Description for Topic 4  |
      | Page content        | Content for Topic 4      |
    And I log out

  Scenario: I can enable collapsable sections within the course
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should not exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"

    When I turn editing mode on
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should not exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"
    And I log out

    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should not exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"
    And I log out

    When I log in as "trainer1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | collapsiblesections | 1 |
    And I press "Save and display"
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I "collapse" course topic "1"
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    When I "expand" course topic "2"
    Then I should see "Test Page for Topic 2" in the "region-main" "region"
    When I "expand" course topic "3"
    Then I should see "Test Page for Topic 3" in the "region-main" "region"
    When I "expand" course topic "4"
    Then I should see "Test Page for Topic 4" in the "region-main" "region"

    When I turn editing mode on
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should not exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"
    And I log out

    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I "collapse" course topic "1"
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"

    When I "expand" course topic "1"
    Then I should see "Test Page for Topic 1" in the "region-main" "region"

    When I "expand" course topic "2"
    Then I should see "Test Page for Topic 2" in the "region-main" "region"

    When I "collapse" course topic "2"
    Then I should not see "Test Page for Topic 2" in the "region-main" "region"

    When I "expand" course topic "3"
    Then I should see "Test Page for Topic 3" in the "region-main" "region"

    When I "collapse" course topic "3"
    Then I should not see "Test Page for Topic 3" in the "region-main" "region"

    When I "expand" course topic "4"
    Then I should see "Test Page for Topic 4" in the "region-main" "region"

    When I "collapse" course topic "4"
    Then I should not see "Test Page for Topic 4" in the "region-main" "region"

  Scenario: I can include an expand/collapse all collapsable sections link within the course
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage
    Then I should not see "Expand all"

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | collapsiblesections            | 1 |
      | collapsiblesectionscollapseall | 1 |
    And I press "Save and display"
    Then I should see "Expand all"
    And "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I click on "Expand all" "link"
    Then I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"
    And I should see "Collapse all"

    When I click on "Collapse all" "link"
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"
    And I should see "Expand all"

    When I "expand" course topic "2"
    Then I should see "Test Page for Topic 2" in the "region-main" "region"

    When I turn editing mode on
    Then "//a[contains(@class,'tw-formatTopics__collapse_link')]" "xpath_element" should not exist
    And I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should see "Test Page for Topic 4" in the "region-main" "region"

  Scenario: I can set a default collapse state for individual sections within the course
    Given I log in as "trainer1"
    And I am on "Course 1" course homepage with editing mode on

    When I click on "Edit" "link" in the "//li[contains(@id,'section-1')]" "xpath_element"
    And I click on "Edit topic" "link" in the "//li[contains(@id,'section-1')]" "xpath_element"
    Then I should not see "Default collapse state"

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | collapsiblesections            | 1 |
      | collapsiblesectionscollapseall | 1 |
    And I press "Save and display"
    And I click on "Edit" "link" in the "//li[contains(@id,'section-1')]" "xpath_element"
    And I click on "Edit topic" "link" in the "//li[contains(@id,'section-1')]" "xpath_element"
    Then I should see "Default collapse state"

    When I set the following fields to these values:
      | collapseddefault  | Collapsed |
    And I press "Save changes"
    And I turn editing mode off
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I turn editing mode on
    And I click on "Edit" "link" in the "//li[contains(@id,'section-2')]" "xpath_element"
    And I click on "Edit topic" "link" in the "//li[contains(@id,'section-2')]" "xpath_element"
    And I set the following fields to these values:
      | collapseddefault  | Expanded |
    And I press "Save changes"
    And I turn editing mode off
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"
    And I log out

    When I log in as "learner1"
    And I am on "Course 1" course homepage
    And I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

  Scenario: The expand or collapsed state of individual sections within a course is is persistent on page reload
    Given I log in as "trainer1"
    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | collapsiblesections | 1 |
    And I press "Save and display"
    Then I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I "collapse" course topic "1"
    And I "expand" course topic "2"
    And I "expand" course topic "3"
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    # The state should persist on page reload.
    When I am on homepage
    And I am on "Course 1" course homepage
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"
    And I log out

    When I log in as "learner1"
    And I am on "Course 1" course homepage
    Then I should see "Test Page for Topic 1" in the "region-main" "region"
    And I should not see "Test Page for Topic 2" in the "region-main" "region"
    And I should not see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    When I "collapse" course topic "1"
    And I "expand" course topic "2"
    And I "expand" course topic "3"
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

    # The state should persist on page reload.
    When I am on homepage
    And I am on "Course 1" course homepage
    Then I should not see "Test Page for Topic 1" in the "region-main" "region"
    And I should see "Test Page for Topic 2" in the "region-main" "region"
    And I should see "Test Page for Topic 3" in the "region-main" "region"
    And I should not see "Test Page for Topic 4" in the "region-main" "region"

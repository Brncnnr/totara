@totara @totara_dashboard @javascript
Feature: Test Dashboard defaults
    In order to test the correct behaviour related to the visibility settings for the dashboard feature
    As a admin
    I need to choose among the three different settings (show/hide/disabled) and check the GUI

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | student1 | Student   | One      | student.one@example.com |
    And the following totara_dashboards exist:
      | name             | locked | published |
      | Test Dashboard 1 | 0      | 2         |
      | Test Dashboard 2 | 0      | 2         |
    # Login to get the Latest announcements created.
    And I log in as "admin"
    And I set the following administration settings values:
      | allowdefaultpageselection | 1 |
    And I am on site homepage
    And I turn editing mode on
    And I add the "Latest announcements" block
    And I am on "Dashboard" page
    And I should see "Latest announcements"
    And I log out

  Scenario: Dashboard is default page for all users except admin by default
    When I log in as "student1"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And "Make home page" "button" should exist
    And I should see "Current Learning"

    When I am on "Dashboard" page
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Latest announcements"
    And I should see "Current Learning"
    And "Make home page" "button" should exist

    When I click on "Home" in the totara menu
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Latest announcements"
    And I should see "Current Learning"
    And "Make home page" "button" should exist

    When I click on "Make home page" "button"
    Then "Make home page" "button" should not exist
    And I should see "Your default page was changed"
    And I should see "Current Learning"
    And I log out

    When I log in as "student1"
    Then I should see "Latest announcements"
    And I should see "Current Learning"
    And "Make home page" "button" should not exist

    When I am on "Dashboard" page
    Then "Make home page" "button" should not exist

  Scenario: Home is default page for admin by default
    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"

    When I am on "Dashboard" page
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"

    When I click on "Home" in the totara menu
    Then I should see "Latest announcements"
    And I should not see "Current Learning"

    When I am on "Dashboard" page
    And I press "Make home page"
    And I should not see "Make home page"
    And I should see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And "Make home page" "button" should not exist

    When I click on "Home" in the totara menu
    And "Make home page" "button" should not exist
    And I should see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "Latest announcements"
    And I should see "Current Learning"
    And "Make home page" "button" should not exist

  Scenario: Disable Totara Dashboard feature
    Given I disable the "totaradashboard" advanced feature

    When I log in as "student1"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I log out

    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    And I log out

  Scenario: Set Home as default user page
    Given I log in as "admin"
    And I set the following administration settings values:
      | defaulthomepage | Front page |
    And I log out

    When I log in as "student1"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    When I am on "Dashboard" page
    And I press "Make home page"
    And I should not see "Make home page"
    And I should see "Current Learning"
    And I log out
    And I log in as "student1"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make home page"
    And I log out

    When I log in as "admin"
    Then I should see "Latest announcements"
    And I should not see "Current Learning"
    When I am on "Dashboard" page
    And I press "Make home page"
    And I should not see "Make home page"
    And I should see "Current Learning"
    And I log out
    And I log in as "admin"
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Current Learning"
    And I should not see "Make home page"
    And I log out

  Scenario: Confirm that we are able to change the home page from one dashboard to another
    Given I log in as "admin"
    And I set the following administration settings values:
      | defaulthomepage | Front page |
    And I log out
    And I log in as "student1"
    And I am on "Dashboard" page

    # Navigate to Test Dashboard 1 and confirm we can set it as home page
    When I click on "Test Dashboard 1" "link"
    Then "Make home page" "button" should be visible
    When I press "Make home page"
    Then "Make home page" "button" should not be visible

    # Navigate to Test Dashboard 2 and confirm we can set it as home page
    When I click on "Test Dashboard 2" "link"
    Then "Make home page" "button" should be visible
    When I press "Make home page"
    Then "Make home page" "button" should not be visible

    # When navigating back to Test Dashboard 1 the 'make home page' button should be available again
    When I click on "Test Dashboard 1" "link"
    Then "Make home page" "button" should be visible

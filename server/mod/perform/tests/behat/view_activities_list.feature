@totara @perform @mod_perform @javascript @vuejs
Feature: Manage performance activity page
  As an activity administrator
  I need to be able to manage activities
  so that I can change them according to the needs.

  As an activity creator
  I need to be able to create activities

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                   |
      | john     | John      | One      | john.one@example.com    |
      | jack     | Jack      | Rabbit   | jack.rabbit@example.com |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name   | description        | activity_type | activity_status | created_at |
      | Active activity | An active activity | feedback      | Active          | 2000-01-01 |
      | Draft activity  | A draft activity   | feedback      | Draft           | 2000-01-01 |

  Scenario: Admin can access the activity management page
    Given I log in as "admin"
    When I navigate to the manage perform activities page
    # The admin should always see all existing activities
    Then I should see the tui datatable contains:
      | Name            | Type     | Status |
      | Draft activity  | Feedback | Draft  |
      | Active activity | Feedback | Active |
    And "Participation reporting" "link" should exist

  Scenario: User can access the activity management page and create an activity given the right capabilities
    Given I log in as "admin"
    # To create an activity you need both create capabilities, in mod_perform and in the container
    And I set the following system permissions of "Authenticated User" role:
      | mod/perform:view_manage_activities | Allow |
    And I log out

    Given I log in as "john"
    When I navigate to the manage perform activities page
    Then I should see "No activities have been created yet."
    And I should not see "Add activity"
    And I log out

    Given I log in as "admin"
    # To create an activity you need both create capabilities, in mod_perform and in the container
    And I set the following system permissions of "Authenticated User" role:
      | mod/perform:create_activity        | Allow |
      | container/perform:create           | Allow |
    And I log out

    Given I log in as "john"
    When I navigate to the manage perform activities page
    Then I should see "No activities have been created yet."
    And I should see "Add activity"

    When I click on "Add activity" "button"
    Given I set the following fields to these values:
      | Title | My Test Activity             |
      | Description    | My Test Activity description |
      | Type  | Feedback                     |
    When I click on "Create" "button"
    And I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | My Test Activity | Feedback | Draft |
    # For activities created by the user the reporting link should be there
    And "Participation reporting" "link" should not exist in the ".tui-performActivityActions" "css_element"

  Scenario: User can access the activity management page and manage activities given the right capabilities
    Given I log in as "admin"
    # To create an activity you need both create capabilities, in mod_perform and in the container
    And I set the following system permissions of "Authenticated User" role:
      | mod/perform:view_manage_activities | Allow |
      | mod/perform:manage_activity      | Allow |
    And I log out

    Given I log in as "john"
    When I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | Draft activity  | Feedback | Draft  |
      | Active activity | Feedback | Active |
    # For activities created by the user the reporting link should be there
    And "Participation reporting" "link" should not exist
    Then I log out

    Given I log in as "admin"
    # To be able to see the link to the reporting the user needs to right capability
    And I set the following system permissions of "Authenticated User" role:
      | mod/perform:view_participation_reporting | Allow |
    And I log out

    Given I log in as "jack"
    When I navigate to the manage perform activities page
    Then I should see the tui datatable contains:
      | Name             | Type     | Status |
      | Draft activity   | Feedback | Draft  |
      | Active activity  | Feedback | Active |
    # Now the user should see the link to the report page in the second row but not the first
    And "Participation reporting" "link" should not exist in the ".tui-dataTableRow:nth-child(1) .tui-performActivityActions" "css_element"
    And "Participation reporting" "link" should exist in the ".tui-dataTableRow:nth-child(2) .tui-performActivityActions" "css_element"
    Then I log out

  Scenario: Activities are paginated and can be filtered.
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | 1        | user1@example.com |
      | user2    | user      | 2        | user2@example.com |
    And the following "cohorts" exist:
      | name | idnumber |
      | aud1 | aud1     |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name        | activity_type | activity_status | create_track | create_section | created_at |
      | Perform activity 010 | feedback      | Draft           | false        | false          | 2016-01-23 |
      | Perform activity 020 | check-in      | Active          | true         | true           | 2004-02-04 |
      | Perform activity 030 | feedback      | Draft           | true         | true           | 2020-06-26 |
      | Perform activity 040 | appraisal     | Active          | true         | true           | 1998-05-29 |
      | Perform activity 050 | check-in      | Draft           | true         | true           | 2005-01-02 |
      | Perform activity 060 | feedback      | Active          | true         | true           | 2012-08-12 |
      | Perform activity 070 | appraisal     | Draft           | true         | true           | 2014-01-01 |
      | Perform activity 080 | check-in      | Active          | true         | true           | 2001-09-11 |
      | Perform activity 090 | feedback      | Draft           | true         | true           | 1996-02-28 |
      | Perform activity 100 | appraisal     | Active          | true         | true           | 1999-01-17 |
      | Perform activity 110 | check-in      | Draft           | true         | true           | 2000-10-04 |
      | Perform activity 120 | feedback      | Active          | true         | true           | 2007-07-29 |
      | Perform activity 130 | appraisal     | Draft           | true         | true           | 2006-06-05 |
      | Perform activity 140 | check-in      | Active          | true         | true           | 2018-03-03 |
      | Perform activity 150 | feedback      | Draft           | true         | true           | 2019-04-04 |
      | Perform activity 160 | appraisal     | Active          | true         | true           | 2015-05-05 |
      | Perform activity 170 | check-in      | Draft           | true         | true           | 2006-06-06 |
      | Perform activity 180 | feedback      | Active          | true         | true           | 2002-07-07 |
      | Perform activity 190 | appraisal     | Draft           | true         | true           | 1992-08-08 |
      | Perform activity 200 | check-in      | Active          | true         | true           | 1994-09-09 |
      | Perform activity 210 | feedback      | Draft           | true         | true           | 2021-10-11 |
      | Perform activity 220 | appraisal     | Active          | true         | true           | 2013-11-11 |
      | Perform activity 230 | check-in      | Draft           | true         | true           | 2010-12-12 |
      | Perform activity 240 | feedback      | Active          | true         | true           | 2009-01-01 |
      | Perform activity 250 | appraisal     | Draft           | true         | true           | 1989-02-02 |
      | Perform activity 260 | check-in      | Active          | true         | true           | 1990-12-25 |
      | Perform activity 270 | feedback      | Draft           | true         | true           | 1988-02-16 |
      | Perform activity 280 | appraisal     | Active          | true         | true           | 1987-03-13 |
    And the following "activities" exist in "mod_perform" plugin:
      | activity_name        | activity_type | activity_status | create_track | create_section | created_at |
      | Perform activity 290 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 300 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 310 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 320 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 330 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 340 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 350 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 360 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 370 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 380 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 390 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 400 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 410 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 420 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 430 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 440 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 450 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 460 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 470 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 480 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 490 | appraisal     | Active          | true         | true           | 2000-01-01 |
      | Perform activity 500 | appraisal     | Active          | true         | true           | 2000-01-01 |
    And the following "activity sections" exist in "mod_perform" plugin:
      | activity_name        | section_name |
      | Perform activity 010 | section 1    |
    And the following "section relationships" exist in "mod_perform" plugin:
      | section_name | relationship |
      | section 1    | subject      |
    And the following "section elements" exist in "mod_perform" plugin:
      | section_name | element_name |
      | section 1    | short_text   |
    And the following "activity tracks" exist in "mod_perform" plugin:
      | activity_name        | track_description |
      | Perform activity 010 | track 1           |
    And the following "track assignments" exist in "mod_perform" plugin:
      | track_description | assignment_type | assignment_name |
      | track 1           | cohort          | aud1            |

    Given I log in as "admin"
    When I navigate to the manage perform activities page
    And I set the field "Sort by" to "Name"
    And I wait for pending js
    Then I should see "Perform activity 010"
    And I should not see "Perform activity 190"
    When I open the dropdown menu in the tui datatable row with "Perform activity 010" "Name"
    And I click on "Activate" option in the dropdown menu
    Then I should see "Confirm activity activation" in the tui modal
    When I click on "Activate" "button"
    Then I should see "Perform activity 180"
    And I should not see "Perform activity 190"

    When I click on "Load more" "button"
    Then I should see "Perform activity 200"
    And I should see "Perform activity 210"
    And I should see "Perform activity 220"
    And I should see "Perform activity 230"
    And I should see "Perform activity 240"
    And I should see "Perform activity 250"
    And I should see "Perform activity 260"
    And I should see "Perform activity 270"
    And I should see "Perform activity 280"
    When I click on "Load more" "button"
    Then I should not see "Load more"

    When I set the field "Sort by" to "Creation date"
    Then I should see "Perform activity 210" under "Name" on row "1" of the tui datatable
    And I should see "11 October 2021" under "Creation date" on row "1" of the tui datatable
    And I should see "Perform activity 030" under "Name" on row "2" of the tui datatable
    And I should see "26 June 2020" under "Creation date" on row "2" of the tui datatable
    And I should see "Perform activity 150" under "Name" on row "3" of the tui datatable
    And I should see "4 April 2019" under "Creation date" on row "3" of the tui datatable
    And I should not see "Perform activity 250"
    And I should not see "Perform activity 270"
    And I should not see "Perform activity 280"

    When I set the field "Sort by" to "Name"
    And I set the field "Type" to "Appraisal"
    Then I should see "Showing 20 of 31 activities"
    And I should see "20" rows in the tui datatable

    When I open the dropdown menu in the tui datatable row with "Perform activity 290" "Name"
    And I click on "Delete" option in the dropdown menu
    Then I should see "Confirm activity deletion" in the tui modal
    When I click on "Delete" "button"
    Then I should not see "Perform activity 290"
    And I should see "Showing 20 of 30 activities"
    And I should see "20" rows in the tui datatable

    When I click on "Load more" "button"
    Then I should see "Showing 30 of 30 activities"
    And I should see "30" rows in the tui datatable
    When I set the field "Name" to "Activity 0"
    And I set the field "Status" to "Active"
    Then I should see "Showing 1 of 1 activities"

    When I set the following fields to these values:
      | Name    | ZZZZZZZZZZZZZ |
      | Sort by | Creation date |
    Then I should see "No matching items found."

    When I set the field "Name" to ""
    And I set the following fields to these values:
      | Type    | All |
      | Status  | All |
    Then I should see "Showing 20 of 51 activities"

@engage @totara @totara_msteams @totara_catalog @totara_program @totara_engage @core_course @javascript
Feature: Add a tab to Teams Channel
  As a course co-ordinator
  I would like to add a course, programme, certification or playlist to a Teams channel
  So that I can create teams and give them access to relevant assigned learning

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "courses" exist:
      | fullname              | shortname | category | summary                                                                                                                          |
      | Culinary arts 101     | CUA101    | 0        | Let's learn cooking! This course is intended for beginners that want to learn how to bake muffins and cupcakes at the same time. |
      | Contemporary arts 102 | COA102    | 0        | We will introduce the eclectic and eccentric world of contemporary arts. |
      | Machine learning 101  | MLN101    | 0        | Learn everything about machine learning, from AlphaGo to SkyNet. |
    And the following "programs" exist in "totara_program" plugin:
      | fullname       | shortname |
      | Mastering arts | P101      |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname    | shortname |
      | Junior chef | CP102     |
    And I add a courseset with courses "CUA101" to "P101":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I add a courseset with courses "COA102" to "P101":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user  |
      | P101    | user1 |
      | CP102   | user1 |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "articles" exist in "engage_article" plugin:
      | name                               | username | content       | access | topics  |
      | 10 apps to boost your productivity | user1    | Lorem? Ipsum! | PUBLIC | Topic 1 |
    And the following "surveys" exist in "engage_survey" plugin:
      | question                     | username | content              | options           | access | topics  |
      | Working from home or office? | user1    | Which do you prefer? | Home,Office,Mixed | PUBLIC | Topic 1 |
    And the following "playlists" exist in "totara_playlist" plugin:
      | name          | username | access | topics  |
      | Cool playlist | user1    | PUBLIC | Topic 1 |
    And I log in as "user1"

  Scenario: msteams401: Search for non-existent learning catalogue
    Given I am on Microsoft Teams "config" page
    Then the field "Tab name" matches value ""
    And the save button should be "disabled" on Microsoft Teams "config" page
    And I should see "Culinary arts"
    And I should see "Contemporary arts"
    And I should see "Machine learning"
    And I should see "Junior chef"
    And I should see "Cool playlist"
    And I should see "10 apps to boost your productivity"
    But I should not see "Working from home or office?"
    And I should not see "We couldn't find any matches"
    And I should see "Logged in as User One."
    And I should see "Sign out"

    When I set the field "Search the catalog and select an item to be added in a new tab" to "Mastering"
    Then I should see "Mastering arts"

    When I set the field "Search the catalog and select an item to be added in a new tab" to "korenga"
    Then I should see "We couldn't find any matches"
    And I should not see "Course"
    And I should not see "Program"
    And I should not see "Certification"
    And I should not see "Resource"
    And I should not see "Playlist"

  Scenario: msteams402: Check the field validation part 1
    Given I am on Microsoft Teams "config" page
    Then I should not see "Name is required"
    And the save button should be "disabled" on Microsoft Teams "config" page

    When I take focus off "Tab name" "field"
    And I set the field "Search the catalog and select an item to be added in a new tab" to ""
    Then I should see "Name is required"

    # Split a scenario here to prevent the 'take focus off' step from messing up with further testing.

  Scenario: msteams403: Check the field validation part 2
    Given I am on Microsoft Teams "config" page
    When I set the field "Search the catalog and select an item to be added in a new tab" to "arts"
    Then I should see "Culinary arts 101"
    When I click on "Culinary arts 101" "list_item"
    Then I should not see "Name is required"
    And the save button should be "enabled" on Microsoft Teams "config" page

    When I set the field "Tab name" to ""
    Then I should see "Name is required"
    And the save button should be "disabled" on Microsoft Teams "config" page

    When I set the field "Tab name" to "Cooking!"
    Then I should not see "Name is required"
    And the save button should be "enabled" on Microsoft Teams "config" page

  Scenario: msteams404: Add a custom tab to a teams channel
    Given I am on Microsoft Teams "config" page
    When I set the field "Search the catalog and select an item to be added in a new tab" to "arts"
    Then the field "Tab name" matches value ""
    And the save button should be "disabled" on Microsoft Teams "config" page
    And I should see "Culinary arts"
    And I should see "Contemporary arts"
    And I should see "Mastering arts"
    But I should not see "Machine learning"
    But I should not see "Junior chef"
    But I should not see "Cool playlist"
    But I should not see "10 apps to boost your productivity"
    And I should see "Logged in as User One."
    And I should see "Sign out"

    When I click on "Contemporary arts 102" "list_item"
    Then the field "Tab name" matches value "Contemporary arts 102"
    And the save button should be "enabled" on Microsoft Teams "config" page

    When I set the field "Tab name" to "Name modified"
    And I click on "Culinary arts 101" "list_item"
    Then the field "Tab name" matches value "Culinary arts 101"
    And the save button should be "enabled" on Microsoft Teams "config" page

    When I set the field "Tab name" to "Name modified again"
    And I click the save button on Microsoft Teams "config" page
    Then the "suggestedTabName" of Microsoft Teams settings matches value "Name modified again"
    And "success" state should be notified to Microsoft Teams

@engage @totara @totara_msteams @totara_catalog @totara_program @totara_engage @block_current_learning @mod_facetoface @javascript
Feature: Navigate a learning item in a custom tab
  As a user
  I would like to see my current learning, Find Learning catalogue and Your Library as tabs within MS Teams
  So that I can get an overview of my learning and available resources without leaving the app

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
      | Programming 201       | PGM201    | 0        | It is time to build your own mobile app. |
      | Machine learning 101  | MLN101    | 0        | Learn everything about machine learning, from AlphaGo to SkyNet. |
      | Activity test course  | ATC101    | 0        | |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name | capacity | description |
      | Hall | 100      |             |
    And the following "seminars" exist in "mod_facetoface" plugin:
      | name            | intro        | course  |
      | Cooking seminar | Cook muffins | CUA101  |
    And the following "seminar events" exist in "mod_facetoface" plugin:
      | facetoface      | details |
      | Cooking seminar | event 1 |
      | Cooking seminar | event 2 |
      | Cooking seminar | event 3 |
    And the following "seminar sessions" exist in "mod_facetoface" plugin:
      | eventdetails | rooms | start                 | finish                |
      | event 1      | Hall  | 1 Dec last year 10:00 | 1 Dec last year 12:00 |
      | event 2      | Hall  | 1 Dec last year 13:00 | 1 Dec last year 17:00 |
      | event 3      | Hall  | 1 Feb next year 11:00 | 1 Dec next year 15:00 |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname    | shortname |
      | Junior chef | CP102     |
    And the following "programs" exist in "totara_program" plugin:
      | fullname       | shortname |
      | Mastering arts | P101      |
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
    And the following "activities" exist:
      | activity   | name            | intro                        | course | idnumber    |
      | assign     | Test assignment | Test assignment description  | ATC101 | assign1     |
      | book       | Test book       | Test book description        | ATC101 | book1       |
      | chat       | Test chat       | Test chat description        | ATC101 | chat1       |
      | choice     | Test choice     | Test choice description      | ATC101 | choice1     |
      | data       | Test database   | Test database description    | ATC101 | data1       |
      | feedback   | Test feedback   | Test feedback description    | ATC101 | feedback1   |
      | folder     | Test folder     | Test folder description      | ATC101 | folder1     |
      | forum      | Test forum      | Test forum description       | ATC101 | forum1      |
      | glossary   | Test glossary   | Test glossary description    | ATC101 | glossary1   |
      | imscp      | Test imscp      | Test imscp description       | ATC101 | imscp1      |
      | label      | Test label      | Test label description       | ATC101 | label1      |
      | lesson     | Test lesson     | Test lesson description      | ATC101 | lesson1     |
      | lti        | Test lti        | Test lti description         | ATC101 | lti1        |
      | page       | Test page       | Test page description        | ATC101 | page1       |
      | quiz       | Test quiz       | Test quiz description        | ATC101 | quiz1       |
      | resource   | Test resource   | Test resource description    | ATC101 | resource1   |
      | scorm      | Test scorm      | Test scorm description       | ATC101 | scorm1      |
      | url        | Test url        | Test url description         | ATC101 | url1        |
      | wiki       | Test wiki       | Test wiki description        | ATC101 | wiki1       |
      | workshop   | Test workshop   | Test workshop description    | ATC101 | workshop1   |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | ATC101 | student |
    # Need to fill in the lti's toolurl field without visiting the lti page or behat is blown up :(
    And I log in as "admin"
    And I am on "Activity test course" course homepage with editing mode on
    And I open "Test lti" actions menu
    And I click on "Edit settings" "link" in the "Test lti" activity
    When I set the field "toolurl" to local url "/mod/lti/tests/fixtures/tool_provider.php"
    And I press "Save and display"
    And I log out
    And I log in as "user1"

  Scenario: msteams301: Navigate a course in a custom tab
    Given I am on "Culinary arts 101" "course" custom tab in Microsoft Teams
    Then I should see "Course: Culinary arts 101" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

    When I follow "Cooking seminar"
    Then "Culinary arts 101" "link" should exist in the ".totara_msteams__navigation" "css_element"

    When I click on "Hall" "link"
    Then "Back" "link" should exist in the ".totara_msteams__navigation" "css_element"
    But "Culinary arts 101" "link" should not exist in the ".totara_msteams__navigation" "css_element"

    When I click on "Back" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Culinary arts 101" in the page title
    And ".totara_msteams__navigation" "css_element" should exist

  Scenario: msteams302: Navigate an unenrollable course in a custom tab
    Given I am on "Machine learning 101" "course" custom tab in Microsoft Teams
    Then I should see "MLN101" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"
    And I should see "You can not enrol yourself in this course"
    But "Continue" "button" should not be visible

  Scenario: msteams303: Sign up a seminar from a custom tab
    Given I am on "Culinary arts 101" "course" custom tab in Microsoft Teams

    When I click on "Go to event" "link_or_button" in the "Upcoming" "table_row"
    Then "Culinary arts 101" "link" should exist in the ".totara_msteams__navigation" "css_element"

    When I click on "Hall" "link"
    Then "Back" "link" should exist in the ".totara_msteams__navigation" "css_element"
    But "Culinary arts 101" "link" should not exist in the ".totara_msteams__navigation" "css_element"

    And I press the "back" button in the browser
    When I click on "Sign-up" "button"
    Then "Cancel booking" "link_or_button" should exist
    And "Culinary arts 101" "link" should exist in the ".totara_msteams__navigation" "css_element"

    When I click on "Hall" "link"
    Then "Back" "link" should exist in the ".totara_msteams__navigation" "css_element"
    But "Culinary arts 101" "link" should not exist in the ".totara_msteams__navigation" "css_element"

    When I click on "Back" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Culinary arts 101" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

  Scenario: msteams304: Navigate a program in a custom tab
    Given I am on "Mastering arts" "program" custom tab in Microsoft Teams
    Then I should see "Program: Mastering arts" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

    When I follow "Culinary arts 101"
    And I click on "Back" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Program: Mastering arts" in the page title

    When I follow "Culinary arts 101"
    And I click on "Go to event" "link_or_button" in the "Upcoming" "table_row"
    And I click on "Culinary arts 101" "link" in the ".totara_msteams__navigation" "css_element"
    And I click on "Back" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Program: Mastering arts" in the page title

  Scenario: msteams305: Navigate a certification in a custom tab
    Given I am on "Junior chef" "certification" custom tab in Microsoft Teams
    Then I should see "Program: Junior chef" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

  Scenario: msteams306: Navigate an article in a custom tab
    Given I am on "10 apps to boost your productivity" "article" custom tab in Microsoft Teams
    Then I should see "10 apps to boost your productivity" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

  Scenario: msteams307: Navigate a playlist in a custom tab
    Given I am on "Cool playlist" "playlist" custom tab in Microsoft Teams
    Then I should see "Cool playlist" in the page title
    And ".totara_msteams__navigation" "css_element" should exist
    And I should see "Open in browser" in the ".totara_msteams__navigation__link__label" "css_element"
    And I should see "Logged in as User One." in the ".totara_msteams__navigation__logout__title" "css_element"
    And I should see "Sign out" in the ".totara_msteams__navigation__logout__link" "css_element"

  Scenario: msteams308: Navigate activities in a custom tab
    Given I am on "Activity test course" "course" custom tab in Microsoft Teams
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test assignment"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test book"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test chat"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test choice"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test database"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test feedback"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test folder"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test forum"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test glossary"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test imscp"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test lesson"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test lti"
    Then I should see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test page"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test quiz"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test scorm"
    Then I should not see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test url"
    Then I should see "You are about to be taken to your default browser to view this page"
    When I click on "Cancel" "button"
    Then I should see "Course: Activity test course" in the page title

    When I follow "Test wiki"
    Then I should see "This page is not fully compatible with Microsoft Teams"
    And I click on "Activity test course" "link" in the ".totara_msteams__navigation" "css_element"
    Then I should see "Course: Activity test course" in the page title

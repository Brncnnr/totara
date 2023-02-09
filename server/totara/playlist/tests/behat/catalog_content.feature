@javascript @totara_engage @totara_playlist @totara @totara_catalog @engage
Feature: Playlist catalog content
  As a user
  I need to view playlists on the catalog
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Details content | 0 |
    And I click on "Save" "button"

    And I follow "Filters"
    And I set the field "Add another..." to "Topics"
    And I click on "Save" "button"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | harry    | Harry     | One      | user1@test.com |
      | sally    | Sally     | One      | user1@test.com |
      | user1    | user      | One      | user1@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name                   | username | summary         | access  | topics  |
      | Harry Public Playlist  | harry    | View playlist 1 | PUBLIC  | Topic 1 |
      | Harry Private Playlist | harry    | View playlist 2 | PRIVATE | Topic 1 |
      | Sally Public Playlist  | sally    | View playlist 3 | PUBLIC  | Topic 1 |
      | Sally Private Playlist | sally    | View playlist 4 | PRIVATE | Topic 1 |
      | User public Playlist1  | user1    | View playlist 5 | PUBLIC  | Topic 2 |
      | User public Playlist2  | user1    | View playlist 6 | PUBLIC  | Topic 2 |

    And I log out

  Scenario: Test viewing a playlist on the catalog
    Given I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Public Playlist"
    And I should see "Harry Private Playlist"
    And I should see "Sally Public Playlist"
    And I should not see "Sally Private Playlist"

    When I click on "Sally Public Playlist" "text"
    Then I should see "Sally Public Playlist"
    And I should see "View playlist 3"

    When I log out
    And I log in as "sally"
    And I click on "Find Learning" in the totara menu
    Then I should see "Sally Public Playlist"
    And I should see "Sally Private Playlist"
    And I should see "Harry Public Playlist"
    And I should not see "Harry Private Playlist"

  Scenario: Test that playlists cannot be seen on the catalog when advanced features are disabled
    Given I enable the "engage_resources" advanced feature
    And I log in as "harry"
    And I click on "Find Learning" in the totara menu
    Then I should see "Harry Public Playlist"

    When I disable the "engage_resources" advanced feature
    And I click on "Find Learning" in the totara menu
    Then I should not see "Harry Public Playlist"
    And I should not see "Playlists" in the ".tw-catalog__aside" "css_element"

  Scenario: Filter playlist catalog by topic
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "User public Playlist1" "link" in the ".tui-sidePanel__content" "css_element"
    And I click on "Side panel" "button"
    When I click on "Topic 2" "link"
    Then I should see "User public Playlist1"
    And I should see "User public Playlist2"
    And I should not see "Harry Public Playlist"
    And I should not see "Sally Private Playlist"
    And I should not see "Sally Public Playlist"
    And I should not see "Harry Private Playlist"

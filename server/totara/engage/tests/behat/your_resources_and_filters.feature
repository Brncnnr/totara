@javascript @totara_engage @totara @engage
Feature: Your resources and filters
  As a user
  I want to use the provided library filters
  So that I can get the specific resources I'm looking for

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
      | Topic2 |
      | Topic3 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content       | access     | topics |
      | Test Article 1 | user1    | Test Filters  | PUBLIC     | Topic1 |
      | Test Article 2 | user1    | Test Filters  | PRIVATE    | Topic1 |
      | Test Article 3 | user1    | Test Filters  | RESTRICTED | Topic3 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | content | access     | topics |
      | Test Survey 1? | user1    | blah    | PUBLIC     | Topic3 |
      | Test Survey 2? | user1    | blah    | PRIVATE    |        |
      | Test Survey 3? | user1    | blah    | RESTRICTED | Topic2 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics |
      | Test Playlist 1 | user1    | PUBLIC | Topic1 |

  Scenario: Default filter should show every resource
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu

    # No filter
    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Filter your contributions by visibility
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu

    # Public resources
    When I select "Public (anyone can see and share this content)" from the "filter_access" singleselect
    And I wait for the next second

    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And "span[aria-label='0 like(s)']" "css_element" should exist
    And "span[aria-label='0 comment(s)']" "css_element" should exist
    And "span[aria-label='0 share(s)']" "css_element" should exist
    And "span[aria-label='Appears in 0 playlist(s)']" "css_element" should exist
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

    # Private resources
    When I select "Hidden (only you)" from the "filter_access" singleselect
    And I wait for the next second

    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And "span[aria-label='0 like(s)']" "css_element" should not exist
    And "span[aria-label='0 comment(s)']" "css_element" should not exist
    And "span[aria-label='0 share(s)']" "css_element" should not exist
    And "span[aria-label='Appears in 0 playlist(s)']" "css_element" should not exist
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

    # Restricted resources
    When I select "Limited (only people and workspaces you share to)" from the "filter_access" singleselect
    And I wait for the next second

    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And "span[aria-label='0 like(s)']" "css_element" should exist
    And "span[aria-label='0 comment(s)']" "css_element" should exist
    And "span[aria-label='0 share(s)']" "css_element" should not exist
    And "span[aria-label='Appears in 0 playlist(s)']" "css_element" should not exist
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Filter your resources by type
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu

    # Resource filter
    When I select "Resource" from the "filter_type" singleselect
    And I wait for the next second

    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

    # Survey filter
    When I select "Survey" from the "filter_type" singleselect
    And I wait for the next second

    Then I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Filter your resources by topic
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu

    # Topic 1
    When I select "Topic1" from the "filter_topic" singleselect
    And I wait for the next second

    Then I should see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

    # Topic 2
    When I select "Topic2" from the "filter_topic" singleselect
    And I wait for the next second

    Then I should see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"

    # Topic 3
    When I select "Topic3" from the "filter_topic" singleselect
    And I wait for the next second

    Then I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
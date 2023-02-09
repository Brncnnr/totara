@javascript @totara_engage @totara @engage
Feature: Shared with you and filters
  As a user
  I want to use the provided library filters
  So that I can get the specific resources I'm looking for

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
      | user2    | User      | Two      | user2@test.com |
      | user3    | User      | Three    | user3@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |
      | Topic2 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access     | topics |
      | Test Article 1 | user1    | blah    | PUBLIC     | Topic1 |
      | Test Article 2 | user1    | blah    | PRIVATE    | Topic2 |
      | Test Article 3 | user1    | blah    | RESTRICTED | Topic2 |

    And "engage_article" "Test Article 3" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

    And the following "surveys" exist in "engage_survey" plugin:
      | question       | username | access     | topics |
      | Test Survey 1? | user1    | PUBLIC     | Topic1 |
      | Test Survey 2? | user1    | PRIVATE    | Topic2 |
      | Test Survey 3? | user1    | RESTRICTED | Topic2 |

    And "engage_survey" "Test Survey 2?" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access     | topics |
      | Test Playlist 1 | user1    | PUBLIC     | Topic1 |
      | Test Playlist 2 | user1    | PRIVATE    | Topic2 |
      | Test Playlist 3 | user1    | RESTRICTED | Topic2 |

    And "totara_playlist" "Test Playlist 1" is shared with the following users:
      | sharer | recipient |
      | user1  | user2     |

  Scenario: Default filter should show all shared resources and playlists
    Given I log in as "user2"
    And I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"

    # No filter
    Then I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Filter shares by type
    Given I log in as "user2"
    And I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"

    # Resource filter
    When I select "Resource" from the "filter_type" singleselect
    And I wait for the next second

    Then I should see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

    # Survey filter
    When I select "Survey" from the "filter_type" singleselect
    And I wait for the next second

    Then I should see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

    # Playlist filter
    When I select "Playlist" from the "filter_type" singleselect
    And I wait for the next second

    Then I should see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"

  Scenario: Log in as different user to confirm no shares
    Given I log in as "user3"
    And I click on "Your Library" in the totara menu
    And I click on "Shared with you" "link" in the ".tui-engageNavigationPanel__menu" "css_element"

    # No shares
    Then I should not see "Test Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Article 3" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 1?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 2?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Survey 3?" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 2" in the ".tui-contributionBaseContent__cards" "css_element"
    And I should not see "Test Playlist 3" in the ".tui-contributionBaseContent__cards" "css_element"
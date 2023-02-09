@engage @block @javascript @totara @block_totara_recently_viewed
Feature: Don't show resource, survey & playlist cards when engage_resources advanced feature is not enabled

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature

    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name   |
      | Topic1 |

    And the following "articles" exist in "engage_article" plugin:
      | name           | username | content | access | topics |
      | Test Article 1 | user1    | blah    | PUBLIC | Topic1 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics |
      | Test Playlist 1 | user1    | PUBLIC | Topic1 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question      | username | access | topics |
      | Test Survey 1 | user1    | PUBLIC | Topic1 |

    And "engage_survey" "Test Survey 1" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

    And the following "courses" exist:
      | fullname      | shortname | category |
      | Test Course 1 | C1        | 0        |

  Scenario: Resource/Survey/Playlist cards should not show when resources advanced feature is disabled
    Given I log in as "admin"
    # Populate our recently viewed list
    And I view playlist "Test Playlist 1"
    And I view article "Test Article 1"
    And I click on "Find Learning" in the totara menu
    And I click on "div[title=\"Test Course 1\"]" "css_element"
    And I click on "Your Library" in the totara menu
    And I set the field "Search your library" to "Test Survey 1"
    And I press "Search your library"
    And I wait for the next second
    And I click on "Vote" "link"
    And I am on "Dashboard" page
    And I click on "Customise this page" "button"
    And I add the "Recently viewed" block

    # We see them
    When I configure the "Recently viewed" block
    And I set the following fields to these values:
      | Display type    | List |
      | Number of items | 4    |
    And I press "Save changes"
    Then I should see "Test Survey 1" in the "Recently viewed" "block"
    And I should see "Test Course 1" in the "Recently viewed" "block"
    And I should see "Test Article 1" in the "Recently viewed" "block"
    And I should see "Test Playlist 1" in the "Recently viewed" "block"

    # Now we don't
    When I disable the "engage_resources" advanced feature
    And I am on "Dashboard" page
    Then I should not see "Test Survey 1" in the "Recently viewed" "block"
    And I should see "Test Course 1" in the "Recently viewed" "block"
    And I should not see "Test Article 1" in the "Recently viewed" "block"
    And I should not see "Test Playlist 1" in the "Recently viewed" "block"
@javascript @totara_engage @totara_playlist @totara @engage
Feature: Bookmark playlist resources
  As a user
  I need to bookmark a resource withing a playlist
  So that I can easily navigate to it in the future

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@test.com |
      | user2    | User      | Two      | user2@test.com |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name       | username | access | topics  |
      | Playlist 1 | user1    | PUBLIC | Topic 1 |

    And the following "articles" exist in "engage_article" plugin:
      | name      | username | content | access | topics  |
      | Article 1 | user1    | A1      | PUBLIC | Topic 1 |

    And the following "surveys" exist in "engage_survey" plugin:
      | question | username | access | topics  |
      | Survey 1 | user1    | PUBLIC | Topic 1 |

    And the following "playlist resources" exist in "totara_playlist" plugin:
      | component      | name      | playlist   | user  |
      | engage_article | Article 1 | Playlist 1 | user1 |
      | engage_survey  | Survey 1  | Playlist 1 | user1 |

    And "totara_playlist" "Playlist 1" is shared with the following users:
      | sharer | recipient |
      | user1  | admin     |

  Scenario: Test bookmarking resources in grid as an authenticated user
    Given I log in as "user2"
    And I view playlist "Playlist 1"
    Then "Bookmark" "button" should exist in the ".tui-engageSurveyCard__header" "css_element"
    And "Bookmark" "button" should exist in the ".tui-engageArticleCard__header" "css_element"

  Scenario: Guest should not be able to bookmark resources in grid as an authenticated user
    Given I log in as "admin"
    And the following "permission overrides" exist:
      | capability                | permission | role  | contextlevel | reference |
      | totara/engage:viewlibrary | Allow      | guest | User         | guest     |
    And I set the following administration settings values:
      | Guest login button | Show |
    When I log out
    And I am on homepage
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I view playlist "Playlist 1"
    Then "Bookmark" "button" should not exist in the ".tui-engageSurveyCard__header" "css_element"
    And "Bookmark" "button" should not exist in the ".tui-engageArticleCard__header" "css_element"
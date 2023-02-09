@totara @totara_playlist @engage @totara_engage
Feature: Remove resource from playlist
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user1    | User      | One      | one@example.com |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name         | username | access  |
      | Playlist 101 | user1    | PRIVATE |

  @javascript
  Scenario: User remove resource from playlist
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    When I follow "Playlist 101"
    Then I should see "Playlist 101"
    When I click on "Contribute" "button" in the ".tui-addNewPlaylistCard__card" "css_element"
    And I set the field "Enter resource title" to "Article 1"
    And I activate the weka editor with css ".tui-engageArticleForm__description-formRow"
    And I type "Create article" in the weka editor
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-accessSelector" "css_element"
    And I click on "Done" "button"
    Then I should see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    When I click on "Remove from playlist" "button"
    And I wait for the next second
    Then I should not see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
    And I click on "Your resources" "link"
    Then I should see "Article 1" in the ".tui-contributionBaseContent__cards" "css_element"
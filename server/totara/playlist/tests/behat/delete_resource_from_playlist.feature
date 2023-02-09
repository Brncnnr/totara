@totara @totara_engage @totara_playlist @javascript @engage
Feature: Delete resource from playlist
  As a user
  I need to delete a resource from my playlist
  So I can keep my library clean

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
      | Topic 2 |

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@example.com |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name            | username | access | topics           |
      | Test Playlist 1 | user1    | PRIVATE | Topic 1, Topic 2 |

  Scenario: Delete resource from a playlist
    # First add the resource to the playlist
    Given I log in as "user1"
    And I view playlist "Test Playlist 1"
    And I press "Contribute"
    And I set the field "Enter resource title" to "Test Resource 1"
    And I set the field with xpath "//*[contains(concat(' ', normalize-space(@class), ' '), ' tui-engageArticleForm__description ')]//div[@contenteditable='true']" to "Resource 1 Content"
    And I wait for the next second
    And I click on "Next" "button"
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-engageAccessForm" "css_element"
    And I click on "Done" "button" in the ".tui-engageAccessForm__buttons" "css_element"
    And I should see "Test Resource 1"

    # Delete the resource
    Then I view article "Test Resource 1"
    And I click on ".tui-iconBtn" "css_element" in the ".tui-dropdown" "css_element"
    And I should see "Delete"
    And I click on ".tui-dropdown__content" "css_element"
    And I confirm the tui confirmation modal
    Then I should not see "Test Article 1"
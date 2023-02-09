@totara @totara_engage @totara_playlist @javascript @engage
Feature: Create a playlist with a banner
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User1      | One      | user1@test.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

  Scenario: Create a playlist and populate the image
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I click on "Contribute playlist" "button"
    And I set the field "Playlist title" to "TestPlaylist"
    And I activate the weka editor with css ".tui-playlistForm"
    And I type "Test Image" in the weka editor
    And I wait for the next second
    And I press "Next"
    And I press "Done"

    # Check it has the default image
    When I click on "Your Library" in the totara menu
    And I set the field "Search your library" to "TestPlaylist"
    And I press "Search your library"
    Then "//div[@class='tui-playlistCard__imageHeader' and contains(@style, 'background-image') and contains(@style, 'default_collection')]" "xpath_element" should exist
    And I should see "TestPlaylist" in the ".tui-playlistCard__imageHeader .sr-only" "css_element"

    # Now go add a resource with an image
    When I view playlist "TestPlaylist"
    Then I press "Contribute"
    And I set the field "article-title" to "TestArticle"
    And I activate the weka editor with css ".tui-engageCreateArticle"
    And I type "Test Image" in the weka editor
    And I select the text "Test Image" in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://test.totaralms.com/exttests/test.jpg"
    And I click on "Done" "button" in the ".tui-modal ~ .tui-modal" "css_element"
    And I click on "Test Image" "link"
    And I click on "Display as embedded media" "button"
    And I wait for the next second
    And I press "Next"
    And I press "Done"

    # Now go back to the playlist
    Then I click on "Your Library" in the totara menu
    And I set the field "Search your library" to "TestPlaylist"
    And I press "Search your library"
    Then "//div[@class='tui-playlistCard__imageHeader' and contains(@style, 'background-image') and contains(@style, 'default_collection')]" "xpath_element" should not exist
    And "//div[@class='tui-playlistCard__imageHeader' and contains(@style, 'background-image') and contains(@style, '/card.png')]" "xpath_element" should exist
    And I should see "TestPlaylist" in the ".tui-playlistCard__imageHeader .sr-only" "css_element"

    # Now delete the resource
    When I view article "TestArticle"
    And I click on ".tui-iconBtn" "css_element" in the ".tui-dropdown" "css_element"
    And I should see "Delete"
    And I click on ".tui-dropdown__content" "css_element"
    And I confirm the tui confirmation modal
    And I click on "Your Library" in the totara menu
    And I set the field "Search your library" to "TestPlaylist"
    And I press "Search your library"

    # Back to the default image
    Then "//div[@class='tui-playlistCard__imageHeader' and contains(@style, 'background-image') and contains(@style, '/default_collection'')]" "xpath_element" should not exist
    And "//div[@class='tui-playlistCard__imageHeader' and contains(@style, 'background-image') and contains(@style, '/card.png')]" "xpath_element" should not exist
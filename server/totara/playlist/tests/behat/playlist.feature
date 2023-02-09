@totara @totara_playlist @engage @totara_engage
Feature: Manipulate playlist instance
  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"
    And I enable the "engage_resources" advanced feature

    And the following "users" exist:
      | username | firstname | lastname | email           |
      | userone  | User      | One      | one@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

    And the following "playlists" exist in "totara_playlist" plugin:
      | name         | username | access     | topics  |
      | Playlist 101 | userone  | PRIVATE    |         |
      | Playlist 102 | userone  | RESTRICTED | Topic 1 |
      | Playlist 103 | userone  | PUBLIC     | Topic 1 |

  @javascript
  Scenario: User edit the playlist's summary and make private playlist to public playlist
    Given I log in as "userone"
    And I click on "Your Library" in the totara menu
    And I follow "Playlist 101"
    Then I should see "Playlist 101"

    When I click on "Side panel" "button"
    And I click on "//button[@title='Add a description (optional)']/parent::*" "xpath_element"
    And I activate the weka editor with css ".tui-playlistSummary__editor"
    And I type "Edit playlist summary" in the weka editor
    And I wait for the next second
    And I click on "Done" "button"
    Then I should see "Edit playlist summary"

    When I click on "Share" "button"
    Then I should see "Content visibility settings"
    And I should see "Hidden (only you)"
    And I should see "Public (anyone can see and share this content)"
    And I should see "Limited (only people and workspaces you share to)"

    When I click on "Public (anyone can see and share this content)" "text" in the ".tui-accessSelector" "css_element"
    And I click on "Tag list" "button" in the ".tui-topicsSelector" "css_element"
    And I click on "Topic 1" option in the dropdown menu
    And I click on "Tag list" "button" in the ".tui-engageSharedRecipientsSelector" "css_element"
    Then the "Done" "button" should be enabled

    When I click on "Done" "button"
    Then I should see "Everyone can view" in the ".tui-engageAccessDisplay__accessIcon-icons" "css_element"

  @javascript
  Scenario: User views restricted playlist and public playlist
    #View restricted playlist
    Given I log in as "userone"
    And I click on "Your Library" in the totara menu
    And I follow "Playlist 102"
    Then I should see "Playlist 102"
    When I click on "Side panel" "button"
    Then I should not see "Reshare"

    # View public playlist
    When I follow "Playlist 103"
    Then I should see "Playlist 103"
    And I click on "Share" "button" in the ".tui-accessSetting" "css_element"
    Then I should see "Settings" in the ".tui-modalContent__header-title" "css_element"

  @javascript
  Scenario: Creator view a private playlist
    Given I log in as "userone"
    And I click on "Your Library" in the totara menu
    And I follow "Playlist 101"
    Then I should see "Playlist 101"
    When I click on "Side panel" "button"
    Then I should not see "0 rating"

  @javascript
  Scenario: Creator view a public playlist
    Given I log in as "userone"
    And I click on "Your Library" in the totara menu
    And I follow "Playlist 102"
    Then I should see "Playlist 102"
    When I click on "Side panel" "button"
    Then I should see "rating"

  @javascript
  Scenario: Creator view a restricted playlist
    Given I log in as "userone"
    And I click on "Your Library" in the totara menu
    And I follow "Playlist 103"
    Then I should see "Playlist 103"
    When I click on "Side panel" "button"
    Then I should see "rating"

  @javascript
  Scenario: User can not view share button and edit settings
    Given I log in as "userone"
    When I view playlist "Playlist 101"
    Then I should see "Share"
    And I should see "Edit settings"
    When I view playlist "Playlist 102"
    Then I should see "Share"
    And I should see "Edit settings"
    When I view playlist "Playlist 103"
    Then I should see "Share"
    And I should see "Edit settings"
    And I log out

    And I log in as "admin"
    When I set the following system permissions of "Authenticated User" role:
      | totara/playlist:share   | Prohibit |
    Then I log out

    And I log in as "userone"
    When I view playlist "Playlist 101"
    Then I should not see "Share"
    And I should not see "Edit settings"
    And I should not see "Reshare"
    When I view playlist "Playlist 102"
    Then I should not see "Share"
    And I should not see "Edit settings"
    And I should not see "Reshare"
    When I view playlist "Playlist 103"
    Then I should not see "Share"
    And I should not see "Edit settings"
    And I should not see "Reshare"
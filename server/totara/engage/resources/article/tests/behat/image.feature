@javascript @totara_engage @engage_article @totara @engage
Feature: Custom article images
  As a user
  I need my article's banner image to be picked from content
  So that it shows distinctly in catalog listings

  Background:
    Given I am on a totara site
    And I set the site theme to "ventura"

    And the following "users" exist:
      | username | firstname | lastname | email          |
      | user1    | User      | One      | user1@example.com |

    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |

  Scenario: Articles without images will use the default svg
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I press "Contribute"
    And I set the field "Enter resource title" to "DefaultResource"
    And I activate the weka editor with css ".tui-engageCreateArticle"
    And I type "No Image" in the weka editor
    And I wait for the next second
    And I press "Next"
    And I wait for pending js
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-engageAccessForm" "css_element"
    And I press "Done"
    And I click on "Your Library" in the totara menu
    Then "//div[@class='tui-engageArticleCard__image' and @role='presentation' and contains(@style, 'background-image') and contains(@style, '/default')]" "xpath_element" should exist

  Scenario: Articles with an external image will use it
    Given I log in as "user1"
    And I click on "Your Library" in the totara menu
    And I press "Contribute"
    And I set the field "Enter resource title" to "ImageResource"
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
    And I wait for the next second
    And I click on "Hidden (only you)" "text" in the ".tui-engageAccessForm" "css_element"
    And I press "Done"
    And I click on "Your Library" in the totara menu
    And I wait for the next second
    Then "//div[@class='tui-engageArticleCard__image' and @role='presentation' and contains(@style, 'background-image') and contains(@style, '_test.jpg')]" "xpath_element" should exist
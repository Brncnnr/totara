@totara @javascript @editor @editor_weka @weka @vuejs
Feature: Test basic Weka functionality
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email           |
      | user_one | User      | One      | one@example.com |

  Scenario: Create resource
    And I log in as "admin"
    And I navigate to the "weka_basic" fixture in the "lib/editor/weka" plugin
    And I set the weka editor with css ".tui-fixture-wekaBasic" to "First sample content"
    And I set the weka editor with css ".tui-fixture-wekaBasic" to "Second sample content "
    And I activate the weka editor with css ".tui-fixture-wekaBasic"
    And I click on the "Bold" toolbar button in the weka editor
    And I type "yee-haw" in the weka editor
    And I click on the "Bold" toolbar button in the weka editor
    And I type "what" in the weka editor
    And I select the text "haw" in the weka editor
    And I click on the "Bold" toolbar button in the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://test.totaralms.com/exttests/test.jpg"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I click on "haw" "link"
    And I click on "Display as embedded media" "button"
    And I select the "test.jpg" "linkMedia" in the weka editor
    And I delete the selected node in the weka editor
    And I move the cursor to the end of the weka editor
    And I click on the "Link" toolbar button in the weka editor
    And I set the field "URL" to "https://test.totaralms.com/exttests/test.jpg"
    And I click on "Done" "button" in the ".tui-modal" "css_element"
    And I activate the menu of the "test.jpg" "linkMedia" in the weka editor
    And I click on "Display as text" "button"
    And I click on "test.jpg" "link"
    And I click on "Display as embedded media" "button"
    And I press enter in the weka editor
    And I click on the block menu in the weka editor
    And I click on "Heading" "menuitem"
    And I type "This is a heading" in the weka editor
    And I press backspace in the weka editor
    And I press enter in the weka editor
    And I type "Hello @use" in the weka editor
    And I click on "User One" "menuitem"
    And I should see "User One" in the weka editor
    And I should see "is a headin" in the weka editor
    And I should not see "is a heading" in the weka editor
    And I should see "test.jpg" "linkMedia" in the weka editor
    And I should not see "foo.jpg" "linkMedia" in the weka editor
    And I should see "User One" "mention" in the weka editor
    And I press enter in the weka editor
    And I type "Give me a #hashtag" in the weka editor
    And I press enter in the weka editor
    And I click on "#hashtag" "link"
    Then I should see "View search results"
    And "a[href$='index.php?catalog_fts=hashtag']" "css_element" should exist

  Scenario: Replace selection
    And I log in as "admin"
    And I navigate to the "weka_basic" fixture in the "lib/editor/weka" plugin
    When I set the weka editor with css ".tui-fixture-wekaBasic" to "uno dos tres!"
    Then I should see "uno dos tres!" in the ".tui-fixture-wekaBasic" "css_element"
    When I activate the weka editor with css ".tui-fixture-wekaBasic"
    And I select the text "tres" in the weka editor
    And I replace the selection with "cuatro" in the weka editor
    Then I should see "uno dos cuatro!" in the ".tui-fixture-wekaBasic" "css_element"

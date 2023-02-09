@core @theme @theme_ventura @javascript
Feature: Theme settings basic validations
  Theme settings should work as expected
  As a user
  I need to confirm that I can navigate to theme settings and see all the different elements

  Background:
    Given I log in as "admin"
    And I am on site homepage
    And I navigate to "Ventura" node in "Site administration > Appearance > Themes"

  Scenario: Confirm Brand tab has all the required elements
    When I click on "Brand" "link"
    Then I should see "Logo" in the ".tui-tabContent" "css_element"
    And the URL for image nested in ".tui-form .tui-formRow:nth-child(1)" should match "/theme\/image.php\/ventura\/totara_core\/[0-9]+\/logo/"
    And I should see "Logo alternative text" in the ".tui-tabContent" "css_element"
    And I should see "Favicon" in the ".tui-tabContent" "css_element"
    And the URL for image nested in ".tui-form .tui-formRow:nth-child(3)" should match "/theme\/image.php\/ventura\/theme\/[0-9]+\/favicon/"

    # Confirm that notification settings are present
    When I click on "Email notifications" "button"
    Then I should see "HTML header" in the ".tui-tabContent" "css_element"
    And I should see "HTML footer" in the ".tui-tabContent" "css_element"
    And I should see "Plain-text footer" in the ".tui-tabContent" "css_element"

    # Confirm that email gets sent
    When I reset the email sink
    And I click on "Test email notification" "button"
    Then the following emails should have been sent:
      | To                 | Subject        | Body              |
      | moodle@example.com | This is a test | Body of the email |

  Scenario: Confirm Colours tab has all the required elements
    When I click on "Colours" "link"
    And I click on "More colours" "button"
    Then the field "Primary brand colour" matches value "#4b7e2b"
    Then the field "Accent colour" matches value "#69bd45"
    And the field "Header background colour" matches value "#ffffff"
    And the field "Header text colour" matches value "#262626"
    And the field "Page text colour" matches value "#262626"

  Scenario: Confirm Images tab has all the required elements
    When I click on "Images" "link"
    And the field "Display login page image" matches value "1"
    And the field "Login alternative text" matches value "Totara Login"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(1) .tui-formRow:nth-child(2)" should match "/theme\/image.php\/ventura\/totara_core\/[0-9]+\/default_login/"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(2) .tui-formRow:nth-child(1)" should match "/theme\/image.php\/ventura\/core\/[0-9]+\/course_defaultimage/"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(2) .tui-formRow:nth-child(2)" should match "/theme\/image.php\/ventura\/totara_program\/[0-9]+\/defaultimage/"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(2) .tui-formRow:nth-child(3)" should match "/theme\/image.php\/ventura\/totara_certification\/[0-9]+\/defaultimage/"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(3) .tui-formRow:nth-child(1)" should match "/theme\/image.php\/ventura\/engage_article\/[0-9]+\/default/"
    And the URL for image nested in "#tabpanel-uid-3 .tui-collapsible:nth-child(3) .tui-formRow:nth-child(2)" should match "/theme\/image.php\/ventura\/container_workspace\/[0-9]+\/default_space/"

  Scenario: Confirm Custom tab has all the required elements
    When I click on "Custom" "link"
    Then I should see "Custom footer" in the ".tui-tabContent:nth-of-type(4)" "css_element"
    And I should see "Custom CSS" in the ".tui-tabContent:nth-of-type(4)" "css_element"

  Scenario: Confirm that when entering custom text in the footer that it displays
    When I click on "Custom" "link"
    And I set the field "Custom footer" to "Behat Test 123"
    And I click on "Save Custom Settings" "button"
    And I reload the page
    Then I should see "Behat Test 123" in the ".footnote" "css_element"

  Scenario: Confirm that when entering custom HTML in the footer that it renders correctly.
    When I click on "Custom" "link"
    And I set the field "Custom footer" to "<div id='behat_test_123'>Behat Test 123</div>"
    And I click on "Save Custom Settings" "button"
    And I reload the page
    Then I should see "Behat Test 123" in the "#behat_test_123" "css_element"

  # XSS test - which it should strip out the bad <script> tag content.
  Scenario: Custom HTML with XSS protection in the footer.
    When I click on "Custom" "link"
    And I set the field "Custom footer" to "<div id='bomba_test_this'>Makag latatag, normalin</div><script id='42_meaning_of_every_thing'>alert('hi there');</script>"
    And I click on "Save Custom Settings" "button"
    And I reload the page
    Then I should see "Makag latatag, normalin" in the "#bomba_test_this" "css_element"
@totara @totara_form @javascript @editor_weka @editor @vuejs
Feature: Totara form weka editor test
  Render the weka editor as the default editor for a user
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I am on a totara site
    And I log in as "admin"

  Scenario: Test if plain text area is rendered
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Plain text area"
    And I press "Save changes"

    When I navigate to the Totara test form
    Then I should see "Form acceptance testing page"

    When I select "User preferred editor element [totara_form\form\testform\element_editor_user_preferred]" from the "Test form" singleselect
    Then I should see "Form: User preferred editor element"
    And ".tui-weka" "css_element" should not exist in the "#tfiid_userpreferrededitor_sectiontotara_form_form_testform_element_editor_user_preferred" "css_element"
    And I should see the following Totara form fields having these values:
      | User preferred editor | |

    When I set the following Totara form fields to these values:
      | User preferred editor | Test |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "userpreferrededitor" row "Value" column of "form_results" table should contain "«Test»"
    And "userpreferrededitorformat" row "Value" column of "form_results" table should contain "«1»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_editor_user_preferred»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test if weka editor is rendered
    Given I open my profile in edit mode
    And I follow "Preferences" in the user menu
    And I follow "Editor preferences"
    And I set the field "Text editor" to "Weka"
    And I press "Save changes"

    When I navigate to the Totara test form
    And I should see "Form acceptance testing page"

    When I select "User preferred editor element [totara_form\form\testform\element_editor_user_preferred]" from the "Test form" singleselect
    And I wait for pending js
    Then I should see "Form: User preferred editor element"
    And ".tui-weka" "css_element" should exist in the "#tfiid_userpreferrededitor_sectiontotara_form_form_testform_element_editor_user_preferred" "css_element"

    When I activate the weka editor with css "#tfiid_userpreferrededitor_sectiontotara_form_form_testform_element_editor_user_preferred"
    And I type "Test" in the weka editor
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "userpreferrededitor" row "Value" column of "form_results" table should contain '«{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Test"}]}]}»'
    And "userpreferrededitorformat" row "Value" column of "form_results" table should contain "«5»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_editor_user_preferred»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
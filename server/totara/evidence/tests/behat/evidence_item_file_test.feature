@totara @perform @totara_evidence @editor @editor_atto @atto @atto_image @_file_upload @javascript
Feature: Evidence item file picker and text area upload

  Scenario: Upload some files to file picker and text area
    # Create type
    Given the following "types" exist in "totara_evidence" plugin:
      | name      | user     | fields | description |
      | File_test | admin    | 0      | One         |
    When I log in as "admin"
    And I navigate to "Evidence > Manage types" in site administration
    And I click on "Edit" "link" in the "File_test" "table_row"
    And I set the field "Create a new custom field" to "File"
    And I set the following fields to these values:
      | Full name                   | Files |
      | Short name (must be unique) | Files |
    And I click on "Save changes" "button"
    And I set the field "Create a new custom field" to "Text area"
    And I set the following fields to these values:
      | Full name                   | Textarea |
      | Short name (must be unique) | Textarea |
    And I click on "Save changes" "button"
    And I navigate to my evidence bank
    And I click on "Add evidence" "link"
    And I expand the evidence type selector
    And I select type "File_test" from the evidence type selector
    And I click on "Use this type" "link"

    # Upload files to file element
    When I upload "totara/evidence/tests/behat/fixtures/pic1.png" file to "Files" filemanager
    Then I should see "1" elements in "Files" filemanager
    And I should see "pic1.png" in the "div.fp-content" "css_element"
    When I upload "totara/evidence/tests/behat/fixtures/text1.txt" file to "Files" filemanager
    Then I should see "2" elements in "Files" filemanager
    And I should see "text1.txt" in the "div.fp-content" "css_element"
    And I click on "Save evidence item" "button"
    And I click on "File_test" "link"
    And I click on "Edit this item" "link"

    # Put image in text area
    When I click on "button.atto_image_button" "css_element" in the "#fitem_id_customfield_Textarea_editor" "css_element"
    And I click on "Browse repositories..." "button"
    And I click on "//div[@class='fp-repo-items']//a[contains(.,'pic1.png')]" "xpath_element"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "textarea_image"
    And I set the following fields to these values:
      | Width  | 100 |
      | Height | 100 |
    And I click on "Save image" "button"
    And I click on "Save changes" "button"

    # View item and then swap files
    Then I should see image with alt text "textarea_image"
    And I should see "pic1.png" in the "Files" evidence item field
    And I should see "text1.txt" in the "Files" evidence item field
    And following "pic1.png" should download "12302" bytes
    And following "text1.txt" should download "2801" bytes
    When I click on "Edit this item" "link"
    Then I should see "2" elements in "Files" filemanager
    And I should see image with alt text "textarea_image"
    And I should see "pic1.png" in the "#fitem_id_customfield_Files_filemanager" "css_element"
    And I should see "text1.txt" in the "#fitem_id_customfield_Files_filemanager" "css_element"
    When I click on "text1.txt" "link"
    And I click on "Delete" "button"
    And I click on "OK" "button"
    Then I should see "1" elements in "Files" filemanager
    And I should not see "text1.txt" in the "div.fp-content" "css_element"
    When I upload "totara/evidence/tests/behat/fixtures/text2.txt" file to "Files" filemanager
    Then I should see "2" elements in "Files" filemanager
    And I should see "text2.txt" in the "div.fp-content" "css_element"

    # Make sure files are visible in a new session
    When I click on "Save changes" "button"
    And I log out
    And I log in as "admin"
    And I navigate to my evidence bank
    And I click on "File_test" "link"
    Then following "text2.txt" should download "1968" bytes
    When I click on "Edit" "link"
    Then I should see "2" elements in "Files" filemanager
    And I should see "text2.txt" in the "div.fp-content" "css_element"
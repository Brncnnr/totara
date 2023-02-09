<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @package contentmarketplace_linkedin
-->

<template>
  <Popover
    class="tui-linkedInImportCategoryPopover"
    size="md"
    :triggers="['click']"
    @open-changed="resetCategoryForm"
  >
    <template v-slot:trigger="{ isOpen }">
      <EditIconButton
        :aria-expanded="isOpen.toString()"
        :aria-label="
          $str('edit_course_category', 'contentmarketplace_linkedin')
        "
        class="tui-linkedInImportCategoryPopover__icon"
        :disabled="disabled"
        :size="100"
      />
    </template>

    <div class="tui-linkedInImportCategoryPopover__edit">
      <!-- Category select and label -->
      <Label
        :for-id="$id('category' + courseId)"
        :label="$str('assign_to_category', 'contentmarketplace_linkedin')"
      />

      <Select
        :id="$id('category' + courseId)"
        v-model="selectedPopoverCategory"
        char-length="15"
        :options="categoryOptions"
      />
    </div>

    <!-- Popover buttons -->
    <template v-slot:buttons="{ close }">
      <Button
        :styleclass="{ primary: true, small: true }"
        :text="$str('update', 'contentmarketplace_linkedin')"
        @click="updateCategory(courseId, close)"
      />
      <Button
        :styleclass="{ small: true }"
        :text="$str('cancel', 'core')"
        @click="close"
      />
    </template>
  </Popover>
</template>

<script>
// Components
import Button from 'tui/components/buttons/Button';
import EditIconButton from 'tui/components/buttons/EditIcon';
import Label from 'tui/components/form/Label';
import Popover from 'tui/components/popover/Popover';
import Select from 'tui/components/form/Select';

export default {
  components: {
    Button,
    EditIconButton,
    Label,
    Popover,
    Select,
  },

  props: {
    // Available category options
    categoryOptions: Array,
    // Course Id
    courseId: [String, Number],
    // Currently selected category option
    currentCategory: [String, Number],
    // Disabled
    disabled: Boolean,
  },

  data() {
    return {
      // Value of selected popover category
      selectedPopoverCategory: this.currentCategory,
    };
  },

  methods: {
    /**
     * On closing of popover, reset select value
     *
     * @param {Boolean} opening
     */
    resetCategoryForm(opening) {
      if (!opening) {
        this.selectedPopoverCategory = this.currentCategory;
      }
    },

    /**
     * Emit String and value of selected option and close popover
     *
     * @param {Number} id
     * @param {Function} close
     */
    updateCategory(id, close) {
      this.$emit('change-course-category', {
        courseId: id,
        value: this.selectedPopoverCategory,
      });
      close();
    },
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "assign_to_category",
      "edit_course_category",
      "update"
    ],
    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-linkedInImportCategoryPopover {
  line-height: 1;

  &__edit {
    margin-top: var(--gap-1);
    & > * + * {
      margin-top: var(--gap-2);
    }
  }
}
</style>

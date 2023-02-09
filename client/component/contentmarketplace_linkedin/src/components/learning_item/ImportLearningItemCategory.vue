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
  <!-- Display currently assigned category -->
  <div class="tui-linkedInImportLearningItemCategory">
    <div>
      {{ $str('category_label', 'contentmarketplace_linkedin') }}
    </div>
    <div class="tui-linkedInImportLearningItemCategory__current">
      {{ currentCategory.label }}
    </div>

    <!-- Add popover triggered by edit icon -->
    <CategoryPopover
      :category-options="categoryOptions"
      :course-id="courseId"
      :current-category="currentCategory.id"
      @change-course-category="$emit('change-course-category', $event)"
    />
  </div>
</template>

<script>
// Components
import CategoryPopover from 'contentmarketplace_linkedin/components/categories/ImportCategoryPopover';

export default {
  components: {
    CategoryPopover,
  },

  props: {
    // Available category options
    categoryOptions: Array,
    // Current course ID
    courseId: {
      type: String,
      required: true,
    },
    // Selected category data for item
    currentCategory: {
      type: Object,
      required: true,
    },
  },
};
</script>

<lang-strings>
  {
    "contentmarketplace_linkedin": [
      "category_label"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-linkedInImportLearningItemCategory {
  display: flex;

  &__current {
    @include tui-font-heavy();
    margin-left: var(--gap-1);
    color: var(--color-text);
  }
}
</style>

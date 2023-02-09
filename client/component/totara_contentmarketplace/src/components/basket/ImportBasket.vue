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
  @package totara_contentmarketplace
-->

<template>
  <Basket
    class="tui-contentMarketplaceImportBasket"
    :items="selectedItems"
    :wide-gap="true"
  >
    <template v-slot:status="{ empty }">
      <Button
        v-if="!empty"
        :styleclass="{ transparent: true }"
        :text="$str('basket_clear_selection', 'totara_contentmarketplace')"
        @click="$emit('clear-selection')"
      />
    </template>

    <template v-slot:actions="{ empty }">
      <!-- Selection view -->
      <template v-if="!viewingSelected">
        <!-- Default category select -->
        <div class="tui-contentMarketplaceImportBasket__category">
          <Select
            :id="$id('categorySelect')"
            :aria-label="
              $str('basket_select_category', 'totara_contentmarketplace')
            "
            char-length="15"
            :options="options"
            :required="true"
            :value="selectedCategory"
            @input="$emit('category-change', $event)"
          />
        </div>

        <!-- Go to review selection view -->
        <Button
          :disabled="empty || !selectedCategory"
          :styleclass="{ primary: true }"
          :text="$str('basket_go_to_review', 'totara_contentmarketplace')"
          @click="$emit('reviewing-selection', true)"
        />
      </template>

      <!-- Review view -->
      <template v-else>
        <Button
          :styleclass="{ transparent: true }"
          :text="$str('basket_back_to_catalogue', 'totara_contentmarketplace')"
          @click="$emit('reviewing-selection', false)"
        />

        <Button
          :disabled="empty"
          :loading="creatingContent"
          :styleclass="{ primary: true }"
          :text="$str('basket_create_courses', 'totara_contentmarketplace')"
          @click="$emit('create-courses')"
        />
      </template>
    </template>
  </Basket>
</template>

<script>
import Basket from 'tui/components/basket/Basket';
import Button from 'tui/components/buttons/Button';
import Select from 'tui/components/form/Select';

export default {
  components: {
    Basket,
    Button,
    Select,
  },

  props: {
    // Available category options
    categoryOptions: Array,
    // Category chosen from select list
    selectedCategory: [String, Number],
    // List of selected item ID's
    selectedItems: {
      type: Array,
      required: true,
    },
    // On the viewing selected screen
    viewingSelected: Boolean,
    creatingContent: Boolean,
    isLoading: Boolean,
    currentCategoryId: [String, Number],
  },

  computed: {
    /**
     * Provide list of available options with a default
     *
     * @return {Array}
     */
    options() {
      let optionList = this.categoryOptions.slice();

      if (!this.isLoading || !this.currentCategoryId) {
        // Add default string
        optionList.unshift({
          id: null,
          label: this.$str('assign_category', 'totara_contentmarketplace'),
        });
      }
      return optionList;
    },
  },
};
</script>

<lang-strings>
  {
    "totara_contentmarketplace": [
      "assign_category",
      "basket_back_to_catalogue",
      "basket_clear_selection",
      "basket_create_courses",
      "basket_go_to_review",
      "basket_select_category"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-contentMarketplaceImportBasket {
  &__category {
    width: tui-char-length(15);
  }
}
</style>

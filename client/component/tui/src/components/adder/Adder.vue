<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module tui
-->

<template>
  <ModalPresenter :open="open" @request-close="$emit('cancel')">
    <Modal
      :dismissable="dismissable"
      size="large"
      :aria-labelledby="$id('adder')"
    >
      <ModalContent :title="title" :title-id="$id('adder')">
        <div class="tui-adder">
          <div v-if="$scopedSlots.notices" class="tui-adder__notices">
            <slot name="notices" />
          </div>
          <div class="tui-adder__tabs">
            <Tabs :small-tabs="true" :fill="true" @input="tabChanged">
              <!-- Browse tab -->
              <Tab
                id="browse"
                :name="$str('adder_browse', 'totara_core')"
                class="tui-adder__tabPanel"
              >
                <!-- Browse Filters -->
                <div class="tui-adder__filters">
                  <slot name="browse-filters" />
                </div>

                <Loader class="tui-adder__list-loading" :loading="loading">
                  <div class="tui-adder__preList">
                    <slot name="pre-list" />
                  </div>
                  <!-- Browse List -->
                  <div class="tui-adder__list">
                    <slot
                      name="browse-list"
                      :disabled-items="existingItems"
                      :selected-items="allSelectedItems"
                      :update="selectionUpdate"
                    />

                    <div v-if="showLoadMore" class="tui-adder__list-loadMore">
                      <Button
                        :text="$str('loadmore', 'totara_core')"
                        @click="$emit('load-more')"
                      />
                    </div>
                  </div>
                </Loader>
              </Tab>

              <!-- Selection (basket) Tab -->
              <Tab
                id="basket"
                :name="basketTabString"
                class="tui-adder__tabPanel"
              >
                <Loader class="tui-adder__list-loading" :loading="loading">
                  <!-- Selected List -->
                  <div
                    v-show="!loading || hasLoadingPreview"
                    class="tui-adder__list tui-adder__listBasket"
                  >
                    <slot
                      name="basket-list"
                      :disabled-items="existingItems"
                      :selected-items="allSelectedItems"
                      :update="selectionUpdate"
                    />
                  </div>
                </Loader>
              </Tab>
            </Tabs>
          </div>

          <!-- Footer (count & action buttons) -->
          <div class="tui-adder__footer">
            <div
              :id="$id('items-added')"
              class="tui-adder__summary"
              aria-live="polite"
              aria-atomic="true"
            >
              <template v-if="customSelected">
                {{ customSelected(count) }}
              </template>
              <template v-else>
                {{ $str('itemsselected', 'totara_core', count) }}
              </template>
            </div>
            <div class="tui-adder__actions">
              <ButtonGroup>
                <Button
                  :disabled="!count || showLoadingBtn"
                  :text="$str('add', 'totara_core')"
                  :styleclass="{ primary: true }"
                  :aria-describedby="$id('items-added')"
                  :aria-haspopup="ariaHaspopup"
                  :loading="showLoadingBtn"
                  @click="$emit('added', allSelectedItems)"
                />
                <ButtonCancel
                  :disabled="showLoadingBtn"
                  @click="$emit('cancel')"
                />
              </ButtonGroup>
            </div>
          </div>
        </div>
      </ModalContent>
    </Modal>
  </ModalPresenter>
</template>

<script>
// Components
import Button from 'tui/components/buttons/Button';
import ButtonCancel from 'tui/components/buttons/Cancel';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Loader from 'tui/components/loading/Loader';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';

export default {
  components: {
    Button,
    ButtonCancel,
    ButtonGroup,
    Loader,
    Modal,
    ModalContent,
    ModalPresenter,
    Tab,
    Tabs,
  },

  props: {
    ariaHaspopup: [Boolean, String],
    // Customized item-selected context
    customSelected: Function,
    // Pre-selected items
    existingItems: {
      type: Array,
      default: () => [],
    },
    hasLoadingPreview: {
      type: Boolean,
    },
    // Display loading overlay for lists
    loading: {
      type: Boolean,
    },
    // Display loading spinner on Add button click
    showLoadingBtn: Boolean,
    // Display a load more button
    showLoadMore: {
      type: [Boolean, String],
    },
    // Adder is open
    open: {
      type: Boolean,
    },
    // Adder title
    title: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      allSelectedItems: [],
      basketItems: [],
      dismissable: {
        overlayClose: false,
        esc: true,
        backdropClick: false,
      },
    };
  },

  computed: {
    /**
     * Get array of ID's for basket
     *
     * @return {Array}
     */
    basketIds() {
      const ids = this.allSelectedItems.filter(
        item => !this.existingItems.includes(item)
      );
      return ids;
    },

    /**
     * Update the basket tab string based on selected count
     *
     * @return {String}
     */
    basketTabString() {
      // Fetch string with a RTL bracket fix for count on safari.
      if (this.count) {
        return this.$str('adder_selection_with_count', 'totara_core', {
          count:
            '\u202D' + this.$str('count', 'totara_core', this.count) + '\u202D',
        });
      }
      return this.$str('adder_selection', 'totara_core');
    },

    /**
     * Update selected count
     *
     * @return {Int}
     */
    count() {
      const items = this.basketIds;
      if (!items.length) {
        return 0;
      }
      return items.length;
    },

    id() {
      return this.$id();
    },
  },

  watch: {
    /**
     * On opening of added include existing items in selection
     *
     */
    open() {
      if (this.open) {
        this.allSelectedItems = this.existingItems;
      }
    },

    existingItems() {
      if (this.open && this.existingItems) {
        this.existingItems.forEach(item => {
          if (!this.allSelectedItems.includes(item)) {
            this.allSelectedItems.push(item);
          }
        });
      }
    },
  },

  methods: {
    /**
     * On Selection change update selected items
     *
     * @param {Array} selection
     */
    selectionUpdate(selection) {
      this.allSelectedItems = selection;
    },

    /**
     * When the tab has changed,
     * check if basket tab is selected and emit an event
     *
     * @param {String} tab
     */
    tabChanged(tab) {
      if (tab === 'basket') {
        this.$emit('selected-tab-active', this.basketIds);
      }
    },
  },
};
</script>

<lang-strings>
{
  "totara_core": [
    "add",
    "adder_browse",
    "adder_selection",
    "adder_selection_with_count",
    "count",
    "loadmore",
    "itemsselected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-adder {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
  height: 50rem;
  margin-top: calc(var(--gap-6) - var(--modal-content-separation));

  &__notices {
    margin-bottom: var(--gap-6);
  }

  &__tabs {
    flex-grow: 1;
    min-height: 0;
  }

  &__tabPanel {
    display: flex;
    flex-direction: column;
  }

  &__list {
    flex-grow: 1;
    height: 316px;
    overflow-y: auto;

    &-loading {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      min-height: 50px;
    }

    &-loadMore {
      margin: var(--gap-4) 0 var(--gap-4);
      text-align: center;
    }

    &::before {
      display: block;
      height: var(--gap-4);
      content: '';
    }
  }

  &__footer {
    display: flex;
    flex-wrap: wrap;
    border-top: var(--border-width-normal) solid var(--color-neutral-5);

    & > * {
      margin-top: var(--gap-6);
    }
  }

  &__summary {
    display: flex;
    align-items: center;
    @include tui-font-heading-label();
  }

  &__actions {
    display: flex;
    margin-left: auto;
  }
}
</style>

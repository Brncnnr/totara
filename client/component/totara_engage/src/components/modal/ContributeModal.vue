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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_engage
-->

<template>
  <span>
    <Modal
      :size="size"
      :aria-labelledby="$id('title')"
      :dismissable="dismissable"
    >
      <ModalContent
        class="tui-engageContributeModal"
        :close-button="false"
        :title="getTitle"
        :content-padding="false"
      >
        <div class="tui-engageContributeModal__content">
          <div
            v-if="adder && !hideTabs"
            class="tui-engageContributeModal__adderContainer"
          >
            <span>
              {{ $str('or', 'totara_engage') }}
              <Button
                :text="adder.text"
                :disabled="false"
                :styleclass="{
                  transparent: true,
                }"
                @click="$emit('adder-open')"
              />
              {{ adder.destination }}
            </span>
          </div>
          <Tabs
            v-if="!$apollo.loading && modals.length > 0"
            v-show="!hideTabs"
            :selected="selectedTab"
            :small-tabs="true"
            :controlled="true"
            class="tui-engageContributeModal__tabs"
            @input="changeTabRequest"
          >
            <Tab
              v-for="modal in modals"
              :id="modal.id"
              :key="modal.id"
              :name="modal.label"
              :disabled="disabledId === modal.id"
            />
          </Tabs>

          <div
            v-if="!$apollo.loading"
            class="tui-engageContributeModal__componentContent"
          >
            <!-- This is where the content of selectedTab is -->
            <component
              :is="selectedTab"
              :container="container"
              :show-notification="showNotification"
              :can-share="getCanShare(selectedTab)"
              @change-title="stage = $event"
              @done="$emit('done', $event)"
              @cancel="$emit('request-close')"
              @unsaved-changes="hasUnsavedChanges = true"
            />
          </div>

          <ButtonIcon
            v-if="expandable"
            v-show="!hideTabs"
            class="tui-engageContributeModal__resize"
            :aria-label="resizeAriaLabel"
            :styleclass="{ transparentNoPadding: true }"
            @click="resize"
          >
            <SizeContractIcon v-if="expanded" />
            <SizeExpandIcon v-else />
          </ButtonIcon>
        </div>
      </ModalContent>
    </Modal>
    <ConfirmationModal
      :open="unsavedChangesModalOpen"
      :title="$str('unsaved_changes_title', 'totara_engage')"
      :confirm-button-text="$str('button_continue', 'totara_engage')"
      @confirm="changeTabConfirm"
      @cancel="unsavedChangesModalOpen = false"
    >
      <p>{{ $str('unsaved_changes_message', 'totara_engage') }}</p>
    </ConfirmationModal>
  </span>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import SizeContractIcon from 'tui/components/icons/SizeContract';
import SizeExpandIcon from 'tui/components/icons/SizeExpand';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';
import tui from 'tui/tui';

// GraphQL
import getModals from 'totara_engage/graphql/modals';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    Button,
    ButtonIcon,
    ConfirmationModal,
    Modal,
    ModalContent,
    SizeContractIcon,
    SizeExpandIcon,
    Tab,
    Tabs,
  },

  mixins: [ContainerMixin],

  props: {
    excludeModals: Array,
    adder: {
      type: Object,
      default: null,
      validator(obj) {
        const keys = ['destination', 'text'];

        return keys.every(key => key in obj);
      },
    },
    showNotification: {
      type: Boolean,
    },
  },

  apollo: {
    modals: {
      query: getModals,
      variables() {
        return {
          exclude: this.getExcludeModals(),
        };
      },
      result({ data: { modals } }) {
        this.modals = modals.slice();
        for (let i in this.modals) {
          if (!has.call(this.modals, i)) {
            continue;
          }

          let modal = this.modals[i];
          this.$options.components[modal.id] = tui.asyncComponent(
            modal.component
          );

          this.canShares[modal.id] = modal.can_share;

          if (null === this.selectedTab) {
            this.selectedTab = modal.id;
          }
        }

        return this.modals;
      },
    },
  },

  data() {
    return {
      compress: this.$str('compress', 'totara_engage'),
      expand: this.$str('expand', 'totara_engage'),
      selectedTab: null,
      size: 'large',
      expanded: false,
      dismissable: {
        overlayClose: false,
        esc: true,
        backdropClick: false,
      },
      modals: [],
      hideTabs: false,
      disabledId: 0,
      stage: 0,
      hasUnsavedChanges: false,
      unsavedChangesModalOpen: false,
      requestedTab: null,
      canShares: [],
    };
  },

  computed: {
    expandable() {
      if (this.modals.length !== 0) {
        let modal = this.getSelectedModal();
        return modal.expandable;
      }
      return false;
    },

    resizeIcon() {
      return this.expanded ? 'compress' : 'expand';
    },

    resizeAriaLabel() {
      return this.expanded ? this.compress : this.expand;
    },

    getTitle() {
      if (this.stage === 1) {
        return this.$str('accesssettings', 'totara_engage');
      }
      return this.$str('contribute', 'totara_engage');
    },
  },

  watch: {
    selectedTab() {
      if (this.expanded && !this.expandable) {
        this.size = 'large';
        this.expanded = false;
      }
    },
    stage() {
      this.hideTabs = this.stage !== 0;
    },
  },

  methods: {
    resize() {
      this.expanded = !this.expanded;
      if (this.expanded) {
        this.size = 'sheet';
      } else {
        this.size = 'large';
      }
    },
    getSelectedModal() {
      const that = this;
      return that.modals.find(function(modal) {
        return modal.id === that.selectedTab;
      });
    },

    getExcludeModals() {
      if (this.container === null || this.container === undefined) {
        return this.excludeModals;
      }

      const { component, showModal } = this.container;
      if (showModal) {
        return [];
      }

      return [component];
    },

    /**
     * User wants to change the tab.
     *
     * @param {String} id
     */
    changeTabRequest(id) {
      if (this.hasUnsavedChanges) {
        this.unsavedChangesModalOpen = true;
        this.requestedTab = id;
      } else {
        this.selectedTab = id;
      }
    },

    /**
     * User has confirmed they want to change the tab.
     */
    changeTabConfirm() {
      this.hasUnsavedChanges = false;
      this.unsavedChangesModalOpen = false;
      this.selectedTab = this.requestedTab;
    },

    /**
     * Can canShare for the specific component.
     *
     * @param {String} selectedTab
     */
    getCanShare(selectedTab) {
      return this.canShares[selectedTab];
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "accesssettings",
      "button_continue",
      "compress",
      "contribute",
      "expand",
      "or",
      "unsaved_changes_message",
      "unsaved_changes_title"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageContributeModal {
  position: relative;

  &__content {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    min-height: 60rem;
    margin-top: var(--gap-2);
  }

  &__adderContainer {
    margin-bottom: var(--gap-2);
    padding: 0 var(--gap-8);
    &__title {
      @include tui-font-heading-small();
      margin-top: 0;
    }
  }

  &__resize.tui-iconBtn {
    position: absolute;
    top: var(--gap-5);
    right: var(--gap-5);
  }

  &__tabs {
    display: flex;
    flex-direction: column;
    padding: 0;

    .tui-tabs {
      &__tabs {
        padding-right: var(--gap-8);
        padding-left: var(--gap-8);
      }

      &__panels {
        display: flex;
        flex-direction: column;
      }
    }
  }

  &__componentContent {
    position: relative;
    display: flex;
    flex-basis: 0;
    flex-direction: column;
    flex-grow: 1;
    min-height: 0;
    padding: var(--gap-8);
  }
}
</style>

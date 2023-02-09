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
  @package tui
-->

<template>
  <div v-if="treeData && treeData.length" class="tui-settingsNavigation">
    <Button
      v-if="navigationType"
      :text="$str('administration', 'core')"
      @click="showAdminModal"
    />

    <Dropdown v-else :close-on-click="false">
      <template v-slot:trigger="{ toggle, isOpen }">
        <Button
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="$str('administration', 'core')"
          :caret="true"
          :text="$str('administration', 'core')"
          @click="toggle"
        />
      </template>

      <SettingsTree
        v-model="openTreeBranches"
        class="tui-settingsNavigation__content"
        no-padding
        :tree-data="treeData"
        is-dropdown
      />
    </Dropdown>

    <ModalPresenter :open="modalOpen" @request-close="closeModal">
      <Modal size="sheet" :aria-labelledby="$id('admin-modal')">
        <ModalContent
          :title="$str('administration', 'core')"
          :title-id="$id('admin-modal')"
          @dismiss="closeModal"
        >
          <SettingsTree
            v-model="openTreeBranches"
            class="tui-settingsNavigation__content"
            label-type="link"
            :tree-data="treeData"
          />
        </ModalContent>
      </Modal>
    </ModalPresenter>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import { config } from 'tui/config';
import Dropdown from 'tui/components/dropdown/Dropdown';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import { NavigationType } from '../../js/lib/internal/settings_navigation_utils';
import SettingsTree from 'tui/components/settings_navigation/SettingsNavigationTree';

import settingsTreeQuery from 'totara_core/graphql/settings_navigation_tree';

export default {
  components: {
    Button,
    Dropdown,
    Modal,
    ModalContent,
    ModalPresenter,
    SettingsTree,
  },

  props: {
    /**
     * Tree data for admin options
     */
    stackedLayout: Boolean,
    // Type of settings navigation
    type: {
      type: String,
      default: NavigationType.DROPDOWN,
      validator(prop) {
        return Object.values(NavigationType).includes(prop);
      },
    },
  },

  data() {
    return {
      modalOpen: false,
      treeData: [],
      openTreeBranches: [],
    };
  },

  apollo: {
    treeData: {
      query: settingsTreeQuery,
      variables() {
        return {
          context_id: config.context.id,
          page_url: window.location.href,
        };
      },
      update({ data }) {
        this.openTreeBranches = this.openTreeBranches.concat(data.open_ids);
        return data.trees;
      },
    },
  },

  computed: {
    isModalType() {
      return this.type === NavigationType.MODAL;
    },

    /**
     * Switch to modal layout if it is stacked or
     * if prop type is set to NavigationType.MODAL
     */
    navigationType() {
      return this.stackedLayout || this.isModalType;
    },
  },

  methods: {
    /**
     * Close admin modal.
     */
    closeModal() {
      this.modalOpen = false;
    },

    showAdminModal() {
      this.modalOpen = true;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "administration"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --settings-navigation-spacing: var(--gap-2);
  --settings-navigation-tree-width: 340px;
}

.tui-settingsNavigation {
  .tui-popoverPositioner {
    width: var(--settings-navigation-tree-width);
  }

  &__content {
    margin: 0 var(--settings-navigation-spacing);
  }
}
</style>

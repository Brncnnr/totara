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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<template>
  <div class="tui-contributeWorkspace">
    <ModalPresenter :open="openModal" @request-close="openModal = false">
      <WorkspaceModal @create-workspace="createWorkspace" />
    </ModalPresenter>

    <ButtonIcon
      class="tui-contributeWorkspace__button"
      :aria-label="buttonAriaLabel"
      :styleclass="{ circle: true, xsmall: true }"
      :disabled="disabled"
      @click.prevent="openModal = true"
    >
      <AddIcon />
    </ButtonIcon>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import AddIcon from 'tui/components/icons/Add';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkspaceModal from 'container_workspace/components/modal/WorkspaceModal';

export default {
  components: {
    ButtonIcon,
    AddIcon,
    ModalPresenter,
    WorkspaceModal,
  },

  props: {
    disabled: Boolean,

    buttonAriaLabel: {
      type: String,
      default() {
        return this.$str('create_space', 'container_workspace');
      },
    },
  },

  data() {
    return {
      submitting: false,
      openModal: false,
    };
  },

  methods: {
    /**
     *
     * @param {Object} workspace
     */
    createWorkspace(workspace) {
      // Hide the modal, after create
      this.openModal = false;
      this.$emit('create-workspace', workspace);
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "create_space"
    ]
  }
</lang-strings>

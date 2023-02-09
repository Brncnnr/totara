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
  <Modal
    :dismissable="{
      backdropClick: false,
      esc: false,
    }"
    class="tui-workspaceAccessModal"
  >
    <ModalContent
      class="tui-workspaceAccessModal__content"
      :title="$str('accesssettings', 'totara_engage')"
      :close-button="false"
    >
      <WorkspaceAccessForm
        :selected-topics="selectedTopics"
        :submitting="submitting"
        @cancel="$emit('request-close')"
        @submit="$emit('submit-form', $event)"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import WorkspaceAccessForm from 'container_workspace/components/form/WorkspaceAccessForm';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';

export default {
  components: {
    WorkspaceAccessForm,
    Modal,
    ModalContent,
  },

  props: {
    /**
     * A prop to say whether to disable the form or not.
     */
    submitting: Boolean,

    selectedTopics: {
      type: Array,
      default() {
        return [];
      },
      validator(topics) {
        topics = Array.prototype.filter.call(topics, topic => {
          return !('id' in topic) || !('value' in topic);
        });

        return 0 === topics.length;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "accesssettings"
    ]
  }
</lang-strings>

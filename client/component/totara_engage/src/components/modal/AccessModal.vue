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
  <Modal
    size="normal"
    :aria-labelledby="$id('title')"
    class="tui-engageAccessModal"
    :dismissable="{ backdropClick: false }"
  >
    <ModalContent
      class="tui-engageAccessModal__content"
      :title="$str('accesssettings', 'totara_engage')"
      :title-id="$id('title')"
      :close-button="showCloseButton"
    >
      <AccessForm
        :item-id="itemId"
        :component="component"
        :show-back="showBack"
        :submitting="submitting"
        :selected-access="selectedAccess"
        :selected-options="selectedOptions"
        :public-disabled="publicDisabled"
        :restricted-disabled="restrictedDisabled"
        :private-disabled="privateDisabled"
        :selected-time-view="selectedTimeView"
        :enable-time-view="enableTimeView"
        :can-share="canShare"
        @back="$emit('back')"
        @done="$emit(done, $event)"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import AccessForm from 'totara_engage/components/form/AccessForm';
import { AccessManager } from 'totara_engage/index';

export default {
  components: {
    Modal,
    ModalContent,
    AccessForm,
  },

  props: {
    itemId: {
      type: [Number, String],
      required: true,
    },

    component: {
      type: String,
      required: true,
    },

    selectedAccess: {
      type: String,
      default: AccessManager.PRIVATE,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    selectedOptions: {
      type: Object,
      default() {
        return {
          shares: [],
          topics: [],
        };
      },
    },

    selectedTimeView: {
      type: String,
      default: null,
    },

    enableTimeView: {
      type: Boolean,
      default: false,
    },

    submitting: {
      type: Boolean,
      default: false,
    },

    publicDisabled: {
      type: Boolean,
      default: false,
    },

    restrictedDisabled: {
      type: Boolean,
      default: false,
    },

    privateDisabled: {
      type: Boolean,
      default: false,
    },

    showCloseButton: {
      type: Boolean,
      default: false,
    },

    showBack: {
      type: Boolean,
      default: false,
    },

    hasNonPublicResources: Boolean,

    isPrivate: Boolean,
    isRestricted: Boolean,
    canShare: Boolean,
  },

  computed: {
    done() {
      if (this.isPrivate && this.hasNonPublicResources)
        return 'warning-privatetorestrictedorpublic';
      if (this.isRestricted && this.hasNonPublicResources)
        return 'warning-restrictedtopublic';
      return 'done';
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

<style lang="scss">
.tui-engageAccessModal {
  &__content {
    .tui-modalContent__content {
      position: relative;
      display: flex;
      flex-basis: 100%;
      flex-direction: column;
      min-height: 450px;
    }
  }
}
</style>

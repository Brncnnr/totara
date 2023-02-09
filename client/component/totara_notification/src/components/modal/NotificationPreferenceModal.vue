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

  @author Qingyang.liu <qingyang.liu@totaralearning.com>
  @module totara_notification
-->

<template>
  <Modal
    size="large"
    :aria-labelledby="$id('title')"
    :dismissable="{
      esc: true,
      backdropClick: false,
      overlayClose: false,
    }"
  >
    <ModalContent
      :title="title"
      :title-id="$id('title')"
      :close-button="showCloseButton"
    >
      <NotificationPreferenceForm
        class="tui-notificationPreferenceModal__form"
        :context-id="contextId"
        :extended-context="extendedContext"
        :preference="preference"
        :parent-value="parentValue"
        :valid-schedule-types="validScheduleTypes"
        :resolver-class-name="resolverClassName"
        :available-recipients="availableRecipients"
        :default-delivery-channels="defaultDeliveryChannels"
        :additional-criteria-component="additionalCriteriaComponent"
        :preferred-editor-format="preferredEditorFormat"
        @submit="handleSubmit"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import NotificationPreferenceForm from 'totara_notification/components/form/NotificationPreferenceForm';
import {
  getDefaultNotificationPreference,
  validatePreferenceProp,
  validateAvailableRecipientsProp,
  validateDefaultDeliveryChannelsProp,
} from '../../internal/notification_preference';

export default {
  components: {
    Modal,
    ModalContent,
    NotificationPreferenceForm,
  },

  props: {
    title: {
      type: String,
      required: true,
    },

    contextId: {
      type: Number,
      required: true,
    },

    extendedContext: {
      type: Object,
      required: false,
    },

    showCloseButton: {
      type: Boolean,
      default: true,
    },

    preference: {
      type: Object,
      validator: validatePreferenceProp(),
      default: getDefaultNotificationPreference({
        body_content: '',
        subject_content: '',
      }),
    },

    parentValue: {
      type: Object,
      validator: validatePreferenceProp(),
      default() {
        return null;
      },
    },

    resolverClassName: {
      type: String,
      required: true,
    },

    validScheduleTypes: {
      type: Array,
      required: true,
    },

    availableRecipients: {
      type: Array,
      required: true,
      validator: validateAvailableRecipientsProp(),
    },

    defaultDeliveryChannels: {
      type: Array,
      required: true,
      validator: validateDefaultDeliveryChannelsProp(),
    },

    additionalCriteriaComponent: {
      type: String,
    },

    preferredEditorFormat: Number,
  },

  methods: {
    handleSubmit(formValue) {
      if (this.resolverClassName) {
        formValue.resolver_class_name = this.resolverClassName;
      }

      this.$emit('form-submit', formValue);
    },
  },
};
</script>

<style lang="scss">
.tui-notificationPreferenceModal {
  &__form {
    margin-top: var(--gap-6);
  }
}
</style>

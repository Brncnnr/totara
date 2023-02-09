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

  @author Cody Finegan <cody.finegan@totaralearning.com>
  @module totara_notification
-->

<template>
  <Modal
    size="large"
    :aria-labelledby="$id('title')"
    :dismissable="{
      esc: true,
      backdropClick: true,
      overlayClose: false,
    }"
  >
    <ModalContent
      :title="title"
      :title-id="$id('title')"
      :close-button="showCloseButton"
    >
      <p>
        {{ $str('notification_trigger', 'totara_notification', resolverLabel) }}
      </p>
      <DeliveryPreferenceForm
        :default-delivery-channels="defaultDeliveryChannels"
        :show-override="showOverride"
        :initial-override-status="initialOverrideStatus"
        @form-submit="handleSubmit"
        @cancel="$emit('request-close')"
      />
    </ModalContent>
  </Modal>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import DeliveryPreferenceForm from 'totara_notification/components/form/DeliveryPreferenceForm';
import { validateDefaultDeliveryChannelsProp } from '../../internal/notification_preference';

export default {
  components: {
    Modal,
    ModalContent,
    DeliveryPreferenceForm,
  },

  props: {
    title: {
      type: String,
      required: true,
    },

    showCloseButton: {
      type: Boolean,
      default: true,
    },

    defaultDeliveryChannels: {
      type: Array,
      required: true,
      validator: validateDefaultDeliveryChannelsProp(),
    },

    resolverClassName: {
      type: String,
      required: true,
    },

    resolverLabel: {
      type: String,
      required: true,
    },

    showOverride: Boolean,
    initialOverrideStatus: Boolean,
  },

  methods: {
    handleSubmit(deliveryChannels, overridenStatus) {
      const formValue = {
        resolver_class_name: this.resolverClassName,
        delivery_channels: deliveryChannels,
      };

      this.$emit('form-submit', formValue, overridenStatus);
    },
  },
};
</script>

<lang-strings>
  {
    "totara_notification": [
      "notification_trigger"
    ]
  }
</lang-strings>

<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Cody Finegan <cody.finegan@totaralearning.com>
  @module totara_notification
-->
<template>
  <Form
    class="tui-notificationDeliveryPreferenceForm"
    @submit="handleFormSubmit"
  >
    <div
      v-if="showOverride"
      class="tui-notificationDeliveryPreferenceForm__overrideWrapper"
    >
      <Checkbox
        :aria-label="
          $str('override_delivery_channels_aria', 'totara_notification')
        "
        :checked="isDeliveryPreferenceOverridden"
        :value="isDeliveryPreferenceOverridden ? '1' : '0'"
        name="override_delivery_preferences"
        @change="overrideDeliveryPreference"
      >
        {{ $str('override', 'totara_notification') }}
      </Checkbox>
      <InfoIconButton
        :aria-label="
          $str(
            'delivery_preferences_override_helptext_aria',
            'totara_notification'
          )
        "
      >
        {{
          $str('delivery_preferences_override_helptext', 'totara_notification')
        }}
      </InfoIconButton>
    </div>
    <Table :data="defaultDeliveryChannels" :expandable-rows="true">
      <template v-slot:header-row>
        <HeaderCell
          size="3"
          class="tui-notificationDeliveryPreferenceForm__tableHeader"
        >
          {{
            $str('notification_delivery_channels_label', 'totara_notification')
          }}
          <InfoIconButton
            v-if="!showOverride"
            :aria-label="
              $str('delivery_preferences_helptext_aria', 'totara_notification')
            "
          >
            {{ $str('delivery_preferences_helptext', 'totara_notification') }}
          </InfoIconButton>
        </HeaderCell>
      </template>
      <template v-slot:row="{ row }">
        <Cell size="3">
          {{ row.label }}
        </Cell>
        <Cell>
          <Checkbox
            v-if="
              !row.parent_component || deliveryChannels[row.parent_component]
            "
            v-model="deliveryChannels[row.component]"
            :aria-label="row.label"
            :disabled="isCheckboxDisabled"
            :name="`default_${row.component}`"
          />
          <template v-else>
            {{ $str('unavailable', 'totara_notification') }}
          </template>
        </Cell>
      </template>
    </Table>
    <FormRow>
      <ButtonGroup class="tui-notificationPreferenceForm__buttonGroup">
        <Button
          :styleclass="{ primary: true }"
          :text="$str('save', 'totara_core')"
          type="submit"
        />

        <Cancel @click="$emit('cancel')" />
      </ButtonGroup>
    </FormRow>
  </Form>
</template>

<script>
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';
import Checkbox from 'tui/components/form/Checkbox';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import Cancel from 'tui/components/buttons/Cancel';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';

import { validateDefaultDeliveryChannelsProp } from '../../internal/notification_preference';

/**
 * @param deliveryChannels
 * @returns {{}}
 */
function createDeliveryChannels(deliveryChannels) {
  const channels = {};

  deliveryChannels.map(({ component, is_enabled }) => {
    channels[component] = is_enabled;
  });

  return channels;
}

export default {
  components: {
    Table,
    HeaderCell,
    Cell,
    Checkbox,
    Form,
    FormRow,
    ButtonGroup,
    Button,
    Cancel,
    InfoIconButton,
  },

  props: {
    defaultDeliveryChannels: {
      type: Array,
      required: true,
      validator: validateDefaultDeliveryChannelsProp(),
    },

    showOverride: Boolean,
    initialOverrideStatus: Boolean,
  },

  data() {
    return {
      deliveryChannels: createDeliveryChannels(this.defaultDeliveryChannels),
      isCheckboxDisabled: this.selectionsDisabled(),
      isDeliveryPreferenceOverridden: this.initialOverrideStatus,
    };
  },

  watch: {
    isDeliveryPreferenceOverridden() {
      this.isCheckboxDisabled = this.selectionsDisabled();
    },
  },

  methods: {
    handleFormSubmit() {
      const channels = [];
      for (const channel in this.deliveryChannels) {
        if (this.deliveryChannels[channel]) {
          channels.push(channel);
        }
      }

      this.$emit(
        'form-submit',
        channels,
        this.showOverride ? this.isDeliveryPreferenceOverridden : null
      );
    },

    selectionsDisabled() {
      // Checkbox disabled state should be handled only if override functionality is visible
      if (this.showOverride) {
        if (
          (this.isDeliveryPreferenceOverridden === undefined &&
            !this.initialOverrideStatus) ||
          (this.isDeliveryPreferenceOverridden !== undefined &&
            !this.isDeliveryPreferenceOverridden)
        ) {
          return true;
        }
      }

      return false;
    },

    overrideDeliveryPreference(value) {
      this.isDeliveryPreferenceOverridden = value;
    },
  },
};
</script>

<lang-strings>
{
  "totara_notification": [
    "delivery_preferences_helptext",
    "delivery_preferences_helptext_aria",
    "notification_delivery_channels_label",
    "unavailable",
    "delivery_preferences_override_helptext",
    "delivery_preferences_override_helptext_aria",
    "override",
    "override_delivery_channels_aria"
  ],
  "totara_core": [
    "save"
  ],
  "core": [
    "required"
  ]
}
</lang-strings>

<style lang="scss">
.tui-notificationDeliveryPreferenceForm {
  &__overrideWrapper {
    display: flex;
    align-items: center;
    margin-top: var(--gap-2);
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
  }

  &__tableHeader {
    flex-direction: row;
  }
}
</style>

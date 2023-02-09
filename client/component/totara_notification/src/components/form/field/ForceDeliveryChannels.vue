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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_notification
-->
<template>
  <Table
    :data="defaultDeliveryChannels"
    class="tui-forceDeliveryChannels"
    :stack-at="350"
  >
    <template v-slot:header-row>
      <HeaderCell>
        {{ $str('delivery_channel', 'totara_notification') }}
      </HeaderCell>
      <HeaderCell align="center">
        {{ $str('default', 'core') }}
      </HeaderCell>
      <HeaderCell align="center">
        <div class="tui-forceDeliveryChannels__forcedHeader">
          {{ $str('forced', 'totara_notification') }}
          <InfoIconButton
            :aria-label="$str('forced_info', 'totara_notification')"
          >
            {{ $str('forced_delivery_help', 'totara_notification') }}
          </InfoIconButton>
        </div>
      </HeaderCell>
    </template>

    <template
      v-slot:row="{
        row: {
          label,
          is_enabled,
          component,
          is_sub_delivery_channel,
          parent_component,
        },
      }"
    >
      <Cell>
        {{ label }}
      </Cell>
      <Cell align="center" :column-header="$str('default', 'core')">
        <CheckSuccess v-if="is_enabled" />
      </Cell>
      <Cell
        align="center"
        :column-header="$str('forced', 'totara_notification')"
      >
        <template
          v-if="
            !is_sub_delivery_channel || isChannelAvailable(parent_component)
          "
        >
          <Checkbox
            :value="component"
            :checked="isForced({ component })"
            :aria-label="$str('force_channel_x', 'totara_notification', label)"
            :disabled="disabled"
            :name="`force_${component}`"
            @change="updateForceDeliveryChannels(component, $event)"
          />
        </template>
        <template v-else>
          {{ $str('notavailable', 'core') }}
        </template>
      </Cell>
    </template>
  </Table>
</template>

<script>
import Table from 'tui/components/datatable/Table';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Cell from 'tui/components/datatable/Cell';
import { validateDefaultDeliveryChannelsProp } from '../../../internal/notification_preference';
import CheckSuccess from 'tui/components/icons/CheckSuccess';
import Checkbox from 'tui/components/form/Checkbox';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';

export default {
  components: {
    Table,
    HeaderCell,
    Cell,
    CheckSuccess,
    Checkbox,
    InfoIconButton,
  },

  props: {
    disabled: Boolean,

    /**
     * The list of delivery channels that enabled for a resolver.
     * @var {Array}
     */
    defaultDeliveryChannels: {
      type: Array,
      required: true,
      validator: validateDefaultDeliveryChannelsProp(),
    },

    /**
     * An list of string component names of which delivery channels are enabled.
     * @var {String[]}
     */
    forcedDeliveryChannels: {
      type: Array,
      default() {
        return [];
      },
      validator(prop) {
        return prop.every(component => {
          return typeof component === 'string';
        });
      },
    },
  },

  methods: {
    /**
     *
     * @param {String} component
     * @return {Boolean}
     */
    isForced({ component }) {
      return !!this.forcedDeliveryChannels.find(
        forcedComponent => component === forcedComponent
      );
    },

    /**
     * Returns whether the channel is available or not, by checking
     * the target's channel parent. If the target's channel parent
     * appears in the default delivery and marked as not available
     * then the channel will not be available at all.
     *
     * @param {?String} parentComponent
     *
     * @return {Boolean}
     */
    isChannelAvailable(parentComponent) {
      if (!parentComponent) {
        return true;
      }

      const parentChannel = this.defaultDeliveryChannels.find(
        ({ component }) => component === parentComponent
      );

      if (!parentChannel) {
        return true;
      }

      // Either the parent channel is enabled, or the parent channel is forced.
      return (
        parentChannel.is_enabled ||
        this.isForced({ component: parentComponent })
      );
    },

    /**
     * Emit the updated version of properties forceDeliveryChannels.
     * @param {String}  component
     * @param {Boolean} checked
     */
    updateForceDeliveryChannels(component, checked) {
      const channels = this.forcedDeliveryChannels;

      if (checked) {
        const foundComponent = channels.find(
          forcedComponent => component === forcedComponent
        );

        if (foundComponent) {
          this.$emit('update-forced-delivery-channels', channels);
          return;
        }

        this.$emit(
          'update-forced-delivery-channels',
          channels.concat([component])
        );
        return;
      }

      // It is probably remove the channel class name from the list of forced delivery.
      this.$emit(
        'update-forced-delivery-channels',
        channels.filter(forcedComponent => forcedComponent !== component)
      );
    },
  },
};
</script>

<lang-strings>
{
  "totara_notification": [
    "delivery_channel",
    "force_channel_x",
    "forced",
    "forced_info",
    "forced_delivery_help"
  ],
  "core": [
    "default",
    "notavailable"
  ]
}
</lang-strings>

<style lang="scss">
.tui-forceDeliveryChannels {
  &__forcedHeader {
    display: flex;
  }
}
</style>

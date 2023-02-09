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

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module totara_notification
-->

<template>
  <div>
    <CollapsibleGroupToggle
      v-model="expanded"
      :align-end="false"
      :transparent="false"
    />

    <div class="tui-userPreferencesTable">
      <Collapsible
        v-for="eventResolver in eventResolvers"
        :key="eventResolver.plugin_name"
        v-model="expanded[eventResolver.plugin_name]"
        :label="eventResolver.plugin_name"
        :indent-contents="true"
      >
        <Table
          :data="eventResolver.resolvers"
          :expandable-rows="true"
          :hover-off="true"
        >
          <template v-slot:header-row>
            <HeaderCell size="4">
              {{ $str('notifiable_events', 'totara_notification') }}
            </HeaderCell>
            <HeaderCell size="4">
              {{ $str('delivery_channels', 'totara_notification') }}
            </HeaderCell>
            <HeaderCell align="start" size="2">
              {{ $str('enabled', 'totara_notification') }}
            </HeaderCell>
            <HeaderCell>
              <span class="sr-only">
                {{ $str('actions', 'core') }}
              </span>
            </HeaderCell>
          </template>

          <template v-slot:row="{ row }">
            <Cell size="4">
              {{ row.name }}
            </Cell>
            <Cell size="4">
              {{ display_delivery_channels(row.delivery_channels) }}
            </Cell>
            <Cell align="start" size="2">
              <ToggleSwitch
                :aria-label="
                  $str('enable_status', 'totara_notification', row.name)
                "
                :value="row.enabled"
                :toggle-only="true"
                @input="toggleEnabled($event, row)"
              />
            </Cell>
            <Cell align="end" size="1">
              <NotifiableEventAction
                :resolver-name="row.name"
                :show-create-notification-option="false"
                :show-delivery-preference-option="true"
                :can-audit="false"
                :can-manage="true"
                @update-delivery-preferences="updateDeliveryPreference(row)"
              />
            </Cell>
          </template>
        </Table>
      </Collapsible>
    </div>
  </div>
</template>

<script>
import Collapsible from 'tui/components/collapsible/Collapsible';
import CollapsibleGroupToggle from 'tui/components/collapsible/CollapsibleGroupToggle';
import Cell from 'tui/components/datatable/Cell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
import NotifiableEventAction from 'totara_notification/components/action/NotifiableEventAction';

export default {
  components: {
    Collapsible,
    CollapsibleGroupToggle,
    Cell,
    HeaderCell,
    Table,
    ToggleSwitch,
    NotifiableEventAction,
  },

  props: {
    eventResolvers: {
      type: Array,
      default: () => [],
      validator(prop) {
        return prop.every(preference => {
          return (
            'component' in preference &&
            'plugin_name' in preference &&
            'resolvers' in preference
          );
        });
      },
    },
  },

  data() {
    return { expanded: {} };
  },

  created() {
    const expanded = {};
    this.eventResolvers.forEach(
      eventResolver => (expanded[eventResolver.plugin_name] = false)
    );

    this.expanded = expanded;
  },

  methods: {
    /**
     * @param {Boolean} value
     * @param {Object} updatedResolver
     */
    toggleEnabled(value, updatedResolver) {
      this.$emit('toggle-enabled', {
        userId: updatedResolver.user_id,
        resolverClassname: updatedResolver.resolver_class_name,
        isEnabled: value,
        userPreferenceId: updatedResolver.user_preference_id,
      });
    },
    /**
     * Squash the collection of delivery channel labels into a string
     *
     * @param {Array} channels
     * @returns {string}
     */
    display_delivery_channels(channels) {
      return channels
        .filter(({ is_enabled }) => is_enabled)
        .map(({ label }) => label)
        .join('; ');
    },

    /**
     * @param {Object} resolverPreference
     */
    updateDeliveryPreference(resolverPreference) {
      this.$emit('update-delivery-preferences', {
        resolverClassName: resolverPreference.resolver_class_name,
        resolverLabel: resolverPreference.name,
        defaultDeliveryChannels: resolverPreference.delivery_channels,
        OverrideDeliveryChannels:
          resolverPreference.overridden_delivery_channels,
        userPreferenceId: resolverPreference.user_preference_id,
      });
    },
  },
};
</script>
<lang-strings>
{
  "totara_notification": [
    "delivery_channels",
    "enabled",
    "enable_status",
    "notifiable_events"
  ],
  "core": [
    "actions"
  ]
}
</lang-strings>

<style lang="scss">
.tui-userPreferencesTable {
  margin-top: var(--gap-4);
}
</style>

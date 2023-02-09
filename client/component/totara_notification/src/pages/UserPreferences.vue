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
  <Layout
    class="tui-userPreferences"
    :title="$str('user_preferences_page_title', 'totara_notification')"
    :loading="$apollo.loading"
  >
    <template v-if="!$apollo.loading" v-slot:content>
      <UserPreferencesTable
        :event-resolvers="groupedPreferences"
        class="tui-userPreferences__table"
        @toggle-enabled="saveUserPreference"
        @update-delivery-preferences="handleUpdateDeliveryPreferences"
      />

      <ModalPresenter
        :open="deliveryPreferenceModal.open"
        @request-close="deliveryPreferenceModal.open = false"
        @close-complete="resetState"
      >
        <DeliveryPreferenceModal
          :title="$str('delivery_preferences', 'totara_notification')"
          :resolver-class-name="targetResolverClassName"
          :resolver-label="targetResolverLabel"
          :default-delivery-channels="targetDefaultDeliveryChannels"
          :show-override="showDeliveryChannelsOverride"
          :initial-override-status="OverrideDeliveryChannels"
          @form-submit="handleNotificationPreferenceSubmit"
        />
      </ModalPresenter>
    </template>
  </Layout>
</template>
<script>
import Layout from 'tui/components/layouts/LayoutOneColumn';
import UserPreferencesTable from 'totara_notification/components/table/UserPreferencesTable';
import { notify } from 'tui/notifications';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import DeliveryPreferenceModal from 'totara_notification/components/modal/DeliveryPreferenceModal';

// GraphQL
import updateUserPreferenceMutation from 'totara_notification/graphql/update_notifiable_event_user_preference';
import updateDeliveryPreferenceChannels from 'totara_notification/graphql/update_user_delivery_channels';

export default {
  components: {
    Layout,
    UserPreferencesTable,
    ModalPresenter,
    DeliveryPreferenceModal,
  },

  props: {
    resolverPreferences: {
      type: Array,
      required: true,
    },
    extendedContext: {
      type: Object,
      default() {
        // Just return empty object by default.
        return {};
      },
      validate(prop) {
        if (
          !('component' in prop) ||
          !('area' in prop) ||
          !('itemId' in prop)
        ) {
          return false;
        }

        if (prop.component !== '' || prop.area !== '' || prop.itemId != 0) {
          // We only accept all the fields to have value. Not either of the fields.
          return prop.component !== '' && prop.area !== '' && prop.itemId != 0;
        }

        return true;
      },
    },
  },

  data() {
    return {
      userPreferencesList: this.resolverPreferences,
      groupedPreferences: [],
      deliveryPreferenceModal: {
        open: false,
      },
      targetResolverClassName: null,
      targetResolverLabel: null,
      targetDefaultDeliveryChannels: null,
      userPreferenceId: null,
      OverrideDeliveryChannels: false,
      showDeliveryChannelsOverride: true,
      preferences: this.resolverPreferences,
    };
  },

  computed: {
    groupPreferences() {
      let groupedResolvers = {};
      this.userPreferencesList.forEach(resolver => {
        const { component, plugin_name } = resolver;
        if (!groupedResolvers[plugin_name]) {
          groupedResolvers[plugin_name] = {
            component: component,
            plugin_name: plugin_name,
            resolvers: [],
          };
        }

        groupedResolvers[plugin_name].resolvers.push(resolver);
      });
      return Object.values(groupedResolvers);
    },
  },
  watch: {
    groupPreferences: {
      immediate: true,
      deep: true,
      handler(value) {
        this.groupedPreferences = value;
      },
    },
  },
  methods: {
    resetState() {
      // Reset the target event class name.
      this.targetResolverClassName = null;

      // Reset the target delivery channels
      this.targetDefaultDeliveryChannels = null;

      // Reset the target label
      this.targetResolverLabel = null;
    },
    /**
     * @param {Number} userId
     * @param {String} resolverClassname
     * @param {Boolean} isEnabled
     * @param {Number} userPreferenceId
     */
    async saveUserPreference({
      userId,
      resolverClassname,
      isEnabled,
      userPreferenceId,
    }) {
      try {
        const { data: result } = await this.$apollo.mutate({
          mutation: updateUserPreferenceMutation,
          variables: {
            user_id: userId,
            resolver_class_name: resolverClassname,
            extended_context: this.extendedContext,
            is_enabled: isEnabled,
            user_preference_id: userPreferenceId,
          },
        });
        this.updateResolverPreferenceList(
          result.notifiable_event_user_preference
        );
      } catch (e) {
        await notify({
          type: 'error',
          message: this.$str(
            'error_user_preference_permission',
            'totara_notification'
          ),
        });
      }
    },

    /**
     * @param {Object} updatedUserPreference
     */
    updateResolverPreferenceList(updatedUserPreference) {
      let deliveryPreferences = Object.assign([], this.groupedPreferences);
      let preferences = deliveryPreferences.find(
        el => el.plugin_name === updatedUserPreference.plugin_name
      );

      preferences.resolvers = preferences.resolvers.map(resolver => {
        if (
          resolver.resolver_class_name ===
            updatedUserPreference.resolver_class_name &&
          resolver.component === updatedUserPreference.component &&
          resolver.name === updatedUserPreference.name &&
          resolver.plugin_name === updatedUserPreference.plugin_name
        ) {
          return updatedUserPreference;
        } else {
          return resolver;
        }
      });
    },

    handleUpdateDeliveryPreferences({
      resolverClassName,
      resolverLabel,
      defaultDeliveryChannels,
      OverrideDeliveryChannels,
      userPreferenceId,
    }) {
      this.targetResolverClassName = resolverClassName;
      this.targetResolverLabel = resolverLabel;
      this.targetDefaultDeliveryChannels = defaultDeliveryChannels;
      this.OverrideDeliveryChannels = OverrideDeliveryChannels;
      this.userPreferenceId = userPreferenceId;
      this.deliveryPreferenceModal.open = true;
    },

    async handleNotificationPreferenceSubmit(
      { delivery_channels, resolver_class_name },
      overriddenStatus
    ) {
      // If override is disabled and all delivery channel selections and disabled
      // then pass null instead of an ampty array
      let channels = !overriddenStatus ? null : delivery_channels;

      await this.$apollo.mutate({
        mutation: updateDeliveryPreferenceChannels,
        variables: {
          resolver_class_name: resolver_class_name,
          extended_context: this.extendedContext,
          delivery_channels: channels,
          user_preference_id: this.userPreferenceId,
        },
        update: (
          proxy,
          { data: { notifiable_event_user_preference: updatedPreference } }
        ) => {
          let deliveryPreferences = Object.assign([], this.groupedPreferences);
          let preferences = deliveryPreferences.find(
            el => el.plugin_name === updatedPreference.plugin_name
          );

          preferences.resolvers = preferences.resolvers.map(resolver => {
            if (
              resolver.resolver_class_name ===
                updatedPreference.resolver_class_name &&
              resolver.name === updatedPreference.name
            ) {
              return updatedPreference;
            }
            return resolver;
          });
        },
      });
      this.deliveryPreferenceModal.open = false;
    },
  },
};
</script>

<lang-strings>
{
  "totara_notification": [
    "error_user_preference_permission",
    "user_preferences_page_title",
    "delivery_preferences"
  ]
}
</lang-strings>

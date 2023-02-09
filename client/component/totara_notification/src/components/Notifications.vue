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

  @author Steve Barnett <steve.barnett@totaralearning.com>
  @module totara_notification
-->

<template>
  <Loader class="tui-notifications" :loading="$apollo.loading">
    <div v-if="!$apollo.loading">
      <NotificationTable
        class="tui-notifications__table"
        :event-resolvers="eventResolvers"
        :context-id="parseInt(contextId)"
        :extended-context="extendedContext"
        :show-delivery-preference-option="canChangeDeliveryChannelDefaults"
        @status-toggle="statusToggle"
        @create-custom-notification="handleCreateCustomNotification"
        @edit-notification="handleEditNotification"
        @delete-notification="confirmDeleteNotification"
        @update-delivery-preferences="handleUpdateDeliveryPreferences"
      />
    </div>

    <div class="tui-notifications__modals">
      <ModalPresenter
        :open="modal.open"
        @request-close="modal.open = false"
        @close-complete="resetState"
      >
        <NotificationPreferenceModal
          :context-id="contextId"
          :extended-context="extendedContext"
          :resolver-class-name="targetResolverClassName"
          :preference="targetPreference || undefined"
          :parent-value="
            targetPreference ? targetPreference.parent_value : null
          "
          :title="modal.title"
          :valid-schedule-types="targetScheduleTypes"
          :available-recipients="targetAvailableRecipients"
          :default-delivery-channels="targetDefaultDeliveryChannels"
          :additional-criteria-component="targetadditionalCriteriaComponent"
          :preferred-editor-format="
            preferredEditorFormat !== null
              ? Number(preferredEditorFormat)
              : null
          "
          @form-submit="handleFormSubmit"
        />
      </ModalPresenter>

      <DeleteConfirmationModal
        :open="deleteModal.open"
        :title="deleteNotificationTitle"
        :confirm-button-text="$str('delete', 'core')"
        :loading="deleting"
        @confirm="deleteNotification"
        @cancel="deleteModal.open = false"
      >
        <template>
          <p>{{ $str('delete_confirm_message', 'totara_notification') }}</p>
        </template>
      </DeleteConfirmationModal>

      <ModalPresenter
        v-if="canChangeDeliveryChannelDefaults"
        :open="deliveryPreferenceModal.open"
        @request-close="deliveryPreferenceModal.open = false"
        @close-complete="resetState"
      >
        <DeliveryPreferenceModal
          :context-id="contextId"
          :resolver-class-name="targetResolverClassName"
          :resolver-label="targetResolverLabel"
          :default-delivery-channels="targetDefaultDeliveryChannels"
          :additional-criteria-component="targetadditionalCriteriaComponent"
          :title="$str('edit_delivery_preferences', 'totara_notification')"
          @form-submit="handleNotificationPreferenceSubmit"
        />
      </ModalPresenter>
    </div>
  </Loader>
</template>
<script>
import Loader from 'tui/components/loading/Loader';
import NotificationTable from 'totara_notification/components/table/NotificationTable';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import NotificationPreferenceModal from 'totara_notification/components/modal/NotificationPreferenceModal';
import DeleteConfirmationModal from 'tui/components/modal/ConfirmationModal';
import DeliveryPreferenceModal from 'totara_notification/components/modal/DeliveryPreferenceModal';
import { notify } from 'tui/notifications';

// GraphQL queries.
import getEventResolvers from 'totara_notification/graphql/event_resolvers_v2';
import createCustomNotification from 'totara_notification/graphql/create_custom_notification_preference_v2';
import overrideNotification from 'totara_notification/graphql/override_notification_preference_v2';
import updateNotification from 'totara_notification/graphql/update_notification_preference_v2';
import deleteNotification from 'totara_notification/graphql/delete_notification_preference';
import updateDefaultDeliveryChannels from 'totara_notification/graphql/update_notifiable_event_default_delivery_channels';
import updateNotificationStatus from 'totara_notification/graphql/toggle_notifiable_event_v2';

const MODAL_STATE_CREATE = 'create';
const MODAL_STATE_UPDATE = 'update';

export default {
  components: {
    Loader,
    NotificationTable,
    ModalPresenter,
    NotificationPreferenceModal,
    DeleteConfirmationModal,
    DeliveryPreferenceModal,
  },

  props: {
    contextId: {
      type: [Number, String],
      required: true,
    },

    preferredEditorFormat: [Number, String],

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

    canChangeDeliveryChannelDefaults: Boolean,
  },

  apollo: {
    eventResolvers: {
      query: getEventResolvers,
      variables() {
        return {
          extended_context: {
            context_id: this.contextId,
            component: this.extendedContext.component,
            area: this.extendedContext.area,
            item_id: this.extendedContext.itemId,
          },
        };
      },

      update({ resolvers }) {
        let result = {};
        resolvers.forEach(resolver => {
          const { component, plugin_name, recipients } = resolver;
          if (!result[plugin_name]) {
            result[plugin_name] = {
              component: component,
              plugin_name: plugin_name,
              resolvers: [],
              recipients: recipients,
            };
          }

          result[plugin_name].resolvers.push(resolver);
        });

        return Object.values(result);
      },
    },
  },

  data() {
    return {
      modal: {
        open: false,
        title: '',
        state: null,
      },
      deleteModal: {
        open: false,
      },
      deliveryPreferenceModal: {
        open: false,
      },
      deleting: false,
      targetResolverClassName: null,
      targetDeletePreference: null,
      targetPreference: null,
      targetScheduleTypes: [],
      targetAvailableRecipients: [],
      targetDefaultDeliveryChannels: [],
      targetadditionalCriteriaComponent: null,
      targetResolverLabel: null,
    };
  },

  computed: {
    deleteNotificationTitle() {
      let title = '';
      if (this.targetDeletePreference) {
        title = this.$str(
          'delete_confirm_title',
          'totara_notification',
          this.targetDeletePreference.title
        );
      }
      return title;
    },
    queryVariables() {
      return {
        extended_context: {
          context_id: this.contextId,
          component: this.extendedContext.component,
          area: this.extendedContext.area,
          item_id: this.extendedContext.itemId,
        },
      };
    },
  },

  methods: {
    /**
     * A method to call when we are closing down the modal.
     * As this function will try to reset the state of several variables,
     * when the modal is closed.
     */
    resetState() {
      // Reset the target event class name.
      this.targetResolverClassName = null;

      // Then the target preference.
      this.targetPreference = null;

      // Reset the modal title
      this.modal.title = '';

      // Reset the target schedule types
      this.targetScheduleTypes = [];

      // Reset the target delivery channels
      this.targetDefaultDeliveryChannels = [];

      // Reset the additional criteria component.
      this.targetadditionalCriteriaComponent = null;

      // Reset the target label
      this.targetResolverLabel = null;
    },

    /**
     * @param {String} resolverClassName
     * @param {Array} scheduleTypes
     * @param {Array} recipients
     * @param {Array} deliveryChannels
     * @param {String} additionalCriteriaComponent
     */
    handleCreateCustomNotification({
      resolverClassName,
      scheduleTypes,
      recipients,
      deliveryChannels,
      additionalCriteriaComponent,
    }) {
      this.modal.title = this.$str(
        'create_custom_notification_title',
        'totara_notification'
      );
      this.modal.open = true;
      this.modal.state = MODAL_STATE_CREATE;

      this.targetResolverClassName = resolverClassName;
      this.targetScheduleTypes = scheduleTypes;
      this.targetAvailableRecipients = recipients;
      this.targetDefaultDeliveryChannels = deliveryChannels;
      this.targetadditionalCriteriaComponent = additionalCriteriaComponent;
    },

    /**
     * @param {Object} oldPreference
     * @param {Array} scheduleTypes
     * @param {Array} recipients
     * @param {Array} deliveryChannels
     */
    async handleEditNotification(
      oldPreference,
      scheduleTypes,
      recipients,
      deliveryChannels,
      additionalCriteriaComponent
    ) {
      this.targetPreference = await this.getOverriddenPreference(oldPreference);
      this.targetResolverClassName = this.targetPreference.resolver_class_name;
      this.targetScheduleTypes = scheduleTypes;
      this.targetAvailableRecipients = recipients;
      this.targetDefaultDeliveryChannels = deliveryChannels;
      this.targetadditionalCriteriaComponent = additionalCriteriaComponent;

      this.modal.title = this.$str('edit_notification', 'totara_notification');
      this.modal.open = true;
      this.modal.state = MODAL_STATE_UPDATE;
    },

    /**
     * @param {String} resolverClassName
     * @param {String} resolverLabel
     * @param {Array} defaultDeliveryChannels
     */
    async handleUpdateDeliveryPreferences({
      resolverClassName,
      resolverLabel,
      defaultDeliveryChannels,
    }) {
      this.targetResolverClassName = resolverClassName;
      this.targetResolverLabel = resolverLabel;
      this.targetDefaultDeliveryChannels = defaultDeliveryChannels;
      this.deliveryPreferenceModal.open = true;
    },

    /**
     * Delete notifications
     */
    async deleteNotification() {
      // If the modal is in the process of closing, skip
      if (!this.deleteModal.open) {
        return;
      }

      this.deleting = true;
      await this.handleDeleteNotification(this.targetDeletePreference);
      this.deleteModal.open = false;
      notify({
        type: 'success',
        message: this.$str('delete_success', 'totara_notification'),
      });

      this.deleting = false;
    },

    /**
     * @param {Object} notificationPreference
     */
    async handleDeleteNotification(notificationPreference) {
      await this.$apollo.mutate({
        mutation: deleteNotification,
        variables: {
          id: notificationPreference.id,
        },
        update: proxy => {
          let { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          const {
            extended_context,
            id,
            resolver_class_name: className,
          } = notificationPreference;
          const { component } = extended_context;

          resolvers = Object.assign([], resolvers);
          resolvers = resolvers.map(resolver => {
            const { notification_preferences: preferences } = resolver;
            const components = preferences.map(
              preference =>
                preference.extended_context &&
                preference.extended_context.component
            );
            if (
              components.includes(component) &&
              resolver.class_name === className
            ) {
              resolver = Object.assign({}, resolver);
              resolver.notification_preferences = preferences.filter(
                preference => preference.id !== id
              );
              return resolver;
            }
            return resolver;
          });

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: { resolvers: resolvers },
          });
        },
      });
    },

    /**
     * @param {Object} deletePreference
     */
    confirmDeleteNotification(notificationPreference) {
      this.targetDeletePreference = notificationPreference;
      this.deleteModal.open = true;
    },

    /**
     * @param {Object} formValue
     */
    async handleFormSubmit(formValue) {
      if (this.modal.state === MODAL_STATE_CREATE) {
        await this.createCustomNotification(formValue);
      } else if (this.modal.state === MODAL_STATE_UPDATE) {
        await this.updateNotification(formValue);
      } else {
        throw new Error('The modal state is invalid');
      }

      this.modal.open = false;

      notify({
        type: 'success',
        message:
          this.modal.state === MODAL_STATE_CREATE
            ? this.$str('saved_notification', 'totara_notification')
            : this.$str('updated_notification', 'totara_notification'),
      });
    },

    /**
     * @param {String} subject
     * @param {String} body
     * @param {String} title
     * @param {String} additional_criteria
     * @param {Number} body_format
     * @param {String} resolver_class_name
     * @param {String} schedule_type
     * @param {Number} schedule_offset
     * @param {Number} subject_format
     * @param {String[]} recipient
     * @param {Boolean} enabled
     * @param {Array}  forced_delivery_channels
     */
    async createCustomNotification({
      subject,
      body,
      title,
      additional_criteria,
      body_format,
      resolver_class_name,
      schedule_type,
      schedule_offset,
      subject_format,
      recipients,
      enabled,
      forced_delivery_channels,
    }) {
      const variables = {
        body,
        subject,
        title,
        additional_criteria,
        body_format,
        resolver_class_name,
        subject_format,
        context_id: this.contextId,
        schedule_type,
        schedule_offset,
        recipients,
        enabled,
        forced_delivery_channels,
      };

      // When area, component and item id are all set together, we pass them to mutation.
      if (
        !!this.extendedContext.area &&
        !!this.extendedContext.component &&
        !!this.extendedContext.itemId
      ) {
        variables.extended_context_area = this.extendedContext.area;
        variables.extended_context_component = this.extendedContext.component;
        variables.extended_context_item_id = this.extendedContext.itemId;
      }

      await this.$apollo.mutate({
        mutation: createCustomNotification,
        variables,
        update: (
          proxy,
          { data: { notification_preference: notificationPreference } }
        ) => {
          const { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          const {
            resolver_plugin_name: pluginName,
            resolver_class_name: className,
          } = notificationPreference;

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: {
              resolvers: resolvers.map(resolver => {
                if (
                  resolver.plugin_name === pluginName &&
                  resolver.class_name === className
                ) {
                  resolver = Object.assign({}, resolver);

                  const { notification_preferences: preferences } = resolver;

                  resolver.notification_preferences = [
                    notificationPreference,
                  ].concat(preferences);
                }

                return resolver;
              }),
            },
          });
        },
      });
    },

    /**
     * @param {Object} oldPreference
     * @return {Object}
     */
    async getOverriddenPreference(oldPreference) {
      if (oldPreference.extended_context.context_id == this.contextId) {
        return oldPreference;
      }

      // The preference is in different context, hence we are going to
      // create a blank overridden notification preference record and use it.
      const {
        data: { notification_preference },
      } = await this.$apollo.mutate({
        mutation: overrideNotification,
        variables: {
          context_id: this.contextId,
          resolver_class_name: oldPreference.resolver_class_name,
          extended_context_component: this.extendedContext.component,
          extended_context_area: this.extendedContext.area,
          extended_context_item_id: this.extendedContext.itemId,
          ancestor_id: oldPreference.ancestor_id
            ? oldPreference.ancestor_id
            : oldPreference.id,
        },
        update: (
          proxy,
          { data: { notification_preference: overriddenPreference } }
        ) => {
          const { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          const {
            resolver_plugin_name: pluginName,
            resolver_class_name: className,
            parent_id: parentId,
          } = overriddenPreference;

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: {
              resolvers: resolvers.map(resolver => {
                if (
                  resolver.plugin_name === pluginName &&
                  resolver.class_name === className
                ) {
                  resolver = Object.assign({}, resolver);
                  const { notification_preferences: preferences } = resolver;

                  resolver.notification_preferences = preferences.map(
                    oldPreference => {
                      return oldPreference.id == parentId
                        ? overriddenPreference
                        : oldPreference;
                    }
                  );
                }

                return resolver;
              }),
            },
          });
        },
      });

      return notification_preference;
    },

    /**
     * @param {String} subject
     * @param {String} title
     * @param {String} additional_criteria
     * @param {String} body
     * @param {Number} body_format
     * @param {String} schedule_type
     * @param {Number} schedule_offset
     * @param {Number} subject_format
     * @param {String[]} recipients
     * @param {Boolean} enabled
     * @param {String[]} forced_delivery_channels
     */
    async updateNotification({
      subject,
      title,
      additional_criteria,
      body,
      body_format,
      schedule_type,
      schedule_offset,
      subject_format,
      recipients,
      enabled,
      forced_delivery_channels,
    }) {
      if (!this.targetPreference) {
        throw new Error('Cannot run update while target preference is empty');
      }

      await this.$apollo.mutate({
        mutation: updateNotification,
        variables: {
          id: this.targetPreference.id,
          additional_criteria,
          subject,
          body,
          body_format,
          subject_format,
          // Note that we don't want NULL here, but undefined, because we would want the graphql
          // to exclude the field title when updating a custom notification at a very specific context.
          title:
            this.targetPreference.is_custom && !this.targetPreference.parent_id
              ? title
              : undefined,
          schedule_type,
          schedule_offset,
          recipients,
          enabled,
          forced_delivery_channels,
        },
        update: (
          proxy,
          { data: { notification_preference: updatedPreference } }
        ) => {
          const { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          const {
            resolver_plugin_name: pluginName,
            resolver_class_name: className,
          } = updatedPreference;

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: {
              resolvers: resolvers.map(resolver => {
                if (
                  resolver.plugin_name === pluginName &&
                  resolver.class_name === className
                ) {
                  resolver = Object.assign({}, resolver);

                  const { notification_preferences: preferences } = resolver;

                  resolver.notification_preferences = preferences.map(
                    oldPreference => {
                      return oldPreference.id == updatedPreference.id
                        ? updatedPreference
                        : oldPreference;
                    }
                  );
                }

                return resolver;
              }),
            },
          });
        },
      });
    },

    /**
     *
     * @param {Object} default_delivery_channels
     * @param {String} resolver_class_name
     * @returns {Promise<void>}
     */
    async handleNotificationPreferenceSubmit({
      delivery_channels,
      resolver_class_name,
    }) {
      await this.$apollo.mutate({
        mutation: updateDefaultDeliveryChannels,
        variables: {
          resolver_class_name,
          default_delivery_channels: delivery_channels,
        },
        update: (proxy, { data: { default_delivery_channels } }) => {
          let { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          resolvers = Object.assign([], resolvers);
          resolvers = resolvers.map(resolver => {
            if (resolver.class_name === resolver_class_name) {
              resolver = Object.assign({}, resolver);
              resolver.default_delivery_channels = default_delivery_channels;
            }

            return resolver;
          });

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: {
              resolvers,
            },
          });
        },
      });
      this.deliveryPreferenceModal.open = false;
    },

    async statusToggle({ value, resolver }) {
      await this.$apollo.mutate({
        mutation: updateNotificationStatus,
        variables: {
          resolver_class_name: resolver.class_name,
          extended_context: {
            context_id: this.contextId,
            component: this.extendedContext.component,
            area: this.extendedContext.area,
            item_id: this.extendedContext.itemId,
          },
          is_enabled: value,
        },
        update: (proxy, { data: { notifiable_event: notifiableEvent } }) => {
          const { resolvers } = proxy.readQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
          });

          const { notification_preferences: preferences } = resolver;

          proxy.writeQuery({
            query: getEventResolvers,
            variables: this.queryVariables,
            data: {
              resolvers: resolvers.map(resolver => {
                if (resolver.class_name === notifiableEvent.class_name) {
                  resolver = Object.assign({}, resolver);

                  resolver.status = Object.assign({}, resolver.status, {
                    is_enabled: value,
                    __typename: resolver.status.__typename,
                  });

                  resolver.notification_preferences = preferences.map(
                    preference => preference
                  );
                }
                return resolver;
              }),
            },
          });
        },
      });
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "delete"
    ],
    "totara_notification": [
      "create_custom_notification_title",
      "delete_confirm_title",
      "delete_confirm_message",
      "delete_success",
      "edit_delivery_preferences",
      "edit_notification",
      "saved_notification",
      "updated_notification"
    ]
  }
</lang-strings>

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
  <div>
    <div class="tui-notificationTable__actions">
      <CollapsibleGroupToggle
        v-show="!singleEventResolver"
        v-model="expanded"
        class="tui-notificationTable__actionsExpand"
        :align-end="false"
        :transparent="false"
      />
      <a
        v-if="canAudit"
        class="tui-notificationTable__actionsAudit"
        :href="
          $url('/totara/notification/notification_event_log.php', auditParams)
        "
      >
        {{ $str('viewnotificationlogs', 'totara_notification') }}
      </a>
    </div>
    <div
      class="tui-notificationTable"
      :class="{
        'tui-notificationTable--singleEventResolver': singleEventResolver,
      }"
    >
      <component
        :is="singleEventResolver ? 'passthrough' : 'Collapsible'"
        v-for="eventResolver in eventResolvers"
        :key="eventResolver.plugin_name"
        v-model="expanded[eventResolver.plugin_name]"
        class="tui-notificationTable__collapsible"
        :label="eventResolver.plugin_name"
        :indent-contents="!singleEventResolver"
      >
        <Table
          :data="eventResolver.resolvers"
          :expandable-rows="true"
          :expand-multiple-rows="true"
          :border-bottom-hidden="true"
          :hover-off="true"
          :indent-expanded-contents="true"
          :stealth-expanded="true"
        >
          <template v-slot:header-row>
            <ExpandCell :hidden="true" :header="true" />
            <HeaderCell size="4">
              {{ $str('notifiable_events', 'totara_notification') }}
            </HeaderCell>
            <HeaderCell size="4">
              <div class="tui-notificationTable__header">
                {{ $str('delivery_channels', 'totara_notification') }}
                <InfoIconButton
                  :aria-label="
                    $str(
                      'delivery_preferences_helptext_aria',
                      'totara_notification'
                    )
                  "
                >
                  {{
                    $str(
                      'default_delivery_preferences_helptext',
                      'totara_notification'
                    )
                  }}
                </InfoIconButton>
              </div>
            </HeaderCell>

            <HeaderCell align="start" size="2">
              <div class="tui-notificationTable__header">
                {{ $str('enabled', 'totara_notification') }}
                <InfoIconButton
                  :aria-label="
                    $str('enabled_helptext_aria', 'totara_notification')
                  "
                >
                  {{ $str('enabled_helptext', 'totara_notification') }}
                </InfoIconButton>
              </div>
            </HeaderCell>
            <HeaderCell size="1">
              <span class="sr-only">
                {{ $str('actions', 'core') }}
              </span>
            </HeaderCell>
          </template>
          <template v-slot:row="{ expand, expandState, row: resolver }">
            <ExpandCell
              :aria-label="resolver.name"
              :expand-state="expandState"
              :text="$str('notifications', 'totara_notification')"
              @click="expand()"
            />
            <Cell
              size="4"
              :column-header="$str('notifiable_events', 'totara_notification')"
            >
              {{ resolver.name }}
              <Popover
                v-if="resolver.warnings.length"
                :triggers="['click', 'focus']"
                class="tui-notificationTable__trigger"
              >
                <template v-slot:trigger>
                  <ButtonIcon
                    class="tui-notificationTable__triggerWarning"
                    :styleclass="{
                      transparentNoPadding: true,
                    }"
                    :aria-label="
                      $str('notification_warning', 'totara_notification')
                    "
                  >
                    <WarningIcon />
                  </ButtonIcon>
                </template>
                <ul class="tui-notificationTable__triggerWarnings">
                  <li
                    v-for="(warning, index) in resolver.warnings"
                    :key="index"
                  >
                    {{ warning }}
                  </li>
                </ul>
              </Popover>
            </Cell>
            <Cell
              size="4"
              :column-header="$str('delivery_channels', 'totara_notification')"
            >
              {{
                display_delivery_channels(resolver.default_delivery_channels)
              }}
            </Cell>
            <Cell
              align="start"
              size="2"
              :column-header="$str('enabled', 'totara_notification')"
            >
              <ToggleSwitch
                :aria-label="
                  $str('enable_status', 'totara_notification', resolver.name)
                "
                :value="resolver.status.is_enabled"
                :toggle-only="true"
                @input="onStatusToggle($event, resolver)"
              />
            </Cell>
            <Cell align="end" size="1">
              <NotifiableEventAction
                :resolver-name="resolver.name"
                :show-delivery-preference-option="showDeliveryPreferenceOption"
                :can-audit="resolver.interactor.can_audit"
                :can-manage="resolver.interactor.can_manage"
                :resolver-class-name="resolver.class_name"
                :extended-context="extendedContext"
                :context-id="contextId"
                @create-custom-notification="
                  $emit('create-custom-notification', {
                    resolverClassName: resolver.class_name,
                    scheduleTypes: resolver.valid_schedules,
                    recipients: resolver.recipients,
                    deliveryChannels: resolver.default_delivery_channels,
                    additionalCriteriaComponent:
                      resolver.additional_criteria_component,
                  })
                "
                @update-delivery-preferences="
                  $emit('update-delivery-preferences', {
                    resolverClassName: resolver.class_name,
                    resolverLabel: resolver.name,
                    defaultDeliveryChannels: resolver.default_delivery_channels,
                    additionalCriteriaComponent:
                      resolver.additional_criteria_component,
                  })
                "
              />
            </Cell>
          </template>
          <template v-slot:expand-content="{ row: resolver }">
            <template v-if="resolver.notification_preferences.length">
              <div
                v-for="pref in resolver.notification_preferences"
                :key="pref.id"
                class="tui-notificationTable__notification"
              >
                <div class="tui-notificationTable__notificationData">
                  <div
                    class="tui-notificationTable__notificationUnit tui-notificationTable__notificationUnit--title"
                  >
                    <div class="tui-notificationTable__notificationLabel">
                      {{ $str('notification', 'totara_notification') }}
                    </div>
                    <div class="tui-notificationTable__notificationContent">
                      {{ pref.title }}
                    </div>
                  </div>
                  <div
                    class="tui-notificationTable__notificationUnit tui-notificationTable__notificationUnit--type"
                  >
                    <div class="tui-notificationTable__notificationLabel">
                      {{ $str('type', 'totara_notification') }}
                    </div>
                    <div class="tui-notificationTable__notificationContent">
                      <template
                        v-if="
                          isDefinedInThisContext(pref) &&
                            pref.is_custom &&
                            pref.parent_id == null
                        "
                      >
                        {{ $str('custom', 'totara_notification') }}
                      </template>
                      <template
                        v-else-if="
                          isDefinedInThisContext(pref) && isOverride(pref)
                        "
                      >
                        {{ $str('amended', 'totara_notification') }}
                      </template>
                      <template v-else-if="isSystemContext">
                        {{ $str('factory', 'totara_notification') }}
                      </template>
                      <template v-else>
                        {{ $str('inherited', 'totara_notification') }}
                      </template>
                    </div>
                  </div>
                  <div
                    class="tui-notificationTable__notificationUnit tui-notificationTable__notificationUnit--recipient"
                  >
                    <div class="tui-notificationTable__notificationLabel">
                      {{ $str('recipient', 'totara_notification') }}
                    </div>
                    <div class="tui-notificationTable__notificationContent">
                      {{ displayRecipients(pref.recipients) }}
                    </div>
                  </div>
                  <div
                    class="tui-notificationTable__notificationUnit tui-notificationTable__notificationUnit--schedule"
                  >
                    <div class="tui-notificationTable__notificationLabel">
                      {{ $str('schedule', 'totara_notification') }}
                    </div>
                    <div class="tui-notificationTable__notificationContent">
                      {{ pref.schedule_label }}
                    </div>
                  </div>
                  <div
                    class="tui-notificationTable__notificationUnit tui-notificationTable__notificationUnit--status"
                  >
                    <div class="tui-notificationTable__notificationLabel">
                      {{ $str('status', 'core') }}
                    </div>
                    <div class="tui-notificationTable__notificationContent">
                      <template v-if="resolver.status.is_enabled">
                        <template v-if="pref.enabled">
                          {{ $str('enabled', 'totara_notification') }}
                        </template>
                        <template v-if="!pref.enabled">
                          {{ $str('disabled', 'totara_notification') }}
                        </template>
                      </template>
                      <template v-else>
                        <template v-if="pref.enabled">
                          <del>{{
                            $str('enabled', 'totara_notification')
                          }}</del>
                        </template>
                        <template v-if="!pref.enabled">
                          <del>{{
                            $str('disabled', 'totara_notification')
                          }}</del>
                        </template>
                      </template>
                    </div>
                  </div>
                </div>
                <div
                  v-if="resolver.interactor.can_manage"
                  class="tui-notificationTable__notificationAction"
                >
                  <div class="tui-notificationTable__notificationContent">
                    <NotificationAction
                      :preference-title="pref.title"
                      :is-deletable="
                        isDefinedInThisContext(pref) &&
                          pref.is_custom &&
                          pref.parent_id == null
                      "
                      @edit-notification="
                        $emit(
                          'edit-notification',
                          pref,
                          resolver.valid_schedules,
                          resolver.recipients,
                          resolver.default_delivery_channels,
                          resolver.additional_criteria_component
                        )
                      "
                      @delete-notification="$emit('delete-notification', pref)"
                    />
                  </div>
                </div>
              </div>
            </template>
            <template v-else>
              {{ $str('no_notifications', 'totara_notification') }}

              <Button
                v-if="resolver.interactor.can_manage"
                :text="$str('create_notification', 'totara_notification')"
                :aria-label="
                  $str(
                    'create_notification_for_event',
                    'totara_notification',
                    resolver.name
                  )
                "
                @click="
                  $emit('create-custom-notification', {
                    resolverClassName: resolver.class_name,
                    scheduleTypes: resolver.valid_schedules,
                    recipients: resolver.recipients,
                    deliveryChannels: resolver.default_delivery_channels,
                    additionalCriteriaComponent:
                      resolver.additional_criteria_component,
                  })
                "
              />
            </template>
          </template>
        </Table>
      </component>
    </div>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Collapsible from 'tui/components/collapsible/Collapsible';
import CollapsibleGroupToggle from 'tui/components/collapsible/CollapsibleGroupToggle';
import Cell from 'tui/components/datatable/Cell';
import ExpandCell from 'tui/components/datatable/ExpandCell';
import HeaderCell from 'tui/components/datatable/HeaderCell';
import Table from 'tui/components/datatable/Table';
import NotificationAction from 'totara_notification/components/action/NotificationAction';
import NotifiableEventAction from 'totara_notification/components/action/NotifiableEventAction';
import ToggleSwitch from 'tui/components/toggle/ToggleSwitch';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import Popover from 'tui/components/popover/Popover';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import WarningIcon from 'tui/components/icons/Warning';

export default {
  components: {
    Button,
    Collapsible,
    CollapsibleGroupToggle,
    Cell,
    ExpandCell,
    HeaderCell,
    Table,
    NotificationAction,
    NotifiableEventAction,
    ToggleSwitch,
    InfoIconButton,
    Popover,
    ButtonIcon,
    WarningIcon,
  },

  props: {
    contextId: {
      type: Number,
      required: true,
    },

    eventResolvers: {
      type: Array,
      default: () => [],
      validator(prop) {
        return prop.every(preference => {
          return (
            'component' in preference &&
            'resolvers' in preference &&
            'recipients' in preference &&
            'plugin_name' in preference
          );
        });
      },
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

    showDeliveryPreferenceOption: Boolean,
  },

  data() {
    const singleEventResolver = this.eventResolvers.length === 1;
    const expanded = {};

    if (singleEventResolver) {
      expanded[this.eventResolvers[0].plugin_name] = true;
    } else {
      this.eventResolvers.forEach(
        eventResolver => (expanded[eventResolver.plugin_name] = false)
      );
    }

    return {
      singleEventResolver,
      expanded,
    };
  },

  computed: {
    /**
     * Whether the current context is the system context
     * @return {boolean}
     */
    isSystemContext() {
      return (
        this.contextId == 1 &&
        (!this.extendedContext ||
          !('component' in this.extendedContext) ||
          this.extendedContext.component == '')
      );
    },

    /**
     * Can this user audit any of the resolvers
     *
     * @returns {Boolean}
     */
    canAudit() {
      return this.eventResolvers.some(resolvers => {
        return resolvers.resolvers.some(resolver => {
          return resolver.interactor.can_audit;
        });
      });
    },

    /**
     * creates the audit parameters
     *
     * @returns {Object} The parameters required for the audit page
     */
    auditParams() {
      let result = { context_id: this.contextId };

      if (
        this.extendedContext &&
        this.extendedContext.component &&
        this.extendedContext.area &&
        this.extendedContext.itemId
      ) {
        result.component = this.extendedContext.component;
        result.area = this.extendedContext.area;
        result.item_id = this.extendedContext.itemId;
      }

      return result;
    },
  },

  methods: {
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

    onStatusToggle(value, resolver) {
      const properties = {
        value: value,
        resolver: resolver,
      };
      this.$emit('status-toggle', properties);
    },

    /**
     * Determine if the given extended context is a natural context, meaning that the component, area and item_id are
     * not specified.
     * @param extendedContext
     * @returns {boolean}
     */
    isNaturalContext(extendedContext) {
      return (
        !extendedContext ||
        !('component' in extendedContext) ||
        extendedContext.component == ''
      );
    },

    /**
     * Determine whether this notification preference is
     * @param pref
     * @returns {boolean}
     */
    isDefinedInThisContext(pref) {
      if (pref.extended_context.context_id != this.contextId) {
        // Obviously not if context doesn't match.
        return false;
      }
      if (
        this.isNaturalContext(pref.extended_context) &&
        this.isNaturalContext(this.extendedContext)
      ) {
        // If they are both natural then they must match.
        return true;
      }
      if (
        this.isNaturalContext(pref.extended_context) ||
        this.isNaturalContext(this.extendedContext)
      ) {
        // If only one of them is natural then the other is not natural so they can't be the same.
        return false;
      }
      return (
        pref.extended_context.component == this.extendedContext.component &&
        pref.extended_context.area == this.extendedContext.area &&
        pref.extended_context.item_id == this.extendedContext.itemId
      );
    },

    /**
     * Whether the notification preference is an override of a higher context notification
     * @return {boolean}
     */
    isOverride(pref) {
      return (
        pref.overridden_additional_criteria ||
        pref.overridden_body ||
        pref.overridden_enabled ||
        pref.overridden_forced_delivery_channels ||
        pref.overridden_recipient ||
        pref.overridden_schedule ||
        pref.overridden_subject
      );
    },

    /**
     * Returns a human readible string of recipents for the notification
     *
     * @param {Array} recipients an array of recipients
     * @returns {String}
     */
    displayRecipients(recipients) {
      return recipients
        .map(recipient => {
          return recipient.name;
        })
        .join(', ');
    },
  },
};
</script>
<lang-strings>
{
  "totara_notification": [
    "amended",
    "notifiable_events",
    "recipient",
    "schedule",
    "custom",
    "delivery_channels",
    "disable",
    "disabled",
    "delivery_preferences_helptext_aria",
    "default_delivery_preferences_helptext",
    "enabled",
    "enabled_helptext",
    "enabled_helptext_aria",
    "enable_status",
    "factory",
    "inherited",
    "notification",
    "notifications",
    "notification_warning",
    "create_notification",
    "create_notification_for_event",
    "no_notifications",
    "type",
    "viewnotificationlogs"
  ],
  "core": [
    "actions",
    "status"
  ]
}
</lang-strings>

<style lang="scss">
.tui-notificationTable {
  margin-top: var(--gap-4);

  &__actions {
    display: flex;
  }

  &__actionsExpand {
    flex-grow: 1;
  }

  &__actionsAudit {
    margin-top: auto;
  }

  &__collapsible + &__collapsible {
    border-top: var(--border-width-normal) solid var(--color-neutral-1);
  }

  &__header {
    display: flex;
  }

  &__trigger {
    display: inline-block;
  }

  &__triggerWarning {
    display: inline-block;
    color: var(--color-prompt-warning);

    &:focus,
    &:hover {
      color: var(--color-prompt-warning);
    }
  }

  &__triggerWarnings {
    margin: 0;
    list-style-type: none;
  }

  &__subTable {
    background-color: var(--color-text-disabled);
  }

  &__notification {
    display: flex;
    padding: var(--gap-2);
    border: 1px solid var(--color-neutral-5);
  }

  &__notificationData {
    flex-basis: 0;
    flex-grow: 17;
    flex-wrap: wrap;

    @media (min-width: $tui-screen-sm) {
      display: flex;
    }

    > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__notificationUnit {
    flex-basis: 100%;

    @media (min-width: $tui-screen-sm) {
      flex-basis: 0;
      margin-top: 0;
    }
  }

  @media (min-width: $tui-screen-sm) {
    &__notificationUnit--title,
    &__notificationUnit--type,
    &__notificationUnit--recipient {
      flex-grow: 4;
    }

    &__notificationUnit--schedule {
      flex-grow: 3;
    }

    &__notificationUnit--status {
      flex-grow: 2;
    }

    &__notificationUnit--action {
      flex-basis: 0;
      flex-grow: 1;
      align-self: center;
    }
  }

  &__notificationLabel {
    color: var(--color-neutral-6);
    font-size: var(--font-size-14);
  }
}
</style>

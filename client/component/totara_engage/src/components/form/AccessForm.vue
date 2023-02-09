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
  <Form
    v-if="!$apollo.loading"
    class="tui-engageAccessForm"
    :vertical="true"
    input-width="full"
    @submit.prevent="disabled ? true : done()"
  >
    <div :class="['tui-engageAccessForm__options', optionsWrapperCss]">
      <FormRow
        v-slot="{ id, labelId }"
        :label="$str('whocansee', 'totara_engage')"
      >
        <p>{{ $str('content_visibility_setting', 'totara_engage') }}</p>
        <AccessSelector
          :id="id"
          :aria-labelledby="labelId"
          :selected-access="access"
          :private-disabled="privateDisabled"
          :public-disabled="publicDisabled"
          :restricted-disabled="restrictedDisabled || !canShare"
          @change="access = $event"
        />
      </FormRow>

      <FormRow
        v-if="timeViewVisible"
        v-slot="{ id, labelId }"
        :label="$str('timetoread', 'totara_engage')"
        class="tui-engageAccessForm__time"
      >
        <TimeViewSelector
          :id="id"
          :selected-time="selectedTime"
          :aria-labelledby="labelId"
          @change="selectedTime = $event"
        />
      </FormRow>
    </div>

    <div
      v-if="accessPublic && !topicConfig.topicEnabled"
      class="tui-engageAccessForm__tag"
    >
      <EngageCallout />
    </div>

    <div v-if="!accessPrivate" class="tui-engageAccessForm__tagLists">
      <TopicsSelector
        v-if="accessPublic && topicConfig.topicEnabled"
        class="tui-engageAccessForm__tagList"
        :selected-topics="selectedTopics"
        @change="selectedTopics = $event"
      />

      <template v-if="canShare">
        <RecipientsSelector
          v-if="canShare"
          :item-id="itemId"
          :component="component"
          :access="access"
          :selected-users="selectedRecipients"
          :container="container"
          class="tui-engageAccessForm__tagList"
          @pick-recipient="selectedRecipients.push($event)"
          @remove-recipient="removeRecipient"
        />

        <SharedBoard :receipts="sharedTo" />
      </template>
    </div>

    <div class="tui-engageAccessForm__buttons">
      <ButtonGroup>
        <Button
          v-show="showBack"
          :text="$str('back', 'core')"
          :disabled="submitting"
          class="tui-engageAccessForm__back"
          @click="$emit('back')"
        />
      </ButtonGroup>

      <ButtonGroup>
        <LoadingButton
          :loading="submitting"
          :primary="true"
          :disabled="disabled"
          :text="doneButtonText"
          @click="done"
        />
        <CancelButton :disabled="submitting" @click="$emit('cancel')" />
      </ButtonGroup>
    </div>
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import CancelButton from 'tui/components/buttons/Cancel';
import { FormRow } from 'tui/components/uniform';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import TopicsSelector from 'totara_engage/components/form/access/EngageTopicsSelector';
import RecipientsSelector from 'totara_engage/components/form/access/RecipientsSelector';
import AccessSelector from 'totara_engage/components/form/access/AccessSelector';
import SharedBoard from 'totara_engage/components/form/SharedBoard';
import { AccessManager, TimeViewType } from 'totara_engage/index';
import { config } from 'tui/config';
import EngageCallout from 'totara_engage/components/callout/EngageCallout';

// GraphQL
import ShareRecipients from 'totara_engage/graphql/share_recipients';
import getTopicConfig from 'totara_topic/graphql/get_config';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';
import TimeViewSelector from 'totara_engage/components/form/access/TimeViewSelector';
import RecipientMixin from 'totara_engage/mixins/recipient_mixin';

const slice = Array.prototype.slice;

export default {
  components: {
    FormRow,
    AccessSelector,
    RecipientsSelector,
    Form,
    ButtonGroup,
    Button,
    LoadingButton,
    CancelButton,
    TopicsSelector,
    TimeViewSelector,
    SharedBoard,
    EngageCallout,
  },

  mixins: [ContainerMixin],

  apollo: {
    sharedTo: {
      query: ShareRecipients,
      variables() {
        return this.shareToVariables;
      },
      update({ recipients }) {
        return recipients.reduce(
          (obj, item) => {
            if (item.user) {
              obj['people'].push(item.user.fullname);
            }
            if (item.other) {
              obj['workspaces'].push(item.other.fullname);
            }
            return obj;
          },
          { people: [], workspaces: [] }
        );
      },
      skip() {
        //  We don't want the share recipients for workspaces and items that don't exist yet.
        return (
          this.shareToVariables.component === 'container_workspace' ||
          String(this.shareToVariables.itemid) === '0'
        );
      },
    },

    topicConfig: {
      query: getTopicConfig,
      variables() {
        let itemType = '';
        if (this.component === 'totara_playlist') {
          itemType = 'playlist';
        } else {
          itemType = 'engage_resource';
        }
        return {
          component: this.component,
          item_type: itemType,
        };
      },
      update({ config }) {
        return {
          topicEnabled: config.enabled,
        };
      },
    },
  },

  props: {
    showBack: Boolean,

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
      default: null,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    selectedTimeView: {
      type: String,
      default: null,
      validator(prop) {
        return TimeViewType.isValid(prop);
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

    doneButtonText: {
      type: String,
      default() {
        return this.$str('done', 'totara_engage');
      },
    },

    enableTimeView: {
      type: Boolean,
      default: false,
    },

    canShare: Boolean,
  },

  data() {
    return {
      access: this.selectedAccess,
      selectedRecipients: slice.call(this.selectedOptions.shares),
      selectedTime: this.selectedTimeView,
      selectedTopics: slice.call(this.selectedOptions.topics),
      sharedTo: { people: [], workspaces: [] },
      topicConfig: { topicEnabled: true },
    };
  },

  computed: {
    // Resource needs to inherit the container's shares.
    shareToVariables() {
      return {
        itemid: this.containerValues.instanceId || this.itemId,
        component: this.containerValues.component || this.component,
        theme: config.theme.name,
      };
    },

    disabled() {
      if (this.accessPublic && !this.topicConfig.topicEnabled) {
        return true;
      }
      if (
        !this.access ||
        this.submitting ||
        (this.enableTimeView && !this.selectedTime && !this.accessPrivate)
      ) {
        return true;
      } else if (this.accessPublic) {
        return 0 >= this.selectedTopics.length;
      } else if (this.accessRestricted) {
        if (
          this.sharedTo.people.length > 0 ||
          this.sharedTo.workspaces.length > 0
        ) {
          return false;
        }
        return 0 >= this.selectedRecipients.length;
      }

      return false;
    },

    accessPublic() {
      return null !== this.access && AccessManager.isPublic(this.access);
    },

    accessRestricted() {
      return null !== this.access && AccessManager.isRestricted(this.access);
    },

    accessPrivate() {
      // For private path, if the access is not set then it should be treated as private as well.
      return null === this.access || AccessManager.isPrivate(this.access);
    },

    optionsWrapperCss() {
      if (this.accessPrivate) {
        return 'tui-engageAccessForm__access--withoutTagLists';
      }

      return 'tui-engageAccessForm__access--withTagLists';
    },

    timeViewVisible() {
      return !this.accessPrivate && this.enableTimeView;
    },
  },

  mounted() {
    this.$apollo.queries.sharedTo.refetch();
  },

  methods: {
    /**
     *
     * @param {Number} id
     */
    removeTopic({ id }) {
      this.selectedTopics = this.selectedTopics.filter(
        topic => id !== topic.id
      );
    },

    /**
     *
     * @param {Object} oldRecipient
     */
    removeRecipient(oldRecipient) {
      this.selectedRecipients = this.selectedRecipients.filter(recipient => {
        return !RecipientMixin.compareRecipients(recipient, oldRecipient);
      });
    },

    done() {
      if (!this.accessPublic) {
        this.selectedTopics = [];
      }

      if (this.accessPrivate) {
        this.selectedRecipients = [];
        this.selectedTime = null;
      }

      this.$emit('done', {
        access: this.access,
        shares: this.selectedRecipients.map(recipient => {
          return {
            instanceid: recipient.instanceid,
            component: recipient.component,
            area: recipient.area,
          };
        }),
        timeView: this.selectedTime,
        topics: this.selectedTopics,
      });
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "back"
    ],

    "totara_core": [
      "settings"
    ],

    "totara_engage": [
      "content_visibility_setting",
      "done",
      "whocansee",
      "timetoread"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageAccessForm {
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: space-between;
  width: 100%;
  height: 100%;

  @media (max-width: $tui-screen-sm) {
    display: block;
    &__buttons {
      margin-bottom: var(--gap-12);
      padding-bottom: var(--gap-12);
    }
  }

  &__heading {
    @include tui-font-heading-small();
    margin-bottom: var(--gap-4);
  }

  &__options {
    &--withTagList {
      flex-basis: 35%;
    }

    &--withoutTagLists {
      flex-basis: 90%;
      flex-grow: 1;
    }
  }

  &__time {
    margin-top: var(--gap-4);
  }

  &__tagLists {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    margin-top: var(--gap-4);
  }

  &__tagList {
    margin-bottom: var(--gap-1);
  }

  &__buttons {
    display: flex;
    justify-content: space-between;
    margin-top: var(--gap-2);
  }

  &__back {
    margin-right: auto;
  }
}
</style>

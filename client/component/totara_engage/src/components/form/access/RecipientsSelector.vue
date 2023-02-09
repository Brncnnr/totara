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
  <div class="tui-engageSharedRecipientsSelector">
    <Label
      :label="shareLabel"
      :for-id="generatedId"
      class="tui-engageSharedRecipientsSelector__label"
    />
    <InfoIconButton
      :is-help-for="shareLabel"
      class="tui-engageSharedRecipientsSelector__icon"
    >
      {{ shareHelpInfo }}
    </InfoIconButton>
    <TagList
      :id="generatedId"
      :filter="query"
      :items="items"
      :input-placeholder="$str('enter_people_or_workspaces', 'totara_engage')"
      :tags="tags"
      :separator="true"
      :debounce-filter="true"
      @filter="find"
      @select="select"
      @remove="remove"
    >
      <template v-slot:item="{ item }">
        <div
          v-if="item.user"
          class="tui-engageSharedRecipientsSelector__profileContainer"
        >
          <MiniProfileCard
            :no-border="true"
            :no-padding="true"
            :read-only="true"
            :display="item.user.card_display"
          />
          <div
            v-if="getRecipientDetails(item, 'alreadyshared')"
            class="tui-engageSharedRecipientsSelector__profileContainer-badge"
          >
            <CheckSuccess />
            <span>{{ $str('alreadyshared', 'totara_engage') }}</span>
          </div>
        </div>

        <div v-else class="tui-engageSharedRecipientsSelector__recipient">
          <Avatar
            :src="getRecipientDetails(item, 'src')"
            :alt="getRecipientDetails(item, 'fullname')"
            size="xsmall"
          />
          <ul class="tui-engageSharedRecipientsSelector__recipient-summary">
            <li>{{ getRecipientDetails(item, 'fullname') }}</li>
            <li>{{ getRecipientDetails(item, 'summary') }}</li>
          </ul>
          <div
            v-if="getRecipientDetails(item, 'alreadyshared')"
            class="tui-engageSharedRecipientsSelector__recipient-badge"
          >
            <CheckSuccess />
            <span>{{ $str('alreadyshared', 'totara_engage') }}</span>
          </div>
        </div>
      </template>
    </TagList>
  </div>
</template>

<script>
import MiniProfileCard from 'tui/components/profile/MiniProfileCard';
import Avatar from 'tui/components/avatar/Avatar';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import CheckSuccess from 'tui/components/icons/CheckSuccess';
import Label from 'tui/components/form/Label';
import TagList from 'tui/components/tag/TagList';
import { AccessManager } from 'totara_engage/index';
import { config } from 'tui/config';

// GraphQL
import shareToRecipients from 'totara_engage/graphql/shareto_recipients';
import engageAdvancedFeatures from 'totara_engage/graphql/advanced_features';

// Mixin
import ContainerMixin from 'totara_engage/mixins/container_mixin';

export default {
  components: {
    Avatar,
    InfoIconButton,
    Label,
    TagList,
    CheckSuccess,
    MiniProfileCard,
  },

  mixins: [ContainerMixin],

  props: {
    itemId: {
      type: [Number, String],
      required: true,
    },

    component: {
      type: String,
      required: true,
    },

    access: {
      type: String,
      default: null,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },

    owned: {
      type: Boolean,
      default: true,
    },
  },

  apollo: {
    features: {
      query: engageAdvancedFeatures,
    },

    recipients: {
      query: shareToRecipients,
      fetchPolicy: 'network-only',
      variables() {
        return {
          itemid: this.itemId,
          component: this.component,
          search: this.query,
          access: this.access,
          theme: config.theme.name,
        };
      },

      result({ data: { recipients } }) {
        let tmp_recipients = [];

        // As preventing a tag from being manually removed isn't currently possible
        // we do not filter the this.containerRecipient item out.

        // ID is not enough to uniquely identify a specific recipient.
        tmp_recipients = recipients.map(recipient => {
          return Object.assign({}, recipient, {
            id:
              recipient.component +
              '/' +
              recipient.area +
              '/' +
              recipient.instanceid,
          });
        });

        // Filter the null value users
        tmp_recipients = tmp_recipients.filter(recipient => {
          if (recipient.area === 'user') {
            return recipient.user.card_display.display_fields.some(
              field => field.value != null
            );
          }
          // Return all workspaces as true
          return true;
        });

        this.recipients = tmp_recipients;
      },
    },
  },

  data() {
    return {
      query: '',
      recipients: [],
      publicTags: [],
      restrictedTags: [],
      features: {},
    };
  },

  computed: {
    tags() {
      return this.accessPublic ? this.publicTags : this.restrictedTags;
    },

    items() {
      return this.recipients.filter(
        recipient => !this.tags.some(tag => recipient.id === tag.id)
      );
    },

    containerRecipient() {
      if (this.container && this.container.autoShareRecipient) {
        return {
          instanceid: this.containerValues.instanceId,
          component: this.containerValues.component,
          area: this.containerValues.area,
          minimum_access: this.containerValues.access,
          other: {
            fullname: this.containerValues.name,
          },
        };
      }
      return false;
    },

    generatedId() {
      return this.$id();
    },

    featureWorkspaces() {
      return this.features && this.features.workspaces;
    },

    shareLabel() {
      const labels = this.featureWorkspaces
        ? [
            'resharetorecipientsworkspaces',
            'sharetorecipientsworkspaces',
            'sharetorecipientsoptionalworkspaces',
          ]
        : [
            'resharetorecipients',
            'sharetorecipients',
            'sharetorecipientsoptional',
          ];

      if (!this.owned) {
        return this.$str(labels[0], 'totara_engage');
      } else {
        return this.$str(
          this.accessRestricted ? labels[1] : labels[2],
          'totara_engage'
        );
      }
    },

    shareHelpInfo() {
      return this.$str(
        this.featureWorkspaces ? 'sharehelpinfoworkspaces' : 'sharehelpinfo',
        'totara_engage'
      );
    },

    accessPublic() {
      return AccessManager.isPublic(this.access);
    },

    accessRestricted() {
      return AccessManager.isRestricted(this.access);
    },
  },

  mounted() {
    if (this.containerRecipient) {
      this.select(this.containerRecipient);
    }
  },

  methods: {
    /**
     * @param {String} query
     */
    find(query) {
      if ('' === query) {
        this.query = query;
        return;
      }

      this.query = query;
      this.skip = false;

      this.$apollo.queries.recipients.refetch();
    },

    /**
     *
     * @param {Object} item
     */
    select(item) {
      if (item.alreadyshared) {
        // ignore already shared users
        return;
      }

      const tag = this.createTag(item);
      this.publicTags.push(tag);
      if (AccessManager.isRestricted(item.minimum_access)) {
        this.restrictedTags.push(tag);
      }

      this.$emit('pick-recipient', item);
      // Reset query to empty string
      this.query = '';
    },

    /**
     *
     * @param {Object} tag
     */
    remove(tag) {
      this.publicTags = this.publicTags.filter(t => t !== tag);
      if (AccessManager.isRestricted(tag.minimum_access)) {
        this.restrictedTags = this.restrictedTags.filter(t => t !== tag);
      }
      this.$emit('remove-recipient', tag);
    },

    /**
     *
     * @param recipient
     * @param type
     * @returns {*}
     */
    getRecipientDetails(recipient, type) {
      // Shared properties.
      switch (type) {
        case 'alreadyshared':
          return recipient.alreadyshared;
        case 'summary':
          return recipient.summary;
      }

      // User specific. equal to recipient.area === 'user' && type === 'fullname'
      if (recipient.area === 'user') {
        const displayField =
          recipient.user.card_display.display_fields.find(
            field => field.label === 'Full name'
          ) || recipient.user.card_display.display_fields[0];
        return displayField.value;
      } else {
        // Currently it's for workspace
        switch (type) {
          case 'src':
            return recipient.other.imageurl || '';
          case 'alt':
            return recipient.other.imagealt || recipient.other.fullname;
          case 'fullname':
            return recipient.other.fullname;
        }
      }
    },

    /**
     * @param {Object} recipient
     */
    createTag(recipient) {
      return {
        text: this.getRecipientDetails(recipient, 'fullname'),
        id:
          recipient.component +
          '/' +
          recipient.area +
          '/' +
          recipient.instanceid,
        instanceid: recipient.instanceid,
        area: recipient.area,
        component: recipient.component,
        minimum_access: recipient.minimum_access,
      };
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "alreadyshared",
      "enter_people_or_workspaces",
      "resharetorecipients",
      "resharetorecipientsworkspaces",
      "sharehelpinfo",
      "sharehelpinfoworkspaces",
      "sharetorecipients",
      "sharetorecipientsworkspaces",
      "sharetorecipientsoptional",
      "sharetorecipientsoptionalworkspaces"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageSharedRecipientsSelector {
  &__label.tui-formLabel {
    @include tui-font-heading-label();
    margin-right: 0;
    margin-bottom: var(--gap-2);
  }

  &__icon {
    display: inline-flex;
  }

  &__recipient {
    display: flex;
    > :first-child {
      margin-right: var(--gap-2);
    }

    &-summary {
      margin: 0;
      list-style-type: none;
      > :first-child {
        @include tui-font-heading-label();
      }
      > :last-child {
        @include tui-font-body-small();
      }
    }

    &-badge {
      align-self: flex-end;
      margin-left: auto;
      > :last-child {
        @include tui-font-body-small();
      }
    }
  }

  &__profileContainer {
    position: relative;
    &-badge {
      display: flex;
      align-items: flex-end;
      justify-content: flex-end;

      > :last-child {
        @include tui-font-body-small();

        padding-left: var(--gap-1);
      }
    }
  }
}
</style>

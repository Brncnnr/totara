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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_reaction
-->
<template>
  <div class="tui-sidePanelLike">
    <template v-if="!$apollo.loading">
      <ButtonIconWithLabel
        :button-aria-label="buttonAriaLabel"
        :label-aria-label="labelAriaLabel"
        :label-text="count"
        :disabled="disabled"
        :closeable-popover="false"
        @popover-open-changed="showPopover = $event"
        @open="showModal = true"
        @click="like"
      >
        <template v-slot:icon>
          <Loading v-if="submitting" />
          <Like v-else-if="!hasLiked" />
          <LikeActive v-else />
        </template>

        <template v-slot:hover-label-content>
          <LikeRecordsList
            :component="component"
            :area="area"
            :instance-id="instanceId"
          />
        </template>
      </ButtonIconWithLabel>

      <ModalPresenter :open="showModal" @request-close="showModal = false">
        <LikeRecordsModal
          :component="component"
          :area="area"
          :instance-id="instanceId"
        />
      </ModalPresenter>
    </template>
  </div>
</template>

<script>
import ButtonIconWithLabel from 'tui/components/buttons/LabelledButtonTrigger';
import LikeRecordsList from 'totara_reaction/components/popover_content/LikeRecordsList';
import LikeRecordsModal from 'totara_reaction/components/modal/LikeRecordsModal';
import Like from 'tui/components/icons/Like';
import LikeActive from 'tui/components/icons/LikeActive';
import Loading from 'tui/components/icons/Loading';
import ModalPresenter from 'tui/components/modal/ModalPresenter';

// GraphQL queries
import getLikes from 'totara_reaction/graphql/get_likes';
import liked from 'totara_reaction/graphql/liked';
import createLike from 'totara_reaction/graphql/create_like';
import removeLike from 'totara_reaction/graphql/remove_like';
import { notify } from 'tui/notifications';

export default {
  components: {
    ModalPresenter,
    ButtonIconWithLabel,
    LikeRecordsList,
    LikeRecordsModal,
    Like,
    LikeActive,
    Loading,
  },

  props: {
    component: {
      type: String,
      required: true,
    },

    area: {
      type: String,
      required: true,
    },

    instanceId: {
      type: [String, Number],
      required: true,
    },

    liked: {
      type: Boolean,
      default: null,
    },

    totalLikes: {
      type: Boolean,
      default: null,
    },

    iconSize: [String, Number],

    buttonAriaLabel: {
      type: String,
      required: true,
    },

    disabled: Boolean,
  },

  apollo: {
    count: {
      query: getLikes,
      variables() {
        return {
          component: this.component,
          area: this.area,
          instanceid: this.instanceId,
        };
      },

      /**
       *
       * @param {Number} count
       * @return {Number}
       */
      update({ count }) {
        return count;
      },

      skip() {
        // Do not load from server if the property's value is provided.
        return (
          'undefined' !== typeof this.totalLikes && null !== this.totalLikes
        );
      },
    },

    hasLiked: {
      query: liked,
      variables() {
        return {
          component: this.component,
          area: this.area,
          instanceid: this.instanceId,
        };
      },

      /**
       *
       * @param {Boolean} result
       * @return {Boolean}
       */
      update({ result }) {
        return result;
      },

      skip() {
        // Do not load from server when the property's value is provided.
        return 'undefined' !== typeof this.liked && null !== this.liked;
      },
    },
  },

  data() {
    return {
      hasLiked: this.liked,
      count: this.totalLikes,
      showPopover: false,
      showModal: false,
      submitting: false,
    };
  },

  computed: {
    labelAriaLabel() {
      if (this.count === 0) {
        return this.$str('nolikes', 'totara_reaction');
      } else {
        return this.$str('numberoflikes', 'totara_reaction', this.count);
      }
    },
  },

  watch: {
    /**
     *
     * @param {Boolean} value
     */
    liked(value) {
      if (value === this.hasLiked) {
        return;
      }

      this.hasLiked = value;
    },

    /**
     *
     * @param {Number} value
     */
    totalLikes(value) {
      if (value == this.count) {
        return;
      }

      this.count = value;
    },
  },

  methods: {
    async like() {
      if (this.hasLiked) {
        await this.removeLike();
      } else {
        await this.createLike();
      }
    },

    async createLike() {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      let variables = {
        component: this.component,
        area: this.area,
        instanceid: this.instanceId,
      };

      try {
        await this.$apollo.mutate({
          mutation: createLike,
          variables: variables,
          refetchAll: false,
          refetchQueries: [
            {
              query: getLikes,
              variables: variables,
            },
            {
              query: liked,
              variables: variables,
            },
          ],
        });

        this.$emit('created-like');
      } catch (e) {
        await notify({
          message: this.$str('error:create_like', 'totara_reaction'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },

    async removeLike() {
      if (this.submitting) {
        return;
      }

      this.submitting = true;
      let variables = {
        component: this.component,
        area: this.area,
        instanceid: this.instanceId,
      };

      try {
        await this.$apollo.mutate({
          mutation: removeLike,
          variables: variables,
          refetchAll: false,
          refetchQueries: [
            {
              query: getLikes,
              variables: variables,
            },
            {
              query: liked,
              variables: variables,
            },
          ],
        });

        this.$emit('removed-like');
      } catch (e) {
        await notify({
          message: this.$str('error:remove_like', 'totara_reaction'),
          type: 'error',
        });
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "totara_reaction": [
      "nolikes",
      "numberoflikes",
      "error:create_like",
      "error:remove_like"
    ]
  }
</lang-strings>

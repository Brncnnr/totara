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
  <div class="tui-simpleLike">
    <template v-if="!$apollo.loading">
      <LikeButtonIcon
        :aria-label="buttonAriaLabel"
        :liked="hasLiked"
        :submitting="submitting"
        :disabled="disabled"
        :text="buttonText"
        @click.prevent="like"
      />

      <Popover
        v-if="0 !== count"
        :triggers="['focus', 'hover']"
        class="tui-simpleLike__popover"
        @open-changed="showPopover = $event"
      >
        <template v-slot:trigger>
          <a href="#" @click.prevent="showModal = true">
            {{ $str('bracketcount', 'totara_reaction', count) }}
          </a>
        </template>

        <LikeRecordsList
          :skip-loading-records="!showPopover"
          :component="component"
          :area="area"
          :instance-id="instanceId"
        />
      </Popover>
    </template>

    <ModalPresenter :open="showModal" @request-close="showModal = false">
      <LikeRecordsModal
        :component="component"
        :instance-id="instanceId"
        :area="area"
      />
    </ModalPresenter>
  </div>
</template>

<script>
import LikeRecordsModal from 'totara_reaction/components/modal/LikeRecordsModal';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Popover from 'tui/components/popover/Popover';
import LikeButtonIcon from 'totara_reaction/components/buttons/LikeButtonIcon';
import LikeRecordsList from 'totara_reaction/components/popover_content/LikeRecordsList';
import { notify } from 'tui/notifications';

// GraphQL
import getLikes from 'totara_reaction/graphql/get_likes';
import hasLiked from 'totara_reaction/graphql/liked';
import createLike from 'totara_reaction/graphql/create_like';
import removeLike from 'totara_reaction/graphql/remove_like';

export default {
  components: {
    LikeRecordsModal,
    ModalPresenter,
    Popover,
    LikeButtonIcon,
    LikeRecordsList,
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
    disabled: Boolean,
    /**
     * Passing this prop to tell whether user has liked record or not. So that this component
     * will not try to fire a request to the server.
     */
    liked: {
      type: Boolean,
      default: null,
    },
    /**
     * Passing this prop with a valid value to prevent firing request to the server.
     * We cant use zero as default, because it might not trigger the query.
     */
    totalLikes: {
      type: [String, Number],
      default: null,
    },

    buttonAriaLabel: {
      type: String,
      required: true,
    },

    showText: Boolean,
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
       * @param {Number|String} count
       * @return {Number}
       */
      update({ count }) {
        return parseInt(count, 9);
      },

      skip() {
        // Only start fetching, when the data is not provided.
        return (
          'undefined' !== typeof this.totalLikes && null !== this.totalLikes
        );
      },
    },

    hasLiked: {
      query: hasLiked,
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
        // Only start fetching when the data is not provided.
        return 'undefined' !== typeof this.liked && null !== this.liked;
      },

      result({ data: { result } }) {
        this.$emit('update-like-status', result);
      },
    },
  },

  data() {
    return {
      hasLiked: this.liked,
      count: this.totalLikes,
      showModal: false,
      showPopover: false,
      submitting: false,
    };
  },

  computed: {
    buttonText() {
      if (!this.showText) {
        return '';
      }

      return this.$str('like', 'totara_reaction');
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
              query: hasLiked,
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
              query: hasLiked,
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
      "bracketcount",
      "error:create_like",
      "error:remove_like",
      "like"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-simpleLike {
  display: inline-flex;
}
</style>

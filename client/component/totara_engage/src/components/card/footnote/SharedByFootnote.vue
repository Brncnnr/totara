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
  <div v-show="sharer" class="tui-cardSharedByFootnote">
    <div class="tui-cardSharedByFootnote__sharer">
      <span class="tui-cardSharedByFootnote__text">
        {{ $str('from', 'core') }}
      </span>
      <a class="tui-cardSharedByFootnote__url" :href="sharer.url">
        {{ sharer.fullname }}
      </a>
    </div>
    <ButtonIcon
      v-if="showButton"
      class="tui-cardSharedByFootnote__deleteButton"
      :aria-label="
        $str(
          area === 'user' ? 'removefromsharedwithyou' : 'removefromlibary',
          'totara_engage'
        )
      "
      :styleclass="{
        small: true,
        transparentNoPadding: true,
        alert: true,
      }"
      :disabled="loading"
      @click.stop.prevent="unshare"
    >
      <Loading v-if="loading" />
      <Remove v-else :size="300" state="alert" />
    </ButtonIcon>
  </div>
</template>

<script>
import Remove from 'tui/components/icons/Remove';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Loading from 'tui/components/icons/Loading';
import { notify } from 'tui/notifications';

//graphQL
import unShare from 'totara_engage/graphql/unshare';

export default {
  components: {
    ButtonIcon,
    Loading,
    Remove,
  },

  props: {
    instanceId: {
      type: Number,
      required: true,
    },
    component: {
      type: String,
      required: true,
    },
    sharer: {
      type: Object,
      default: () => ({}),
      validator: sharer => ['fullname', 'url'].every(prop => prop in sharer),
    },
    area: {
      type: String,
      required: true,
    },
    recipientId: {
      type: Number,
      required: true,
    },
    showButton: {
      type: Boolean,
      required: true,
    },
    name: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      loading: false,
    };
  },

  methods: {
    async unshare() {
      if (!this.loading) {
        this.loading = true;
      }

      let refetchQueries = ['totara_engage_contribution_cards'];
      let message = this.$str(
        'removefromsharedlibrary',
        'totara_engage',
        this.name
      );

      if (this.area !== 'user') {
        refetchQueries = [
          'container_workspace_contribution_cards',
          'container_workspace_shared_cards',
        ];

        message = this.$str(
          'removefromyourworkspace',
          'totara_engage',
          this.name
        );
      }

      try {
        let {
          data: { result },
        } = await this.$apollo.mutate({
          mutation: unShare,
          refetchQueries,
          variables: {
            recipient_id: this.recipientId,
            component: this.component,
            item_id: this.instanceId,
          },
        });

        if (result) {
          await notify({
            message,
            type: 'success',
          });
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "from"
  ],
  "totara_engage": [
    "removefromlibary",
    "removefromsharedlibrary",
    "removefromsharedwithyou",
    "removefromyourworkspace"
  ]
}
</lang-strings>

<style lang="scss">
.tui-cardSharedByFootnote {
  display: flex;
  flex-direction: row;
  justify-content: space-between;
}
</style>

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
  @module container_workspace
-->
<template>
  <div class="tui-emptySpacesHeader">
    <ModalPresenter :open="openModal" @request-close="openModal = false">
      <WorkspaceModal @create-workspace="navigateToSpace" />
    </ModalPresenter>

    <h1 class="tui-emptySpacesHeader__title">
      {{ $str('no_spaces', 'container_workspace') }}
    </h1>

    <div class="tui-emptySpacesHeader__actionBox">
      <ActionLink
        class="tui-emptySpacesHeader__actionLink"
        :href="$url('/container/type/workspace/spaces.php')"
        :text="$str('find_spaces', 'container_workspace')"
        :styleclass="{ primary: true }"
      />

      <p v-if="canCreate">
        <span>{{ $str('or', 'container_workspace') }}</span>
        <a href="#" @click.prevent="openModal = true">
          {{ $str('create_space', 'container_workspace') }}
        </a>
      </p>
    </div>
  </div>
</template>

<script>
import ActionLink from 'tui/components/links/ActionLink';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import WorkspaceModal from 'container_workspace/components/modal/WorkspaceModal';

export default {
  components: {
    ActionLink,
    ModalPresenter,
    WorkspaceModal,
  },

  props: {
    canCreate: Boolean,
  },

  data() {
    return {
      openModal: false,
    };
  },

  methods: {
    /**
     *
     * @param {Number} id
     */
    navigateToSpace({ id }) {
      this.openModal = false;

      // Navigate to the page.
      document.location.href = this.$url(
        '/container/type/workspace/workspace.php',
        { id }
      );
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "no_spaces",
      "find_spaces",
      "or",
      "create_space"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-emptySpacesHeader {
  display: flex;
  flex-direction: column;
  align-items: center;

  &__title {
    @include tui-font-heading-medium();
    @include tui-wordbreak--hard();
    margin: 0;
    margin-bottom: var(--gap-12);
    text-align: center;
  }

  &__actionBox {
    display: flex;
    flex-direction: column;
    align-items: center;

    p {
      @include tui-font-body();
      margin: 0;
      margin-bottom: var(--gap-8);

      a {
        @include tui-font-link();
      }
    }
  }

  &__actionLink {
    margin-bottom: var(--gap-4);
  }
}
</style>

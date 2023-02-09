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
  @module container_workspace
-->

<template>
  <div class="tui-workspaceContributeCard">
    <Card class="tui-workspaceContributeCard__card">
      <Contribute
        :adder="{
          text: $str('select_existing_resource', 'container_workspace'),
          destination: $str('to_share_with_workspace', 'container_workspace'),
        }"
        :show-text="false"
        :show-icon="true"
        :styleclass="{ circle: true, primary: true }"
        :size="500"
        :container="container"
        @done="$emit('contribute', $event)"
        @open-adder="showAdder = true"
      />
    </Card>

    <Share
      grid-direction="horizontal"
      :workspace-id="instanceId"
      :show-adder="showAdder"
      :units="12"
      @close="showAdder = false"
    />
  </div>
</template>

<script>
import Card from 'tui/components/card/Card';
import Contribute from 'totara_engage/components/contribution/Contribute';
import { AccessManager } from 'totara_engage/index';
import Share from 'container_workspace/components/contribution/Share';
import { isPrivate, isHidden } from 'container_workspace/index';
import { config } from 'tui/config';

// GraphQL
import getWorkspace from 'container_workspace/graphql/get_workspace';

export default {
  components: {
    Card,
    Contribute,
    Share,
  },

  props: {
    instanceId: {
      type: [Number, String],
      required: true,
    },
  },

  data() {
    return {
      workspace: {},
      showAdder: false,
    };
  },

  apollo: {
    workspace: {
      query: getWorkspace,
      variables() {
        return {
          id: this.instanceId,
          theme: config.theme.name,
        };
      },
    },
  },

  computed: {
    container() {
      return {
        instanceId: this.instanceId,
        component: 'container_workspace',
        access: this.accessStatus,
        autoShareRecipient: true,
        area: 'library',
        name: this.workspace.name,
        showModal: true,
      };
    },

    accessStatus() {
      if (this.workspace.access) {
        if (
          isPrivate(this.workspace.access) ||
          isHidden(this.workspace.access)
        ) {
          return AccessManager.RESTRICTED;
        }
      }

      // Otherwise default to public.
      return AccessManager.PUBLIC;
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "select_existing_resource",
      "to_share_with_workspace"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceContributeCard {
  width: 100%;
  height: calc(var(---engage-card-height) + 11px);

  .tui-card {
    // Overiding cards border
    border: 2px dashed var(--color-primary);
  }

  &__card {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
  }

  &__icon {
    color: var(--color-primary);
    cursor: pointer;
  }
}
</style>

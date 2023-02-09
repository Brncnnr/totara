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

  @author Alvin Smith <alvin.smith@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-shareSetting">
    <ButtonIconWithLabel
      :label-text="sharedByCountDisplay"
      :label-aria-label="sharedByCountAriaLabel"
      :button-aria-label="buttonAriaLabel"
      :closeable-popover="false"
      class="tui-shareSetting__buttonLabel"
      @open="showReciptsModal"
      @click="showReciptsModal"
    >
      <template v-slot:icon>
        <Share />
      </template>
      <template v-slot:hover-label-content>
        <div class="tui-shareSetting__buttonLabel-hoverContent">
          {{ sharedByCountAriaLabel }}
        </div>
      </template>
    </ButtonIconWithLabel>
    <ModalPresenter
      v-if="!owned"
      :open="reciptsModalOpen"
      @request-close="reciptsModalRequestClose"
    >
      <Modal size="normal" :dismissable="{ backdropClick: false }">
        <ModalContent
          :close-button="true"
          :title="$str('reshare', 'totara_engage')"
          @dismiss="reciptsModalRequestClose"
        >
          <div class="tui-shareSetting__recipient">
            <RecipientsSelector
              :access="accessValue"
              :owned="owned"
              :item-id="instanceId"
              :component="component"
              @pick-recipient="addNewShare"
              @remove-recipient="removeNewShare"
            />
            <SharedBoard :receipts="sharedTo" />
            <DoneCancelGroup
              @done="submit"
              @cancel="reciptsModalRequestClose"
            />
          </div>
        </ModalContent>
      </Modal>
    </ModalPresenter>
  </div>
</template>

<script>
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Share from 'tui/components/icons/Share';
import { config } from 'tui/config';

import { AccessManager } from 'totara_engage/index';
import ButtonIconWithLabel from 'tui/components/buttons/LabelledButtonTrigger';
import DoneCancelGroup from 'totara_engage/components/buttons/DoneCancelGroup';
import RecipientsSelector from 'totara_engage/components/form/access/RecipientsSelector';
import SharedBoard from 'totara_engage/components/form/SharedBoard';

// GraphQL queries
import shareRecipients from 'totara_engage/graphql/share_recipients';
import shareWith from 'totara_engage/graphql/share';

export default {
  components: {
    ButtonIconWithLabel,
    DoneCancelGroup,
    Modal,
    ModalContent,
    ModalPresenter,
    RecipientsSelector,
    Share,
    SharedBoard,
  },

  apollo: {
    sharedTo: {
      query: shareRecipients,
      skip: true,
      variables() {
        return {
          itemid: this.instanceId,
          component: this.component,
          theme: config.theme.name,
        };
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
    },
  },

  props: {
    accessValue: {
      type: String,
      required: true,
      validator(prop) {
        return AccessManager.isValid(prop);
      },
    },
    component: {
      type: String,
      required: true,
    },
    instanceId: {
      type: [String, Number],
      required: true,
    },
    owned: {
      type: Boolean,
      required: true,
    },
    buttonAriaLabel: {
      type: String,
      required: true,
    },
    sharedByCount: {
      type: Number,
      required: true,
    },
  },

  data() {
    return {
      access: this.accessValue,
      newShares: [],
      reciptsModalOpen: false,
      resharerModalOpen: false,
      sharedByCountLocal: 0,
      sharedByLoaded: false,
      sharedTo: { people: [], workspaces: [] },
    };
  },

  computed: {
    sharedByCountDisplay() {
      return this.sharedByCountLocal
        ? this.sharedByCountLocal
        : this.sharedByCount;
    },

    sharedByCountAriaLabel() {
      const count = this.sharedByCountDisplay;
      if (count === 0) {
        return this.$str('noshares', 'totara_engage');
      } else {
        return this.$str('numberofshares', 'totara_engage', count);
      }
    },
  },

  watch: {
    sharedByCountDisplay: function() {
      if (this.sharedByLoaded) {
        this.$apollo.queries.sharedBy.refetch();
      }
    },
  },

  methods: {
    addNewShare(instance) {
      const share = {
        instanceid: instance.instanceid,
        component: instance.component,
        area: instance.area,
      };
      this.newShares.push(share);
    },

    reciptsModalRequestClose() {
      this.reciptsModalOpen = false;
    },

    removeNewShare(instance) {
      this.newShares = this.newShares.filter(
        newShare => newShare.instanceid !== instance.instanceid
      );
    },

    showReciptsModal() {
      if (this.owned) {
        this.$emit('access-modal');
      } else {
        this.$apollo.queries.sharedTo.start();
        this.reciptsModalOpen = true;
      }
    },

    submit: async function() {
      const data = await this.$apollo.mutate({
        refetchAll: false,
        mutation: shareWith,
        variables: {
          itemid: this.instanceId,
          component: this.component,
          recipients: this.newShares,
        },
      });

      this.sharedByCountLocal = data.data.shares.sharedbycount;
      this.$apollo.queries.sharedTo.refetch();
      this.reciptsModalRequestClose();
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "noshares",
      "reshare",
      "resharedbypeople",
      "numberofshares",
      "share",
      "tipnumberofresharer"
    ]
  }
</lang-strings>

<style lang="scss">
:root {
  --shareSetting-min-height: 250px;
}

.tui-shareSetting {
  &__buttonLabel {
    &-hoverContent {
      text-align: center;
      hyphens: none;
    }
  }
  &__recipient {
    display: flex;
    flex-direction: column;
    min-height: var(--shareSetting-min-height);
  }
}
</style>

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
  <div class="tui-engageContribute">
    <ButtonIcon
      :aria-label="ariaLabel"
      :text="text"
      :styleclass="styleclass"
      @click="showModal"
    >
      <slot v-if="showIcon" name="icon">
        <Add :size="size" />
      </slot>
    </ButtonIcon>
    <ModalPresenter :open="modalOpen" @request-close="modalRequestClose">
      <slot name="modal">
        <ContributeModal
          :container="container"
          :adder="adder"
          @done="$emit('done', $event)"
          @request-close="modalRequestClose"
          @adder-open="adderOpen"
        />
      </slot>
    </ModalPresenter>
  </div>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import ContributeModal from 'totara_engage/components/modal/ContributeModal';
import Add from 'tui/components/icons/Add';

// Mixins
import ContainerMixin from 'totara_engage/mixins/container_mixin';

export default {
  components: {
    ButtonIcon,
    ModalPresenter,
    ContributeModal,
    Add,
  },

  mixins: [ContainerMixin],

  props: {
    showText: {
      type: Boolean,
      default: true,
    },
    showIcon: Boolean,
    styleclass: Object,
    size: Number,

    ariaLabel: {
      type: String,
      default() {
        return this.$str('contribute', 'totara_engage');
      },
    },
    adder: Object,
  },

  data() {
    return {
      text: this.showText ? this.$str('contribute', 'totara_engage') : '',
      modalOpen: false,
    };
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },
    modalRequestClose() {
      this.modalOpen = false;
    },

    adderOpen() {
      this.modalRequestClose();
      this.$emit('open-adder');
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "contribute"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageContribute {
  display: flex;
  align-items: center;
}
</style>

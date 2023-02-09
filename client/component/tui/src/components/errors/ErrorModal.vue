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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<template>
  <Modal :size="size" :aria-labelledby="$id('title')" error-modal>
    <ModalContent
      :close-button="true"
      :title="$tryStr('system_error', 'totara_core') || 'System Error'"
      :title-id="$id('title')"
      @dismiss="$emit('cancel')"
    >
      <template>
        <p v-if="error && error.type == 'server'">
          {{ $str('error:ajax', 'totara_core') }}
        </p>
        <p v-else>
          {{ $str('error:unexpected', 'totara_core') }}
        </p>
        <div>
          <ButtonIcon
            class="tui-errorModal__detailsToggle"
            :styleclass="{ transparentNoPadding: true }"
            :text="
              $tryStr('technical_details', 'totara_core') || 'Technical details'
            "
            :aria-expanded="showDetails.toString()"
            :aria-controls="$id('detailsRegion')"
            :aria-label="
              $tryStr('technical_details', 'totara_core') || 'Technical details'
            "
            @click="toggleDetails"
          >
            <CollapseIcon v-if="showDetails" size="100" />
            <ExpandIcon v-else size="100" />
          </ButtonIcon>
        </div>
        <div
          v-if="showDetails"
          :id="$id('detailsRegion')"
          class="tui-errorModal__details"
        >
          <p
            v-if="error && error.title"
            class="tui-errorModal__label"
            v-text="error.title"
          />
          <p
            v-if="error && error.context"
            class="tui-errorModal__context"
            v-text="error.context"
          />
          <p v-if="error && error.debugMessage" v-text="error.debugMessage" />
          <p
            v-if="error && error.extraInfo"
            class="tui-errorModal__extraInfo"
            v-text="error.extraInfo"
          />
          <template v-if="error.stack">
            <div>
              <ButtonIcon
                class="tui-errorModal__detailsToggle"
                :styleclass="{ transparentNoPadding: true }"
                text="Stack trace"
                :aria-expanded="showStack.toString()"
                :aria-controls="$id('stackRegion')"
                aria-label="Stack trace"
                @click="toggleStack"
              >
                <CollapseIcon v-if="showStack" size="100" />
                <ExpandIcon v-else size="100" />
              </ButtonIcon>
            </div>
            <pre
              v-if="showStack"
              :id="$id('stackRegion')"
              class="tui-errorModal__stackPre"
              v-text="error.stack"
            />
          </template>
        </div>
      </template>

      <template v-slot:footer-content>
        <div class="tui-errorModal__buttons">
          <div class="tui-errorModal__navButtons">
            <template>
              <ButtonIcon
                :styleclass="{ square: true }"
                :aria-label="$tryStr('previous', 'core') || 'Previous'"
                :disabled="atStart"
                @click="previous"
              >
                <EntryPreviousIcon />
              </ButtonIcon>
              <div>{{ errorIndex + 1 }} of {{ errors.length }}</div>
              <ButtonIcon
                :styleclass="{ square: true }"
                :aria-label="$tryStr('next', 'core') || 'Next'"
                :disabled="atEnd"
                @click="next"
              >
                <EntryNextIcon />
              </ButtonIcon>
            </template>
            <Button
              :text="$tryStr('copy_all', 'totara_core') || 'Copy all'"
              @click="copyAll"
            />
          </div>
          <Button
            :styleclass="{ primary: true }"
            :text="$tryStr('close', 'totara_core') || 'Close'"
            @click="$emit('request-close')"
          />
        </div>
      </template>
    </ModalContent>
  </Modal>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import EntryNextIcon from 'tui/components/icons/EntryNext';
import EntryPreviousIcon from 'tui/components/icons/EntryPrevious';
import ExpandIcon from 'tui/components/icons/Expand';
import CollapseIcon from 'tui/components/icons/Collapse';
import { copyText } from '../../js/internal/clipboard';

export default {
  components: {
    Button,
    ButtonIcon,
    Modal,
    ModalContent,
    EntryNextIcon,
    EntryPreviousIcon,
    ExpandIcon,
    CollapseIcon,
  },

  props: {
    closeButton: {
      type: Boolean,
      default: false,
    },
    size: {
      type: String,
      default: 'normal',
    },
    errors: Array,
  },

  data() {
    return {
      showDetails: false,
      showStack: false,
      errorIndex: 0,
    };
  },

  computed: {
    error() {
      return this.errors[this.errorIndex];
    },

    atStart() {
      return this.errorIndex == 0;
    },

    atEnd() {
      return this.errorIndex == this.errors.length - 1;
    },
  },

  methods: {
    toggleDetails() {
      this.showDetails = !this.showDetails;
    },

    toggleStack() {
      this.showStack = !this.showStack;
    },

    next() {
      if (this.atEnd) {
        this.errorIndex = 0;
      } else {
        this.errorIndex++;
      }
    },

    previous() {
      if (this.atStart) {
        this.errorIndex = this.errors.length - 1;
      } else {
        this.errorIndex--;
      }
    },

    copyAll() {
      const text = this.errors
        .map((error, i) => {
          const lines = [
            `Error ${i + 1} of ${this.errors.length}`,
            '',
            error.title,
            error.context,
            '',
            error.url ? `URL: ${error.url}` : '',
            error.extraInfo,
            error.debugMessage,
            '',
            error.stack,
          ];
          return lines
            .filter(x => x != null)
            .join('\n')
            .replace(/\n\n+/, '\n\n');
        })
        .join('\n\n' + '==============================' + '\n\n');
      copyText(text);
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "next",
    "previous"
  ],
  "totara_core": [
    "close",
    "details",
    "copy_all",
    "system_error",
    "error:ajax",
    "error:unexpected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-errorModal {
  &__detailsToggle {
    display: block;
    margin-top: var(--gap-4);
    text-decoration: none;

    &:hover,
    &:focus {
      text-decoration: none;
    }
  }

  &__buttons {
    display: flex;
    justify-content: space-between;
    width: 100%;
  }

  &__navButtons {
    display: flex;
    align-items: center;
    @include tui-stack-horizontal(var(--gap-4));
  }

  &__details {
    margin-top: var(--gap-3);
  }

  &__label {
    @include tui-font-heading-x-small();
  }

  &__context {
    color: var(--color-text-hint);
  }

  &__extraInfo {
    white-space: pre-wrap;
  }

  &__stackPre {
    margin: var(--gap-2) 0 0 0;
    padding: var(--gap-4);
    color: var(--color-neutral-7);
    word-break: break-word;
    background-color: var(--color-neutral-3);
    border-radius: 4px;
  }
}
</style>

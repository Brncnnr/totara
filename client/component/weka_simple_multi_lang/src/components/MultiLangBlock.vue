<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module weka_simple_multi_lang
-->
<!-- A content block component to display the texts within a block -->
<template>
  <div class="tui-wekaMultiLangBlock" contenteditable="false">
    <!-- We need the contenteditable attribute to be set to false here, so that prosemirror does not put a cursor within it. -->
    <ModalPresenter :open="editModal" @request-close="editModal = false">
      <EditMultiLangModal
        :lang="language"
        :content="langContentJson"
        :editor-compact="editorCompact"
        :placeholder-resolver-class="placeholderResolverClass"
        @submit="handleUpdateLangContent"
      />
    </ModalPresenter>

    <div class="tui-wekaMultiLangBlock__container">
      <div class="tui-wekaMultiLangBlock__wrapper">
        <div class="tui-wekaMultiLangBlock__languageWrapper">
          <div class="tui-wekaMultiLangBlock__language">
            {{
              $str(
                'language_label',
                'weka_simple_multi_lang',
                language || $str('unspecified', 'weka_simple_multi_lang')
              )
            }}
          </div>
          <div class="tui-wekaMultiLangBlock__actions">
            <ButtonIcon
              v-if="!editorDisabled"
              :aria-label="editLanguageAriaLabel"
              :styleclass="{
                transparent: true,
                transparentNoPadding: true,
              }"
              @click="editModal = true"
            >
              <Edit :size="100" />
            </ButtonIcon>
          </div>
        </div>
        <!-- Multi lang block. A content placeholder that will hold collection of text blocks -->
        <div ref="content" class="tui-wekaMultiLangBlock__texts" />
      </div>
      <ButtonIcon
        v-if="removable"
        class="tui-wekaMultiLangBlock__remove"
        :aria-label="removeLanguageAriaLabel"
        :styleclass="{
          transparent: true,
          transparentNoPadding: true,
        }"
        @click="handleRemoving"
      >
        <Delete :size="100" />
      </ButtonIcon>
    </div>
  </div>
</template>

<script>
import BaseNode from 'editor_weka/components/nodes/BaseNode';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Edit from 'tui/components/icons/Edit';
import Delete from 'tui/components/icons/Delete';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import EditMultiLangModal from 'weka_simple_multi_lang/components/modal/EditSimpleMultiLangModal';

export default {
  components: {
    ButtonIcon,
    Edit,
    Delete,
    ModalPresenter,
    EditMultiLangModal,
  },
  extends: BaseNode,

  data() {
    return {
      editModal: false,
    };
  },

  computed: {
    language() {
      return this.attrs.lang;
    },

    /**
     * @return {Array}
     */
    langContentJson() {
      const jsonNodes = [];
      this.node.content.forEach(node => jsonNodes.push(node.toJSON()));

      return jsonNodes;
    },

    removable() {
      if (this.editorDisabled) {
        // Editor is disabled, so we cannot remove the node.
        return false;
      }

      return this.attrs.siblings_count > 2;
    },

    editLanguageAriaLabel() {
      return this.$str(
        'edit_language_x',
        'weka_simple_multi_lang',
        this.language || this.$str('unspecified', 'weka_simple_multi_lang')
      );
    },

    removeLanguageAriaLabel() {
      return this.$str(
        'remove_language_x',
        'weka_simple_multi_lang',
        this.language || this.$str('unspecified', 'weka_simple_multi_lang')
      );
    },

    editorCompact() {
      return this.context.getCompact();
    },

    placeholderResolverClass() {
      return this.context.getPlaceholderResolverClassName();
    },
  },

  methods: {
    /**
     *
     * @param {String} lang
     * @param {Object[]} content
     */
    handleUpdateLangContent({ lang, content }) {
      const parameters = {
        attrs: {
          lang: lang,
          siblings_count: this.attrs.siblings_count,
        },
        content: content,
      };

      this.editModal = false;
      this.context.updateSelf(parameters, this.getRange);
    },

    /**
     * Executes self removing.
     */
    handleRemoving() {
      this.context.removeSelf(this.getRange);
    },
  },
};
</script>

<lang-strings>
{
  "weka_simple_multi_lang": [
    "edit_language_x",
    "remove_language_x",
    "unspecified",
    "language_label"
  ]
}
</lang-strings>

<style lang="scss">
.tui-wekaMultiLangBlock {
  white-space: normal;

  &__container {
    display: flex;
    align-items: baseline;
  }

  &__wrapper {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: var(--gap-2);
    background: var(--color-neutral-3);
    border: 1px solid var(--color-neutral-5);
  }

  &__languageWrapper {
    display: flex;
  }

  &__language {
    display: flex;
    flex: 1;
    padding-right: var(--gap-2);
    font-size: var(--font-size-12);
  }

  &__texts {
    white-space: break-spaces;
  }

  &__remove {
    margin-left: var(--gap-2);
  }
}
</style>

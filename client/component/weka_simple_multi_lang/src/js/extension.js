/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module weka_simple_multi_lang
 */
import BaseExtension from 'editor_weka/extensions/Base';
import MultiLangBlock from 'weka_simple_multi_lang/components/MultiLangBlock';
import MultiLangBlockCollection from 'weka_simple_multi_lang/components/MultiLangBlockCollection';
import { ToolbarItem } from 'editor_weka/toolbar';
import { langString } from 'tui/i18n';
import { pick } from 'tui/util';
import MultiLangIcon from 'tui/components/icons/MultiLang';
import simpleMultiLang from './plugin';
import { TextSelection } from 'ext_prosemirror/state';
import { getJsonAttrs } from 'editor_weka/extensions/util';

// eslint-disable-next-line no-unused-vars
import { EditorView } from 'ext_prosemirror/view';

class WekaSimpleMultiLangExtension extends BaseExtension {
  nodes() {
    return {
      weka_simple_multi_lang_lang_block: {
        schema: {
          atom: true,
          selectable: false,
          isolating: true,
          group: 'weka_simple_multi_lang_lang_blocks',
          draggable: false,
          content: '(paragraph|heading)*',
          allowGapCursor: false,
          attrs: {
            lang: { default: undefined },
            siblings_count: { default: 1 },
          },
          toDOM(node) {
            return [
              'div',
              {
                class: 'tui-wekaMultiLangBlock',
                'data-attrs': JSON.stringify({
                  lang: node.attrs.lang,
                  siblings_count: node.attrs.siblings_count,
                }),
              },
              0,
            ];
          },
          parseDOM: [
            {
              tag: 'div.tui-wekaMultiLangBlock',
              getAttrs: getJsonAttrs,
            },
          ],
        },
        component: MultiLangBlock,
        componentContext: {
          removeSelf: this._removeLangBlock.bind(this),
          updateSelf: this._updateLangBlock.bind(this),
          getCompact: () => {
            return this.options.compact || false;
          },
          getPlaceholderResolverClassName: () => {
            return this.options.placeholder_resolver_class_name || null;
          },
        },
      },
      /**
       * A collection block
       */
      weka_simple_multi_lang_lang_blocks: {
        schema: {
          atom: true,
          draggable: false,
          selectable: true,
          isolating: true,
          group: 'block',
          content: 'weka_simple_multi_lang_lang_block+',
          allowGapCursor: false,
          toDOM() {
            return ['div', { class: 'tui-wekaMultiLangBlockCollection' }, 0];
          },

          parseDOM: [{ tag: 'div.tui-wekaMultiLangBlockCollection' }],
        },
        component: MultiLangBlockCollection,
        componentContext: {
          insertNewLangBlock: this._insertNewLangBlock.bind(this),
        },
      },
    };
  }

  toolbarItems() {
    if (!this.options.is_active) {
      // Do not show the toolbar item, if the multi lang filter is
      // not enabled for the system.
      return [];
    }

    return [
      new ToolbarItem({
        group: 'embeds',
        label: langString('multi_lang', 'weka_simple_multi_lang'),
        iconComponent: MultiLangIcon,
        execute: this._createCollectionBlock.bind(this),
      }),
    ];
  }

  plugins() {
    return [simpleMultiLang({ handleKeyDown: this._handleKeyDown.bind(this) })];
  }

  keymap(bind) {
    if (!this.options.is_active) {
      // Remove the key binding, if the multi lang filter is not enabled for the system.
      // This is to prevent the ability of creating a new collection block, via interface.
      // However, it does not means that we are going to disable edit functionality.
      // The reason that we are doing this is because of the node might be invalid if we disable it.
      return;
    }

    bind('Ctrl-m', this._createCollectionBlock.bind(this));
  }

  loadSerializedVisitor() {
    return {
      weka_simple_multi_lang_lang_blocks: node => {
        node.content.forEach(child => {
          if (!child.attrs) child.attrs = {};
          child.attrs.siblings_count = node.content.length;
        });
      },
    };
  }

  saveSerializedVisitor() {
    return {
      weka_simple_multi_lang_lang_blocks: node => {
        node.content.forEach(child => {
          delete child.attrs.siblings_count;
        });
      },
    };
  }

  /**
   * @private
   */
  _createCollectionBlock() {
    const { from, to } = pick(this.editor.state.selection, ['from', 'to']);
    this.editor.execute((state, dispatch) => {
      const { tr: transaction } = state;
      transaction.replaceWith(from, to, [
        state.schema.node('weka_simple_multi_lang_lang_blocks', {}, [
          state.schema.node('weka_simple_multi_lang_lang_block', {
            siblings_count: 2,
          }),
          state.schema.node('weka_simple_multi_lang_lang_block', {
            siblings_count: 2,
          }),
        ]),
      ]);

      dispatch(transaction);
    });
  }

  /**
   *
   * @param {Function} getRange
   * @private
   */
  _removeLangBlock(getRange) {
    const { from } = getRange();

    // We need to find out the parents of this current node, which is the collection node
    // to check if the collection node only have two of this nodes or not.
    const resolvedPosition = this.doc.resolve(from);
    const parentResolvedPosition = this.doc.resolve(resolvedPosition.end());

    let collectionNode = parentResolvedPosition.node();

    if (collectionNode.type.name !== 'weka_simple_multi_lang_lang_blocks') {
      console.error(
        '[Weka] cannot resolve the position of parent collection node'
      );
      return;
    }

    if (collectionNode.content.content.length <= 2) {
      console.warn(
        '[Weka] cannot remove another single lang block due to the minimum requirement'
      );
      return;
    }

    this.editor.execute((state, dispatch) => {
      const { schema, tr: transaction } = state;

      // Convert the collection node to JSON data, so we can easily work with it.
      collectionNode = collectionNode.toJSON();

      // Remove one item by the index of it.
      collectionNode.content = collectionNode.content.filter((node, index) => {
        return index !== resolvedPosition.index();
      });

      const currentTotal = collectionNode.content.length;
      collectionNode.content = collectionNode.content.map(node => {
        node = Object.assign({}, node);
        node.attrs = Object.assign({}, node.attrs, {
          siblings_count: currentTotal,
        });

        return node;
      });

      transaction.replaceWith(
        parentResolvedPosition.before(),
        parentResolvedPosition.after(),
        schema.nodeFromJSON(collectionNode)
      );

      dispatch(transaction);
      this.editor.view.focus();
    });
  }

  /**
   * @param {Object} attrs
   * @param {Array} content
   * @param {Function} getRange
   * @private
   */
  _updateLangBlock({ attrs, content }, getRange) {
    const { from, to } = getRange();
    this.editor.execute((state, dispatch) => {
      const { tr: transaction } = state;

      const contentNodes = content.map(jsonNode => {
        return state.schema.nodeFromJSON(jsonNode);
      });

      dispatch(
        transaction.replaceWith(
          from,
          to,
          state.schema.node(
            'weka_simple_multi_lang_lang_block',
            attrs,
            contentNodes
          )
        )
      );
    });

    this.editor.view.focus();
  }

  /**
   *
   * @param {Function} getRange
   * @private
   */
  _insertNewLangBlock(getRange) {
    const { to } = getRange();

    // Resolve the collection block, which we need to step inside by 1.
    const resolvedPosition = this.editor.state.doc.resolve(to - 1);
    let collectionNode = resolvedPosition.node();

    if (collectionNode.type.name !== 'weka_simple_multi_lang_lang_blocks') {
      console.warn(
        `Unable to resolve node 'weka_simple_multi_lang_lang_blocks' from the position ${to}`
      );

      return;
    }

    // Add new item to content of collection node, but also updating the siblings counter
    // of the child within it.
    collectionNode = collectionNode.toJSON();
    const currentTotal = collectionNode.content.length;

    collectionNode.content = collectionNode.content.map(node => {
      node = Object.assign({}, node);
      node.attrs = Object.assign({}, node.attrs, {
        siblings_count: currentTotal + 1,
      });

      return node;
    });

    this.editor.execute((state, dispatch) => {
      const { tr: transaction, schema } = state;
      transaction.replaceWith(
        resolvedPosition.before(),
        resolvedPosition.after(),
        schema.nodeFromJSON(collectionNode)
      );

      // Insert a new block
      transaction.insert(
        resolvedPosition.end(),
        schema.node('weka_simple_multi_lang_lang_block', {
          siblings_count: currentTotal + 1,
        })
      );

      dispatch(transaction);
    });

    this.editor.view.focus();
  }

  /**
   *
   * @param {EditorView} view
   * @param {KeyboardEvent} event
   *
   * @return {Boolean}
   * @private
   */
  _handleKeyDown(view, event) {
    switch (event.key) {
      case 'Enter':
        return this._createNewLine(view);

      case 'Backspace':
      case 'Delete':
        return this._handleRemoveCollectionBlocKFromBackspace(view);

      default:
        // eslint-disable-next-line no-case-declarations
        const resolvedPos = view.state.tr.selection.$from,
          currentNode = resolvedPos.node();

        if (
          currentNode &&
          (currentNode.type.name === 'weka_simple_multi_lang_lang_blocks' ||
            currentNode.type.name === 'weka_simple_multi_lang_lang_block')
        ) {
          // Disable every other keys within the collection block.
          return true;
        }

        // Otherwise let prose mirror handle it.
        return false;
    }
  }

  /**
   *
   * @param {EditorView} view
   * @return {Boolean}
   * @private
   */
  _createNewLine(view) {
    const from = view.state.tr.selection.$from,
      node = from.node();

    const getInsertionFrom = () => {
      switch (node.type.name) {
        case 'weka_simple_multi_lang_lang_blocks':
          return from.after();

        case 'weka_simple_multi_lang_lang_block':
          // Get the parent position
          // eslint-disable-next-line no-case-declarations
          const resolvedPosition = this.doc.resolve(from.before());
          return resolvedPosition.after();

        case 'paragraph':
        case 'heading':
          // We need to check if the the current paragraph node is within the lang_block or not.i
          // eslint-disable-next-line no-case-declarations
          const parentNode = from.node(from.depth - 1);
          if (parentNode.type.name === 'weka_simple_multi_lang_lang_block') {
            const blockPosition = view.state.doc.resolve(from.before()),
              collectionPosition = view.state.doc.resolve(
                blockPosition.before()
              );

            return collectionPosition.after();
          }

          return null;

        default:
          return null;
      }
    };

    let insertPoint = getInsertionFrom();
    if (insertPoint !== null) {
      this.editor.execute((state, dispatch) => {
        const { tr: transaction } = state;

        // Insert the new paragraph node.
        transaction.insert(
          insertPoint,
          state.schema.nodes.paragraph.createAndFill()
        );

        // Move the cursor to this newly created paragraph node.
        transaction.setSelection(
          new TextSelection(transaction.doc.resolve(insertPoint + 1))
        );

        dispatch(transaction);
      });

      return true;
    }

    return false;
  }

  /**
   *
   * @param {EditorView} view
   * @return {Boolean}
   *
   * @private
   */
  _handleRemoveCollectionBlocKFromBackspace(view) {
    const resolvedPosition = view.state.tr.selection.$from,
      currentNode = resolvedPosition.node();

    const deleteBlock = ({ from, to }) => {
      this.editor.execute((state, dispatch) => {
        const { tr: transaction } = state;
        transaction.delete(from, to);
        dispatch(transaction);
      });
    };

    if (currentNode.type.name === 'weka_simple_multi_lang_lang_blocks') {
      // Cursor in the collection block or single block.
      deleteBlock({
        from: resolvedPosition.before(),
        to: resolvedPosition.after(),
      });

      return true;
    } else if (currentNode.type.name === 'weka_simple_multi_lang_lang_block') {
      // If the selection is within the single block, then we do not remove the whole collection block.
      return true;
    } else if (currentNode.type.name === 'paragraph') {
      // Check its parent node.
      const parentNode = resolvedPosition.node(resolvedPosition.depth - 1);
      if (parentNode.type.name === 'weka_simple_multi_lang_lang_block') {
        // Nope, we do not allow inline editing.
        return true;
      }
    }

    return false;
  }
}

export default options => new WekaSimpleMultiLangExtension(options);

/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module editor_weka
 */

import { cmdItem, ToolbarItem } from 'editor_weka/toolbar';
import { langString, isRtl } from 'tui/i18n';
import { Selection } from 'ext_prosemirror/state';
import { Fragment } from 'ext_prosemirror/model';
import { chainCommands } from 'ext_prosemirror/commands';
import BaseExtension from './Base';
import { isEmptyParagraph } from 'editor_weka/lib/prosemirror_utils/helpers';
import { findParentNodeOfTypeClosestToPos } from 'editor_weka/lib/prosemirror_utils/selection';
import { getInsertedPos } from 'editor_weka/util';
import LayoutBlock from 'editor_weka/components/nodes/LayoutBlock';
import LayoutColumn from 'editor_weka/components/nodes/LayoutColumn';
import AddIcon from 'tui/components/icons/Add';
import LayoutIcon from 'tui/components/icons/Layout';
import LayoutCol1Icon from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol1';
import LayoutCol2Icon from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2';
import LayoutCol2NarrowLeftIcon from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2NarrowLeft';
import LayoutCol2NarrowRightIcon from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol2NarrowRight';
import LayoutCol3Icon from 'editor_weka/components/editing/graphics/layout_icons_24/LayoutCol3';

/**
 * @typedef {object} LayoutConfig
 * @property {Array<{ type?: string }>} columns
 */

class LayoutExtension extends BaseExtension {
  nodes() {
    return {
      layout_block: {
        schema: {
          group: 'rootblock',
          content: 'layout_column+',
          isolating: true,
          defining: true,
          parseDOM: [{ tag: 'div.jsoneditor-layout-block' }],
          toDOM() {
            return ['div', { class: 'jsoneditor-layout-block' }, 0];
          },
        },
        component: LayoutBlock,
        componentContext: {
          getLayoutBlockDetails,
          updateLayout: this.updateLayout.bind(this),
          deleteLayout: this.deleteLayout.bind(this),
        },
      },

      layout_column: {
        schema: {
          content: 'block+',
          isolating: true,
          defining: true,
          attrs: {
            type: { default: undefined },
          },
          parseDOM: [
            {
              tag: 'div.jsoneditor-layout-column--sidebar',
              attrs: { type: 'sidebar' },
            },
            {
              tag: 'div.jsoneditor-layout-column',
              attrs: { type: undefined },
            },
          ],
          toDOM(node) {
            const classList = ['jsoneditor-layout-column'];

            if (node.attrs.type === 'sidebar') {
              classList.push('jsoneditor-layout-column--sidebar');
            }

            return ['div', { class: classList.join(' ') }, 0];
          },
        },
        component: LayoutColumn,
      },
    };
  }

  getLayoutOptions() {
    return [
      {
        label: langString('layout_one_column', 'editor_weka'),
        iconComponent: LayoutCol1Icon,
        columns: [{}],
      },
      {
        label: langString('layout_two_column', 'editor_weka'),
        columns: [{}, {}],
        iconComponent: LayoutCol2Icon,
      },
      {
        label: langString('layout_narrow_left_column', 'editor_weka'),
        columns: isRtl()
          ? [{}, { type: 'sidebar' }]
          : [{ type: 'sidebar' }, {}],
        iconComponent: LayoutCol2NarrowLeftIcon,
      },
      {
        label: langString('layout_narrow_right_column', 'editor_weka'),
        columns: isRtl()
          ? [{ type: 'sidebar' }, {}]
          : [{}, { type: 'sidebar' }],
        iconComponent: LayoutCol2NarrowRightIcon,
      },
      {
        label: langString('layout_three_column', 'editor_weka'),
        columns: [{}, {}, {}],
        iconComponent: LayoutCol3Icon,
      },
    ];
  }

  toolbarItems() {
    return [
      new ToolbarItem({
        group: 'layout',
        label: langString('layout', 'editor'),
        iconComponent: LayoutIcon,
        children: [
          cmdItem(addLayout, {
            label: langString('add_layout', 'editor_weka'),
            iconComponent: AddIcon,
            type: 'button',
          }),
          new ToolbarItem({ type: 'separator' }),
          ...this.getLayoutOptions().map(createSetLayoutItem),
        ],
      }),
    ];
  }

  /**
   * Update the layout in the given range (getRange)
   *
   * @param {() => ({ from: number, to: number })} getRange
   * @param {LayoutConfig} options
   */
  updateLayout(getRange, { columns }) {
    const state = this.editor.state;
    const range = getRange();

    const oldBlockNode = state.doc.nodeAt(range.from);

    const block = updateLayoutNode({ columns }, oldBlockNode);

    let tr = state.tr;

    tr.replaceWith(range.from, range.to, block);
    tr.setSelection(Selection.near(tr.doc.resolve(range.from + 2)));

    this.editor.dispatch(tr);
    this.editor.view.focus();
  }

  /**
   * Delete the layout at the given range
   *
   * @param {() => ({ from: number, to: number })} getRange
   */
  deleteLayout(getRange) {
    this.editor.execute((state, dispatch) => {
      const range = getRange();
      dispatch(state.tr.delete(range.from, range.to));
    });
  }
}

/**
 * Create a command to insert a layout with the specified options.
 *
 * @param {LayoutConfig} config
 * @returns {import('ext_prosemirror/state').Command}
 */
function insertLayoutOfType({ columns }) {
  return (state, dispatch) => {
    if (columns.length < 1) {
      return false;
    }

    if (!dispatch) {
      return true;
    }

    const { selection } = state;
    const { layout_block, layout_column } = state.schema.nodes;

    const block = layout_block.create(
      null,
      columns.map(col => layout_column.createAndFill({ type: col.type }))
    );

    let tr = state.tr;
    tr.insert(selection.to, block);

    const mapCount = tr.mapping.maps.length;

    let insertedPos = getInsertedPos(tr);
    if (insertedPos != null) {
      // If there was selected content, move it to the first layout column.
      if (!selection.empty) {
        const selectionInsertPos = insertedPos + 2;
        tr.replace(
          selectionInsertPos,
          selectionInsertPos + block.content.child(0).content.size,
          state.doc.slice(selection.from, selection.to)
        );
        // remove the content we just moved
        tr.delete(selection.from, selection.to);
      }

      // If cursor is in an empty paragraph, remove it
      if (selection.empty && isEmptyParagraph(selection.$from.parent)) {
        const paraPos = tr.mapping.map(selection.$from.before());
        tr.delete(paraPos, paraPos + selection.$from.parent.nodeSize);
      }

      // Map the position of our block through the changes that have happened since
      insertedPos = tr.mapping.slice(mapCount).map(insertedPos);

      // Move the selection to the end of the first column
      const firstColContentSize = tr.doc.nodeAt(insertedPos + 1).content.size;
      tr.setSelection(
        Selection.near(
          tr.doc.resolve(insertedPos + 2 + firstColContentSize),
          -1
        )
      );
    }

    dispatch(tr);
    return true;
  };
}

/**
 * Update the layout at the current selection.
 *
 * @param {LayoutConfig} config
 * @returns {import('prosemirror-state').Command}
 */
function updateLayoutAtSelection(config) {
  return (state, dispatch) => {
    const layoutNode = findParentNodeOfTypeClosestToPos(
      state.selection.$from,
      state.schema.nodes.layout_block
    );

    if (!layoutNode) {
      return false;
    }

    if (dispatch) {
      const newNode = updateLayoutNode(config, layoutNode.node);
      const tr = state.tr;
      tr.replaceWith(
        layoutNode.pos,
        layoutNode.pos + layoutNode.node.nodeSize,
        newNode
      );
      tr.setSelection(Selection.near(tr.doc.resolve(layoutNode.pos + 2)));
      dispatch(tr);
    }

    return true;
  };
}

/**
 * Create a new layout block from an existing one, using the provided config.
 *
 * @param {LayoutConfig} config
 * @param {import('ext_prosemirror/model').Node} oldLayout
 * @returns {import('ext_prosemirror/model').Node}
 */
function updateLayoutNode({ columns }, oldLayout) {
  const { layout_block, layout_column } = oldLayout.type.schema.nodes;

  const colCount = columns.length;

  return layout_block.create(
    null,
    columns.map((col, colIndex) => {
      const attrs = {
        type: col.type,
      };

      const child = oldLayout.maybeChild(colIndex);
      let colContent = child ? child.content : Fragment.empty;

      // If this is the last column, append the contents of the remaining columns too.
      if (colIndex === colCount - 1) {
        for (
          let oldColIndex = colIndex + 1;
          oldColIndex < oldLayout.childCount;
          oldColIndex++
        ) {
          const oldCol = oldLayout.child(oldColIndex);
          if (oldCol.childCount !== 1 || !isEmptyParagraph(oldCol.child(0))) {
            colContent = colContent.append(oldCol.content);
          }
        }
      }

      return (
        layout_column.createAndFill(attrs, colContent) ||
        layout_column.create(attrs)
      );
    })
  );
}

/** @type {import('prosemirror-state').Command} */
function addLayout(state, dispatch) {
  const layoutNode = findParentNodeOfTypeClosestToPos(
    state.selection.$from,
    state.schema.nodes.layout_block
  );

  const config = layoutNode
    ? getLayoutBlockDetails(layoutNode.node)
    : { columns: [{}, {}] };

  return insertLayoutOfType(config)(state, dispatch);
}

/**
 * Get information about the provided layout block node.
 *
 * @param {object} node
 * @returns {LayoutConfig}
 */
function getLayoutBlockDetails(node) {
  const columns = [];
  node.forEach(node => {
    columns.push({ ...node.attrs });
  });
  return { columns };
}

/**
 * Create a menu item to set the current layout.
 *
 * @param {LayoutConfig} config
 * @returns {ToolbarItem}
 */
function createSetLayoutItem(config) {
  const command = chainCommands(
    updateLayoutAtSelection(config),
    insertLayoutOfType(config)
  );
  return cmdItem(command, {
    label: config.label,
    iconComponent: config.iconComponent,
  });
}

export default opt => new LayoutExtension(opt);

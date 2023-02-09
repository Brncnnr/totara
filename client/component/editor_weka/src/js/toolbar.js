/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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

import { toggleMark, setBlockType } from 'ext_prosemirror/commands';

/**
 * Represents an item to display on the editor toolbar.
 */
export class ToolbarItem {
  constructor(opts) {
    this._opts = opts;
    this._editor = null;

    this._def = {
      type: opts.type,
      group: opts.group,
      label: opts.label,
      /**
       * A vue component
       * for dynamic icons, set `getIconComponent` to a function which returns a component. getIconComponent will be run on update
       */
      iconComponent: opts.iconComponent,
      enabled: true,
      active: null,
      children: opts.children && opts.children.map(x => x.getDef()),
      popover: opts.popover,
      execute: () => opts.execute(this._editor),
      reset: () => opts.reset(this._editor),
    };
  }

  update(editor) {
    const { _def: def, _opts: opts } = this;
    this._editor = editor;

    if (!this._editor.view.editable) {
      // The whole editor is disabled, hence we can make the toolbar item
      // to be disabled as well without questioning itself.
      def.enabled = false;
    } else {
      def.enabled = opts.enabled === undefined || opts.enabled(editor);
    }

    def.iconComponent =
      opts.getIconComponent !== undefined
        ? opts.getIconComponent(editor)
        : def.iconComponent;

    def.active = opts.active != null ? opts.active(editor) : null;
    if (opts.children) {
      opts.children.forEach(x => x.update(editor));
    }

    return Object.assign({}, def);
  }

  getDef() {
    return this._def;
  }
}

/**
 * Create a ToolbarItem that executes a command.
 *
 * @param {function} cmd
 * @param {object} options
 * @returns {ToolbarItem}
 */
export function cmdItem(cmd, options) {
  return new ToolbarItem(
    Object.assign(
      {
        enabled: editor => editor.canExecute(cmd),
        execute: editor => {
          editor.view.focus();
          editor.execute(cmd);
        },
      },
      options
    )
  );
}

/**
 * Create a ToolbarItem that toggles a mark.
 *
 * @param {Mark} mark
 * @param {object} options
 * @returns {ToolbarItem}
 */
export function markItem(mark, options) {
  options = Object.assign(
    { active: editor => isMarkActive(editor.state, mark) },
    options
  );
  const cmd = toggleMark(mark);
  const item = cmdItem(cmd, options);
  return item;
}

/**
 * Create a ToolbarItem that changes the current block type.
 *
 * @param {NodeType} nodeType
 * @param {object} matchOptions
 * @param {object} options
 * @returns {ToolbarItem}
 */
export function blockTypeItem(nodeType, matchOptions, options) {
  options = Object.assign(
    {
      active(editor) {
        let { $from, to, node } = editor.state.selection;
        const parent = $from.parent;

        // For all attributes we're ignoring, mock the match options to match the target's attribute value
        if (matchOptions.ignoreAttrs) {
          matchOptions.ignoreAttrs.forEach(attr => {
            if (node && !(attr in node.attrs)) {
              return;
            } else if (!(attr in parent.attrs)) {
              return;
            }

            if (!matchOptions.attrs) {
              matchOptions.attrs = {};
            }

            matchOptions.attrs[attr] = node
              ? node.attrs[attr]
              : parent.attrs[attr];
          });
        }

        if (node && node.isTextblock) {
          return node.hasMarkup(nodeType, matchOptions.attrs);
        }

        return (
          to <= $from.end() && parent.hasMarkup(nodeType, matchOptions.attrs)
        );
      },
    },
    options
  );
  const cmd = setBlockType(nodeType, matchOptions.attrs);
  return cmdItem(cmd, options);
}

/**
 * Check if the specified mark is active at the selection.
 *
 * @param {EditorState} state
 * @param {Mark} type
 */
function isMarkActive(state, type) {
  let { from, $from, to, empty } = state.selection;
  if (empty) return !!type.isInSet(state.storedMarks || $from.marks());
  else return !!state.doc.rangeHasMark(from, to, type);
}

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
 * @author Aaron Machin <aaron.machin@totaralearning.com>
 * @module editor_weka
 */

import BaseExtension from './Base';
import { ToolbarItem } from '../toolbar';
import LeftAlignIcon from 'tui/components/icons/AlignLeft';
import CenterAlignIcon from 'tui/components/icons/AlignCenter';
import RightAlignIcon from 'tui/components/icons/AlignRight';
import {
  setAlignmentOnNode,
  findSingleAttrValueInRange,
  typeHasAttr,
} from '../utils/internal/alignment';
import { langString, isRtl } from 'tui/i18n';

class AlignmentExtension extends BaseExtension {
  /**
   * Sets the given alignment to the highest parent node in the selection range
   * Will reset alignment for all children nodes
   *
   * @param {Editor} editor
   * @param {string} alignment
   * @returns {boolean}
   */
  setAlignment(editor, alignment) {
    const { state } = editor;
    const { tr, selection } = state;

    this.editor.view.focus();

    const blockRange = selection.$from.blockRange(selection.$to);
    if (!blockRange) {
      return false;
    }

    const rangeStart = blockRange.$from.start(blockRange.depth);
    blockRange.parent.nodesBetween(
      blockRange.$from.pos - rangeStart,
      blockRange.$to.pos - rangeStart,
      (node, pos) => {
        if (
          (alignment === 'right' && isRtl()) ||
          (alignment === 'left' && !isRtl())
        ) {
          setAlignmentOnNode(tr, node, pos, undefined);
          return;
        }

        setAlignmentOnNode(tr, node, pos, alignment);
      },
      rangeStart
    );

    if (!tr.docChanged) {
      return false;
    }

    this.editor.dispatch(tr);
    return true;
  }

  /**
   * Checks if the current selection has the alignment given selected
   *
   * @param {Editor} editor
   * @param {string} alignment
   * @returns {boolean}
   */
  isAlignmentActive(editor, alignment) {
    const { state } = editor;
    const { selection } = state;

    const attrValue = findSingleAttrValueInRange(
      state.doc,
      selection.from,
      selection.to,
      'align'
    );

    // All alignments in selection are default (undefined), see if the alignment we are searching for matches our default
    if (
      attrValue === undefined &&
      ((alignment === 'left' && !isRtl()) || (alignment === 'right' && isRtl()))
    ) {
      return true;
    }

    return attrValue === alignment;
  }

  /**
   * Based on the current selection, will return the correct alignment icon. Defaults to LeftAlignIcon.
   * Used as the dropdown's icon.
   *
   * @param {Editor} editor
   * @returns {Vue.Component}
   */
  getAlignmentIcon(editor) {
    const { state } = editor;
    const { selection } = state;

    const attrValue = findSingleAttrValueInRange(
      state.doc,
      selection.from,
      selection.to,
      'align'
    );

    switch (attrValue) {
      case 'center':
        return CenterAlignIcon;
      case 'right':
        return RightAlignIcon;
      case 'left':
        return LeftAlignIcon;
    }

    return isRtl() ? RightAlignIcon : LeftAlignIcon;
  }

  /**
   * Searches the selection to see if alignment can be set on any of those nodes [within the selection]
   *
   * @param {Editor} editor
   * @returns {boolean}
   */
  canSetAlignment(editor) {
    const { state } = editor;
    const { selection } = state;

    let alignable = false;

    const blockRange = selection.$from.blockRange(selection.$to);
    if (!blockRange) {
      return false;
    }

    const rangeStart = blockRange.$from.start(blockRange.depth);
    blockRange.parent.nodesBetween(
      blockRange.$from.pos - rangeStart,
      blockRange.$to.pos - rangeStart,
      node => {
        if (!typeHasAttr(node.type, 'align')) {
          return;
        }

        alignable = true;
      },
      rangeStart
    );

    return alignable;
  }

  toolbarItems() {
    return [
      new ToolbarItem({
        group: 'text',
        label: langString('alignment', 'editor'),
        getIconComponent: this.getAlignmentIcon,
        enabled: editor => this.canSetAlignment(editor),
        children: [
          new ToolbarItem({
            group: 'text',
            label: langString('left', 'editor'),
            iconComponent: LeftAlignIcon,
            execute: editor => this.setAlignment(editor, 'left'),
            active: editor => this.isAlignmentActive(editor, 'left'),
          }),

          new ToolbarItem({
            group: 'text',
            label: langString('centre', 'editor'),
            iconComponent: CenterAlignIcon,
            execute: editor => this.setAlignment(editor, 'center'),
            active: editor => this.isAlignmentActive(editor, 'center'),
          }),

          new ToolbarItem({
            group: 'text',
            label: langString('right', 'editor'),
            iconComponent: RightAlignIcon,
            execute: editor => this.setAlignment(editor, 'right'),
            active: editor => this.isAlignmentActive(editor, 'right'),
          }),
        ],
      }),
    ];
  }
}

export default opt => new AlignmentExtension(opt);

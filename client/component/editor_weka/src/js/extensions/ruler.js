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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @module editor_weka
 */

import BaseExtension from './Base';
import { ToolbarItem } from '../toolbar';
import { langString } from 'tui/i18n';
import HorizontalRuleIcon from 'tui/components/icons/HorizontalRule';

class RulerExtension extends BaseExtension {
  nodes() {
    return {
      ruler: {
        schema: {
          group: 'block',
          parseDOM: [{ tag: 'hr' }],
          toDOM() {
            return ['hr'];
          },
        },
      },
    };
  }

  toolbarItems() {
    return [
      new ToolbarItem({
        group: 'embeds',
        label: langString('horizontalrule', 'editor'),
        iconComponent: HorizontalRuleIcon,
        execute: editor => {
          editor.execute((state, dispatch) => {
            let hr = state.schema.nodes.ruler;
            dispatch(state.tr.replaceSelectionWith(hr.create()));
            editor.view.focus();
          });
        },
      }),
    ];
  }

  keymap(bind) {
    const { ruler } = this.getSchema().nodes;
    bind('Mod-_', (state, dispatch) => {
      dispatch(state.tr.replaceSelectionWith(ruler.create()).scrollIntoView());
      return true;
    });
  }
}

export default opt => new RulerExtension(opt);

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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module tui
 */

import { reconcileFormats, toPlain } from '../editor_abstraction';
import { EditorContent, Format } from 'tui/editor';

const simpleContent =
  '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"abc"}]}]}';
const richContent =
  '{"type":"doc","content":[{"type":"ordered_list","attrs":{"order":"1"},"content":[{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","text":"abc"},{"type":"emoji","attrs":{"shortcode":"1F642"}},{"type":"hard_break"},{"type":"hard_break"}]}]},{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","text":" def "}]}]}]},{"type":"paragraph","content":[]},{"type":"paragraph","content":[{"type":"text","marks":[{"type":"link","attrs":{"href":"http://example.com"}}],"text":"link"}]},{"type":"paragraph","content":[]}]}';
const headingsAndLists =
  '{"type":"doc","content":[{"type":"heading","attrs":{"level":1},"content":[{"type":"text","text":"Heading. "}]},{"type":"paragraph","content":[]},{"type":"heading","attrs":{"level":2},"content":[{"type":"text","text":"Sub"}]},{"type":"paragraph","content":[]},{"type":"paragraph","content":[{"type":"text","marks":[{"type":"strong"}],"text":"bold"}]},{"type":"paragraph","content":[]},{"type":"ordered_list","attrs":{"order":"1"},"content":[{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","text":"hjsjs"}]}]},{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","text":"dggd"}]}]}]},{"type":"paragraph","content":[]},{"type":"bullet_list","content":[{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","text":"gsyys"}]}]},{"type":"list_item","content":[{"type":"paragraph","content":[{"type":"text","marks":[{"type":"em"}],"text":"sjuus"}]}]}]},{"type":"paragraph","content":[]}]}';

describe('toPlain', () => {
  it('extracts text from simple text content', () => {
    const plain = toPlain(simpleContent);
    expect(plain).toEqual('abc\n\n');
  });

  it('extracts text from rich content', () => {
    const plain = toPlain(richContent);
    expect(plain).toEqual('abc\n\n\n\n def \n\n\n\n\n\nlink\n\n\n\n');
  });

  it('formats breaks between paragraphs with 2 line breaks', () => {
    const plain = toPlain(headingsAndLists);
    expect(plain).toEqual(
      'Heading. \n\n\n\nSub\n\n\n\nbold\n\n\n\nhjsjs\n\ndggd\n\n\n\n\n\ngsyys\n\nsjuus\n\n\n\n\n\n'
    );
  });
});

describe('reconcileFormat', () => {
  it('returns the same value when the format is supported', () => {
    const value = new EditorContent({
      format: Format.JSON_EDITOR,
      content: simpleContent,
    });

    const reconciled = reconcileFormats(value, {
      from: null,
      to: Format.JSON_EDITOR,
    });

    expect(reconciled).toBe(value);
  });
});

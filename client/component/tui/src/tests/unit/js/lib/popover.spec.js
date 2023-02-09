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
 * @module tui
 */

import { position } from 'tui/lib/popover';
import { Rect, Size, Point } from 'tui/geometry';
import { isRtl, langSide } from 'tui/i18n';

describe('position', () => {
  beforeEach(() => {
    isRtl.mockReset().mockReturnValue(false);
    langSide.mockImplementation(dir => {
      return dir;
    });
  });

  it('positions popover on requested side', () => {
    const viewport = new Rect(0, 0, 1920, 1080);
    const ref = new Rect(500, 500, 100, 50);
    const size = new Size(200, 100);
    const padding = 10;
    const options = { viewport, ref, size, padding };

    let result;

    result = position({ ...options, position: ['top'] });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(450, 400));
    expect(result.arrowDistance).toBe(size.width / 2 - padding);

    result = position({ ...options, position: ['bottom'] });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(450, 550));
    expect(result.arrowDistance).toBe(size.width / 2 - padding);

    result = position({ ...options, position: ['left'] });
    expect(result.side).toBe('left');
    expect(result.location).toEqual(new Point(300, 475));
    expect(result.arrowDistance).toBe(size.height / 2 - padding);

    result = position({ ...options, position: ['right'] });
    expect(result.side).toBe('right');
    expect(result.location).toEqual(new Point(600, 475));
    expect(result.arrowDistance).toBe(size.height / 2 - padding);

    // fallback to bottom when insufficient room
    result = position({
      ...options,
      viewport: new Rect(0, 0, 100, 100),
      ref: new Rect(0, 0, 50, 50),
      position: ['right'],
    });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(0, 50));
    expect(result.arrowDistance).toBe(15);
  });

  it('RTL languages are on the correct side', () => {
    // And now Rtl
    isRtl.mockImplementation(() => true);
    langSide.mockImplementation(dir => {
      switch (dir) {
        case 'right':
          return 'left';
        case 'left':
          return 'right';
        default:
          return dir;
      }
    });
    const viewport = new Rect(0, 0, 1920, 1080);
    const ref = new Rect(500, 500, 100, 50);
    const size = new Size(200, 100);
    const padding = 10;
    const options = { viewport, ref, size, padding };
    let result;

    result = position({ ...options, position: ['top'] });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(450, 400));
    expect(result.arrowDistance).toBe(size.width / 2 - padding * 2);

    result = position({ ...options, position: ['bottom'] });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(450, 550));
    expect(result.arrowDistance).toBe(size.width / 2 - padding * 2);

    result = position({ ...options, position: ['left'] });
    expect(result.side).toBe('right');
    expect(result.location).toEqual(new Point(600, 475));
    expect(result.arrowDistance).toBe(size.height / 2 - padding);

    result = position({ ...options, position: ['right'] });
    expect(result.side).toBe('left');
    expect(result.location).toEqual(new Point(300, 475));
    expect(result.arrowDistance).toBe(size.height / 2 - padding);

    // fallback to bottom when insufficient room
    result = position({
      ...options,
      viewport: new Rect(0, 0, 100, 100),
      ref: new Rect(0, 0, 50, 50),
      position: ['right'],
    });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(0, 50));
    expect(result.arrowDistance).toBe(155);
  });

  it('allows specifying a secondary side', () => {
    const viewport = new Rect(0, 0, 1920, 1080);
    const ref = new Rect(500, 500, 100, 50);
    const size = new Size(200, 100);
    const padding = 10;
    const options = { viewport, ref, size, padding };

    let result;

    result = position({ ...options, position: ['top', 'left'] });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(490, 400));
    expect(result.arrowDistance).toBe(50);

    result = position({ ...options, position: ['top', 'right'] });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(410, 400));
    expect(result.arrowDistance).toBe(130);

    result = position({ ...options, position: ['bottom', 'left'] });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(490, 550));
    expect(result.arrowDistance).toBe(50);

    result = position({ ...options, position: ['bottom', 'right'] });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(410, 550));
    expect(result.arrowDistance).toBe(130);

    result = position({ ...options, position: ['left', 'top'] });
    expect(result.side).toBe('left');
    expect(result.location).toEqual(new Point(300, 490));
    expect(result.arrowDistance).toBe(25);

    result = position({ ...options, position: ['left', 'bottom'] });
    expect(result.side).toBe('left');
    expect(result.location).toEqual(new Point(300, 460));
    expect(result.arrowDistance).toBe(55);

    result = position({ ...options, position: ['right', 'top'] });
    expect(result.side).toBe('right');
    expect(result.location).toEqual(new Point(600, 490));
    expect(result.arrowDistance).toBe(25);

    result = position({ ...options, position: ['right', 'bottom'] });
    expect(result.side).toBe('right');
    expect(result.location).toEqual(new Point(600, 460));
    expect(result.arrowDistance).toBe(55);
  });

  it('keeps popover within the viewport', () => {
    const viewport = new Rect(0, 500, 1920, 1080);
    const size = new Size(200, 100);
    const padding = 10;
    const options = { viewport, size, padding, preferSlide: true };

    let result;

    // overlapping top -> bottom
    result = position({
      ...options,
      ref: new Rect(500, viewport.top + 90, 100, 50),
      position: ['top'],
    });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(450, viewport.top + 140));

    // overlapping bottom -> top
    result = position({
      ...options,
      ref: new Rect(500, viewport.bottom - 100, 100, 50),
      position: ['bottom'],
    });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(450, viewport.bottom - 200));

    // overlapping left -> right
    result = position({
      ...options,
      ref: new Rect(50, viewport.top + 500, 100, 50),
      position: ['left'],
    });
    expect(result.side).toBe('right');
    expect(result.location).toEqual(new Point(150, viewport.top + 475));

    // overlapping right -> left
    result = position({
      ...options,
      ref: new Rect(viewport.right - 150, viewport.top + 500, 100, 50),
      position: ['right'],
    });
    expect(result.side).toBe('left');
    expect(result.location).toEqual(
      new Point(viewport.right - 350, viewport.top + 475)
    );

    // if ref is outside of viewport
    result = position({
      ...options,
      ref: new Rect(500, viewport.bottom + 150, 100, 50),
      position: ['bottom'],
    });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(450, viewport.bottom + 50));

    // arrow should slide along edge
    result = position({
      ...options,
      ref: new Rect(viewport.right - 70, viewport.top + 500, 50, 50),
      position: ['bottom'],
    });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(
      new Point(viewport.right - size.width, viewport.top + 550)
    );
    expect(result.arrowDistance).toBe(145);

    // arrow should slide along edge, until it can slide no longer
    result = position({
      ...options,
      ref: new Rect(500, viewport.top, 50, 50),
      position: ['right'],
    });
    expect(result.side).toBe('bottom');
    expect(result.location).toEqual(new Point(425, viewport.top + 50));
    expect(result.arrowDistance).toBe(90);
    result = position({
      ...options,
      ref: new Rect(500, viewport.bottom - 50, 50, 50),
      position: ['right'],
    });
    expect(result.side).toBe('top');
    expect(result.location).toEqual(new Point(425, viewport.bottom - 150));
    expect(result.arrowDistance).toBe(90);
  });
});

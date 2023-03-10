function scrollSpy(container) {
  var INIT_DELAY_MS = 300
  var SCROLL_DEBOUNCE_MS = 100
  var RESIZE_DEBOUNCE_MS = 500

  var PADDING = 5
  // If we are applying a scroll padding, we'll be doing it to the HTML element
  // so we'll check it to see if we should do something different than the default
  // by trying to get the value from the styles
  var htmlElement = document.querySelector('html')
  if (htmlElement) {
    var scrollPaddingTop = window.getComputedStyle(htmlElement).scrollPaddingTop
    if (
      scrollPaddingTop &&
      typeof scrollPaddingTop === 'string' &&
      scrollPaddingTop !== 'auto' &&
      scrollPaddingTop.endsWith('px')
    ) {
      PADDING = PADDING + parseInt(scrollPaddingTop.split('px')[0])
    }
  }
  var ACTIVE_CLASS = 'nav-scroll-active'
  var EXPAND_CLASS = 'nav-scroll-expand'
  var EXPANDABLE_SELECTOR = '.nav-group-section'

  var currentIndex = null
  var sections = [] // [{ id: 'query-someQuery', top: 1234 }]

  function init() {
    findScrollPositions()
    handleScroll()
    window.addEventListener('scroll', handleScroll)
    window.addEventListener('resize', handleResize)
  }

  function findScrollPositions() {
    // Inspired by: https://codepen.io/zchee/pen/ogzvZZ
    currentIndex = null
    var allScrollableItems = container.querySelectorAll('[data-traverse-target]')
    Array.prototype.forEach.call(allScrollableItems, function (e) {
      sections.push({ id: e.id, top: e.offsetTop })
    })
  }

  function isElementInViewport(el) {
    // https://stackoverflow.com/a/7557433/347554
    var rect = el.getBoundingClientRect()
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <=
        (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    )
  }

  var handleResize = debounce(function () {
    findScrollPositions()
    handleScroll()
  }, RESIZE_DEBOUNCE_MS)

  var handleScroll = debounce(function () {
    var scrollPosition =
      document.documentElement.scrollTop || document.body.scrollTop
    var index = getVisibleSectionIndex(scrollPosition)

    if (index === currentIndex) {
      return
    }

    currentIndex = index
    var section = sections[index]

    var getParentSection = function (el) {
      if (!el || !el.closest) return null
      return el.closest(EXPANDABLE_SELECTOR)
    }

    var activeEl = container.querySelector(`.${ACTIVE_CLASS}`)
    var nextEl = section
      ? container.querySelector('#nav a[href*=' + section.id + ']')
      : null

    var parentNextEl = getParentSection(nextEl)
    var parentActiveEl = getParentSection(activeEl)
    var isDifferentParent = parentActiveEl !== parentNextEl

    if (parentActiveEl && isDifferentParent) {
      parentActiveEl.classList.remove(EXPAND_CLASS)
    }
    if (parentNextEl && isDifferentParent) {
      parentNextEl.classList.add(EXPAND_CLASS)
    }

    if (nextEl) {
      // TOTARA: update URL hash on scroll
      window.location.hash = section.id;
      nextEl.classList.add(ACTIVE_CLASS)
      if (nextEl.scrollIntoViewIfNeeded) {
        nextEl.scrollIntoViewIfNeeded()
      } else if (nextEl.scrollIntoView && !isElementInViewport(nextEl)) {
        nextEl.scrollIntoView({ block: 'center', inline: 'start' })
      }
    }

    if (activeEl) {
      activeEl.classList.remove(ACTIVE_CLASS)
    }
  }, SCROLL_DEBOUNCE_MS)

  function getVisibleSectionIndex(scrollPosition) {
    var positionToCheck = scrollPosition + PADDING
    for (var i = 0; i < sections.length; i++) {
      var section = sections[i]
      var nextSection = sections[i + 1]
      if (
        positionToCheck >= section.top &&
        (!nextSection || positionToCheck < nextSection.top)
      ) {
        return i
      }
    }
    return -1
  }

  setTimeout(init, INIT_DELAY_MS)
}
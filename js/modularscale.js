/* Modular Scales */
var modularScales = {
  1: {
    'name': 'minor second',
    'ratio': '15:16',
    'numerical': 1.067
  },
  2: {
    'name': 'major second',
    'ratio': '8:9',
    'numerical': 1.125
  },
  3: {
    'name': 'minor third',
    'ratio': '5:6',
    'numerical': 1.2
  },
  4: {
    'name': 'major third',
    'ratio': '4:5',
    'numerical': 1.25
  },
  5: {
    'name': 'perfect fourth',
    'ratio': '3:4',
    'numerical': 1.333
  },
  6: {
    'name': 'aug. fourth / dim. fifth',
    'ratio': '1:√2',
    'numerical': 1.414
  },
  7: {
    'name': 'perfect fifth',
    'ratio': '2:3',
    'numerical': 1.5
  },
  8: {
    'name': 'minor sixth',
    'ratio': '5:8',
    'numerical': 1.6
  },
  9: {
    'name': 'golden section',
    'ratio': '1:1.618',
    'numerical': 1.618
  },
  10: {
    'name': 'major sixth',
    'ratio': '3:5',
    'numerical': 1.667
  },
  11: {
    'name': 'minor seventh',
    'ratio': '9:16',
    'numerical': 1.778
  },
  12: {
    'name': 'major seventh',
    'ratio': '8:15',
    'numerical': 1.875
  },
  13: {
    'name': 'octave',
    'ratio': '1:2',
    'numerical': 2
  },
  14: {
    'name': 'major tenth',
    'ratio': '2:5',
    'numerical': 2.5
  },
  15: {
    'name': 'major eleventh',
    'ratio': '3:8',
    'numerical': 2.667
  },
  16: {
    'name': 'major twelfth',
    'ratio': '1:3',
    'numerical': 3
  },
  17: {
    'name': 'double octave',
    'ratio': '1:4',
    'numerical': 4
  }
};

/* Responsive breakpoints */
var breakpoints = {
  'extrasmall': 320,
  'small': 576,
  'medium': 768,
  'large': 992,
  'extralarge': 1200,
  'massive': 1600
};

/**
 * Calculate modular scale
 * @param {int} base Base pixel size
 * @param {int} ratio Ratio to apply
 * @param {int} value Step to apply
 */
function modularScale(base, ratio, value) {
  var ms = base * Math.pow(ratio, value);
  var limit = ms.toFixed(2);
  return Number.parseFloat(limit);
}

/**
 * Get slides set to apply scaling to
 */
function getSlides() {
  return document.querySelectorAll('section[data-textsize-base]');
}

/**
 * Set base font sizes
 * @param {HTMLCollection} elements Applicable slides
 * @param {int} base Base pixel size
 */
function applyBaseSize(element) {
  var dataset = element.dataset;
  if (dataset.textsizeBase) {
    var base = parseInt(dataset.textsizeBase);
  } else {
    var base = 16;
  }
  breakpoint = getBreakpoint(element.offsetWidth);
  if (breakpoint == 'smaller') {
    var modifier = base;
  } else if (breakpoint == 'extrasmall') {
    var modifier = base + (presentationTextsizingFactor * 1);
  } else if (breakpoint == 'small') {
    var modifier = base + (presentationTextsizingFactor * 2);
  } else if (breakpoint == 'medium') {
    var modifier = base + (presentationTextsizingFactor * 3);
  } else if (breakpoint == 'large') {
    var modifier = base + (presentationTextsizingFactor * 4);
  } else if (breakpoint == 'extralarge') {
    var modifier = base + (presentationTextsizingFactor * 5);
  } else if (breakpoint == 'massive') {
    var modifier = base + (presentationTextsizingFactor * 6);
  } else {
    var modifier = base;
  }
  element.style.fontSize = modifier + 'px';
}

/**
 * Set header font sizes
 * @param {HTMLCollection} elements Applicable slides
 * @param {int} base Base pixel size
 */
function applyHeaderSizes(element) {
  var dataset = element.dataset;
  if (dataset.textsizeBase) {
    var base = parseInt(dataset.textsizeBase);
    if (base <= 16) {
      base = 16;
    }
  } else {
    var base = 16;
  }
  var headers = {
    h1: element.getElementsByTagName('h1'),
    h2: element.getElementsByTagName('h2'),
    h3: element.getElementsByTagName('h3'),
    h4: element.getElementsByTagName('h4'),
    h5: element.getElementsByTagName('h5'),
    h6: element.getElementsByTagName('h6')
  };
  breakpoint = getBreakpoint(element.offsetWidth);
  if (breakpoint == 'smaller') {
    var modifier = base;
  } else if (breakpoint == 'extrasmall') {
    var modifier = base + (presentationTextsizingFactor * 1);
  } else if (breakpoint == 'small') {
    var modifier = base + (presentationTextsizingFactor * 2);
  } else if (breakpoint == 'medium') {
    var modifier = base + (presentationTextsizingFactor * 3);
  } else if (breakpoint == 'large') {
    var modifier = base + (presentationTextsizingFactor * 4);
  } else if (breakpoint == 'extralarge') {
    var modifier = base + (presentationTextsizingFactor * 5);
  } else if (breakpoint == 'massive') {
    var modifier = base + (presentationTextsizingFactor * 6);
  } else {
    var modifier = base;
  }
  var modularScales = {
    h1: modularScale(modifier, dataset.textsizeScale, 5),
    h2: modularScale(modifier, dataset.textsizeScale, 4),
    h3: modularScale(modifier, dataset.textsizeScale, 3),
    h4: modularScale(modifier, dataset.textsizeScale, 2),
    h5: modularScale(modifier, dataset.textsizeScale, 1),
    h6: modularScale(modifier, dataset.textsizeScale, 0)
  };
  Object.entries(headers).forEach(([key, value]) => {
    Array.prototype.forEach.call(value, function (header) {
      header.style.fontSize = modularScales[key] + 'px';
    });
  });
}

/**
 * Find applicable breakproint from given width
 * @param {int} width Element width
 */
function getBreakpoint(width) {
  if (width < breakpoints.extrasmall) {
    return 'smaller';
  } else if (width >= breakpoints.extrasmall && width < breakpoints.small) {
    return 'extrasmall';
  } else if (width >= breakpoints.small && width < breakpoints.medium) {
    return 'small';
  } else if (width >= breakpoints.medium && width < breakpoints.large) {
    return 'medium';
  } else if (width >= breakpoints.large && width < breakpoints.extralarge) {
    return 'large';
  } else if (width >= breakpoints.extralarge && width < breakpoints.massive) {
    return 'extralarge';
  } else if (width >= breakpoints.massive) {
    return 'massive';
  }
}

/**
 * Apply modular scale logic
 */
function applyModularScale(target) {
  applyBaseSize(target);
  applyHeaderSizes(target);
}

/**
 * Apply modular scale logic on current slide changes in width
 */
function onChange() {
  var currentSlide = document.querySelector('section section.present');
  applyModularScale(currentSlide);
}

/* Debounce and throttle */
/* @see https://codepen.io/dreit/pen/gedMez?editors=0010 */
var forLastExec = 100;
var delay = 200;
var throttled = false;
var calls = 0;

/* Run after slide changes or loads */
Reveal.addEventListener('slidechanged', function (event) {
  if (!throttled) {
    onChange();
    throttled = true;
    setTimeout(function () {
      throttled = false;
    }, delay);
  }
  clearTimeout(forLastExec);
  forLastExec = setTimeout(onChange, delay);
});

/* Run after slide changes size */
window.addEventListener("resize", function () {
  if (!throttled) {
    onChange();
    throttled = true;
    setTimeout(function () {
      throttled = false;
    }, delay);
  }
  clearTimeout(forLastExec);
  forLastExec = setTimeout(onChange, delay);
});
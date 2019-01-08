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
  if (currentSlide.classList.contains('textsizing')) {
    applyModularScale(currentSlide);
  }
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
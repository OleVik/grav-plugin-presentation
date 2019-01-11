/**
 * Find fitting font size from given width
 * @param {int} width Element width
 */
function getFontSize(width) {
  var keys = [];
  Array.prototype.forEach.call(presentationBreakpoints, function (breakpoint) {
    keys.push(breakpoint.width);
  });
  var match = getClosest(width, keys);
  var result = presentationBreakpoints.filter(breakpoint => breakpoint.width == match);
  return result[0].font_size;
}

/**
 * Set base font size
 * @param {HTMLCollection} elements Applicable slides
 * @param {int} base Base pixel size
 */
function applyBaseSize(element) {
  if (element.dataset.textsizeBase) {
    var fontSize = parseFloat(element.dataset.textsizeBase);
  } else {
    var fontSize = getFontSize(element.offsetWidth);
  }
  element.style.fontSize = fontSize + 'px';
}

/**
 * Set header font sizes
 * @param {HTMLCollection} elements Applicable slides
 * @param {int} base Base pixel size
 */
function applyHeaderSizes(element) {
  var currentSlide = document.querySelector('section section.present');
  var headers = {
    h1: element.getElementsByTagName('h1'),
    h2: element.getElementsByTagName('h2'),
    h3: element.getElementsByTagName('h3'),
    h4: element.getElementsByTagName('h4'),
    h5: element.getElementsByTagName('h5'),
    h6: element.getElementsByTagName('h6')
  };
  var ms = ModularScale({
    ratio: parseFloat(element.dataset.textsizeScale),
    base: getFontSize(currentSlide.offsetWidth) + 'px'
  })
  var modularScales = {
    h1: ms(6, true),
    h2: ms(5, true),
    h3: ms(4, true),
    h4: ms(3, true),
    h5: ms(2, true),
    h6: ms(1, true)
  };
  Object.entries(headers).forEach(([key, value]) => {
    Array.prototype.forEach.call(value, function (header) {
      header.style.fontSize = modularScales[key] + 'em';
    });
  });
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
    applyBaseSize(currentSlide);
    applyModularScale(currentSlide);
  }
}

/* Debounce and throttle */
/* @see https://codepen.io/dreit/pen/gedMez?editors=0010 */
var forLastExec = 100;
var delay = 200;
var throttled = false;
var calls = 0;

/* Run after slide loads */
Reveal.addEventListener('ready', function (event) {
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

/* Run after slide changes */
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
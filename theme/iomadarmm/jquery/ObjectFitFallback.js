// objectFitFallback.js
// requires modernizr

// REFERENCES:
// https://css-tricks.com/almanac/properties/o/object-fit/

(function(Modernizr) {

  // switch between height:100% and width:100% based on comparison of img and container aspect ratios
  function coverFillSwitch(container, img, invert) {
    if (!container || !img) return false;

    var imgHeight = img.naturalHeight || img.videoHeight;
    var imgWidth = img.naturalWidth || img.videoWidth;
    var containerRatio = container.offsetWidth / container.offsetHeight;
    var imgRatio = imgWidth / imgHeight;

    var ratioComparison = false;
    if (imgRatio >= containerRatio) ratioComparison = true;
    if (invert) ratioComparison = !ratioComparison; // flip the bool

    if (ratioComparison) {
      img.style.height = '100%';
      img.style.width = 'auto';
    } else {
      img.style.height = 'auto';
      img.style.width = '100%';
    }

  }

  function objectFitResize() {
    var i, img, container;

    var imgsCover = document.getElementsByClassName('object-fit__cover');
    for (i = 0; i < imgsCover.length; i++) {
      img = imgsCover[i];
      container = img.parentElement;
      if (container.classList.contains('object-fit__container')) {
        coverFillSwitch(container, img);
      }
    }

    var imgsContain = document.getElementsByClassName('object-fit__contain');
    for (i = 0; i < imgsContain.length; i++) {
      img = imgsContain[i];
      container = img.parentElement;
      if (container.classList.contains('object-fit__container')) {
        coverFillSwitch(container, img, true);
      }
    }
  }

  // add absolute center image css properties
  function applyStandardProperties(container, img) {
    var containerStyle = window.getComputedStyle(container);
    if (containerStyle.overflow !== 'hidden') container.style.overflow = 'hidden';
    if (containerStyle.position !== 'relative' &&
      containerStyle.position !== 'absolute' &&
      containerStyle.position !== 'fixed') container.style.position = 'relative';
    img.style.position = 'absolute';
    img.style.top = '50%';
    img.style.left = '50%';
    img.style.transform = 'translate(-50%,-50%)';
  }

  function objectFitInt() {

    var imgs = document.querySelectorAll('[class*="object-fit__"]');
    for (var i = 0; i < imgs.length; i++) {

      var type = 'cover';
      var img = imgs[i];
      var container = img.parentElement;

      if (img.classList.contains('object-fit__container')) type = 'container';
      if (img.classList.contains('object-fit__cover')) type = 'cover';
      if (img.classList.contains('object-fit__fill')) type = 'fill';
      if (img.classList.contains('object-fit__contain')) type = 'contain';
      if (img.classList.contains('object-fit__none')) type = 'none';
      if (img.classList.contains('object-fit__scale-down')) type = 'scale-down';

      switch (type) {
        case 'container':
          break;
        case 'cover':
          coverFillSwitch(container, img);
          applyStandardProperties(container, img);
          break;
        case 'contain': // opposite of cover
          coverFillSwitch(container, img, true);
          applyStandardProperties(container, img);
          break;
        case 'fill':
          img.style.height = '100%';
          img.style.width = '100%';
          applyStandardProperties(container, img);
          break;
        case 'none':
          img.style.height = 'auto';
          img.style.width = 'auto';
          applyStandardProperties(container, img);
          break;
        case 'scale-down':
          img.style.maxHeight = '100%';
          img.style.maxWidth = '100%';
          img.style.height = 'auto';
          img.style.width = 'auto';
          applyStandardProperties(container, img);
          break;
        default:
          break;
      }
    }
  }

  var resizeTimeout;

  function resizeThrottler() { // @source https://developer.mozilla.org/en-US/docs/Web/Events/resize
    if (!resizeTimeout) {
      resizeTimeout = setTimeout(function() {
        resizeTimeout = null;
        objectFitResize();
      }, 66); // The objectFitResize will execute at a rate of 15fps
    }
  }

  // Modernizr.objectfit = Modernizr.testAllProps('objectFit'); // detect IE and Edge
  if (!Modernizr.objectfit) {
    window.addEventListener('load', objectFitInt, false);
    window.addEventListener('resize', resizeThrottler, false);
  }

})(Modernizr);
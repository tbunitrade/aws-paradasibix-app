// Check if visitor is a bot and if so, skip deferred loading
if (typeof window.fu_is_bot_useragent === "undefined") {
  window.fu_is_bot_useragent = 0;
}
if (typeof fu_bot_useragents !== "undefined") {
  for (var j = 0; j < fu_bot_useragents.length; j++) {
      if (window.navigator.userAgent.toLowerCase().indexOf(fu_bot_useragents[j]) !== -1) {
          window.fu_is_bot_useragent = 1;
          break;
      }
  }
}

// Slide Show Functionality
if (typeof window.fu_slideIndex === "undefined") {
  window.fu_slideIndex = new Array();
}
if (typeof window.fu_slideTimeout === "undefined") {
  window.fu_slideTimeout = new Array();
}

function fuPlusSlides(n, id) {
  fuShowSlide(window.fu_slideIndex[id] += n, id);
}

function fuCurrentSlide(n, id) {
  fuShowSlide(window.fu_slideIndex[id] = n, id);
}

function fuShowSlide(n, id) {
  var i, slides = document.getElementsByClassName("fu_slide_" + id),
    dots = document.getElementsByClassName("fu_slidedot_" + id);
  if (n >= slides.length) {
    window.fu_slideIndex[id] = 0;
  }
  if (n < 0) {
    window.fu_slideIndex[id] = slides.length - 1;
  }
  for (i = 0; i < slides.length; i++) {
    slides[i].style.display = "none";
  }
  for (i = 0; i < dots.length; i++) {
    dots[i].className = dots[i].className.replace(" fu_slideactive", "");
  }
  if (slides.length > 0) {
    slides[window.fu_slideIndex[id]].style.display = "block";
  }
  if (dots.length > 0) {
    dots[window.fu_slideIndex[id]].className += " fu_slideactive";
  }
}

function fuLoadNextSlide(id) {
  var slides = document.getElementsByClassName("fu_slide_" + id);

  for (var i = 0; i < slides.length; i++) {
    if (slides[i].style.display == "none") {
      slides[i].style.display = "block";
      if (i+1 >= slides.length)
      {
        var loadmore = document.getElementsByClassName("fu_slideloadmore_" + id);
        for (var i = 0; i < loadmore.length; i++)
          loadmore[i].style.display = "none";
      }
      return;
    }
  }
}

function fuStartSlideShow(id) {
    fuPlusSlides(1, id);
    window.fu_slideTimeout[id] = setTimeout(fuStartSlideShow, window.fu_slideshowtimer, id);
}

function fuStopSlideShow(id) {
  clearTimeout(window.fu_slideTimeout[id]);
}

function fuGoToAnchor(anchor) {
  var loc = document.location.toString().split('#')[0];
  document.location = loc + '#' + anchor;
  return false;
}


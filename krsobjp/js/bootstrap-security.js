/*
 * Fixes for the two known XSS issues of Bootstrap 3.4.1, which no 3.x
 * release fixes. Load right after bootstrap(.min).js; the rest of Bootstrap
 * stays as it is.
 *
 * - CVE-2024-6485: Button's data-loading-text (and every data-*-text state)
 *   was put into the button as HTML. Now as text; resetting brings back the
 *   button's original content, which is the page's own markup.
 * - CVE-2024-6484: Carousel's [data-slide] / [data-slide-to] took the target
 *   from data-target or href unchecked. Now only a plain "#id" or ".class"
 *   selector is accepted, anything else is ignored.
 */
+function ($) {
  'use strict';

  if (!$ || !$.fn.button || !$.fn.carousel) {
    return;
  }

  var Button = $.fn.button.Constructor;

  Button.prototype.setState = function (state) {
    var d    = 'disabled';
    var $el  = this.$element;
    var isInput = $el.is('input');
    var data = $el.data();

    state += 'Text';

    if (data.resetText == null) {
      $el.data('resetText', isInput ? $el.val() : $el.html());
    }

    // push to event loop to allow forms to submit (as Bootstrap does)
    setTimeout($.proxy(function () {
      var value = data[state] == null ? this.options[state] : data[state];

      if (isInput) {
        $el.val(value);
      } else if (state == 'resetText') {
        $el.html(value);
      } else {
        $el.text(value);
      }

      if (state == 'loadingText') {
        this.isLoading = true;
        $el.addClass(d).attr(d, d).prop(d, true);
      } else if (this.isLoading) {
        this.isLoading = false;
        $el.removeClass(d).removeAttr(d).prop(d, false);
      }
    }, this), 0);
  };

  var safeTarget = /^[#.][A-Za-z_][\w-]*$/;

  var clickHandler = function (e) {
    var $this  = $(this);
    var href   = $this.attr('href');

    if (href) {
      href = href.replace(/.*(?=#[^\s]+$)/, ''); // strip for ie7
    }

    var target = $this.attr('data-target') || href;

    if (!target || !safeTarget.test(target)) {
      return;
    }

    var $target = $(document).find(target);

    if (!$target.hasClass('carousel')) {
      return;
    }

    var options = $.extend({}, $target.data(), $this.data());
    var slideIndex = $this.attr('data-slide-to');

    if (slideIndex) {
      options.interval = false;
    }

    $target.carousel(options);

    if (slideIndex) {
      $target.data('bs.carousel').to(slideIndex);
    }

    e.preventDefault();
  };

  $(document)
    .off('click.bs.carousel.data-api')
    .on('click.bs.carousel.data-api', '[data-slide]', clickHandler)
    .on('click.bs.carousel.data-api', '[data-slide-to]', clickHandler);
}(window.jQuery);

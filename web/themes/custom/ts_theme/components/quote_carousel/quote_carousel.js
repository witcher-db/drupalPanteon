(function (Drupal, once) {
  Drupal.behaviors.tsSwiper = {
    attach: function (context, settings) {
      once('tsSwiper', '.js-quote-carousel', context).forEach(function (el) {
        if (!(el instanceof HTMLElement)) return;

        new Swiper(el, {
          loop: false,
          navigation: {
            nextEl: el.querySelector('.swiper-button-next'),
            prevEl: el.querySelector('.swiper-button-prev'),
          },
          breakpoints: {
            650: {
              loop: true,
            },
          },
        });
      });
    }
  };

  Drupal.behaviors.starsField = {
    attach: function (context, settings) {
      once('stars-field', '.field--name-field-quantity-of-stars', context).forEach(function (el) {
        if (!(el instanceof HTMLElement)) return;

        let number = parseInt(el.textContent.trim(), 10);

        if (!isNaN(number) && number > 0) {
          el.textContent = '★'.repeat(number);
        }

        el.style.setProperty('display', 'block', 'important');
      });
    }
  };

})(Drupal, once);

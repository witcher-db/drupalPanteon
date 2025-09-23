(function (Drupal) {
  Drupal.behaviors.tsSwiper = {
    attach: function (context, settings) {
      new Swiper('.QuoteLayoutCarousel', {
        loop: true,
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },
      });
    }
  };
})(Drupal);
(function (Drupal) {
  Drupal.behaviors.starsField = {
    attach: function (context) {
      once('starsField', '.field--name-field-quantity-of-stars', context).forEach((el) => {
        const number = parseInt(el.textContent.trim(), 10);

        if (!isNaN(number) && number > 0) {
          el.textContent = '★'.repeat(number);
        }

        el.style.setProperty('display', 'block', 'important');
      });
    }
  };
})(Drupal);


((Drupal, once) => {
  Drupal.behaviors.statisticBlock = {
    attach: (context, settings) => {
      once('statisticBlock', '.statistic-block-layout', context)
        .forEach((element) => {
          const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.9
          };

          const callback = (entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                element.style.opacity = 1;
              } else {
                element.style.opacity = 0;
              }
            });
          };

          const observer = new IntersectionObserver(callback, options);
          observer.observe(element);
      });
    }
  };
})(Drupal, once);

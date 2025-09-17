
((Drupal, once) => {
  Drupal.behaviors.statisticBlock = {
    attach: (context, settings) => {
      once('statisticBlock', '.statistic-block-layout', context)
        .forEach((element) => {
          let block;

          const options = {
            root: null,
            rootMargin: '0px',
            threshold: 0.9
          };

          const callback = (entries, observer) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                block.style.opacity = 1;
              } else {
                block.style.opacity = 0;
              }
            });
          };

          block = element;
          const observer = new IntersectionObserver(callback, options);
          observer.observe(block);
      });
    }
  };
})(Drupal, once);


((Drupal, once) => {
  Drupal.behaviors.rollingWords = {
    attach: (context, settings) => {
      once('rollingWords', '.field--name-field-text-slider', context)
        .forEach((element) => {
          let text = element.innerText;
          for (let i = 0; i < 4; i++) {
            element.innerText += text;
          }
      });
    }
  };
})(Drupal, once);

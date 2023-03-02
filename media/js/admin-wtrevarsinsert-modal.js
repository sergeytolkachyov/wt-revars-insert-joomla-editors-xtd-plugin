/**
 * @copyright  (C) 2023 Sergey Tolkachyov. <https://web-tolk.ru>
 * @license    GNU General Public License version 2 or later
 */
(() => {
  document.addEventListener('DOMContentLoaded', () => {
    // Get the elements
    const elements = document.querySelectorAll('.WtRevarsInsertBtn');

    for (let i = 0, l = elements.length; l > i; i += 1) {
      // Listen for click event
      elements[i].addEventListener('click', event => {
        event.preventDefault();
        const {
          target
        } = event;

        const revars_variable_index = target.getAttribute('data-wtrevars-variable');

        if (!Joomla.getOptions('xtd-wtrevarsinsert')) {
          // Something went wrong!
          // @TODO Close the modal
          return false;
        }

        const {
          editor
        } = Joomla.getOptions('xtd-wtrevarsinsert');

        let revars_variables  = Joomla.getOptions('wt_revars_insert');

        window.parent.Joomla.editors.instances[editor].replaceSelection(revars_variables[revars_variable_index]);

        if (window.parent.Joomla.Modal) {
          window.parent.Joomla.Modal.getCurrent().close();
        }
      });
    }
  });
})();

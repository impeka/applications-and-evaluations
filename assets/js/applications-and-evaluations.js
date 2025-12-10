document.addEventListener("DOMContentLoaded", function() {
    application_form.init();
});

const application_form = {
    init : function() {
        this.nav_submit_click_init();
        this.save_flag_on_submit_init();
    },
    nav_submit_click_init : function() {
        const submitEl = document.querySelector('[data-disabled="1"]');

        if( submitEl !== null ) {
            submitEl.addEventListener('click', (event) => {
                event.preventDefault();
                alert( submitEl.dataset.alert );
            });
        }
    },
    save_flag_on_submit_init : function() {
        const forms = document.querySelectorAll('form');

        if( ! forms.length ) {
            return;
        }

        forms.forEach((formEl) => {
            let lastSubmitter = null;

            formEl.addEventListener('click', (event) => {
                const target = event.target;
                if( target && target.matches('button[type="submit"], input[type="submit"]') ) {
                    lastSubmitter = target;
                }
            });

            formEl.addEventListener('submit', (event) => {
                const submitter = event.submitter || lastSubmitter;
                let saveFlagEl = formEl.querySelector('[name="save_flag"]');

                if( ! saveFlagEl ) {
                    saveFlagEl = document.createElement('input');
                    saveFlagEl.type = 'hidden';
                    saveFlagEl.name = 'save_flag';
                    saveFlagEl.value = '';
                    formEl.appendChild(saveFlagEl);
                }

                const isSave = submitter && submitter.name === 'save';

                if( saveFlagEl.type === 'checkbox' ) {
                    saveFlagEl.checked = isSave;
                } else {
                    saveFlagEl.value = isSave ? '1' : '';
                }

                if( isSave && typeof acf !== 'undefined' && acf.validation ) {
                    acf.validation.active = false;
                }

                lastSubmitter = null;
            });
        });
    }
};

/* global AFSA */



if (typeof jQuery !== 'undefined')
    jQuery(function ($) {




        try {
            $('body').addClass('afsa_prestahop afsa_theme_main');

            var us = $('#afsa_dashboard');
            us.insertBefore(
                    us.parent().children('section').get(0)
                    );


        } catch (e) {
            console.log(e);
        }



        // If not displaying dashboard
        if (typeof AFSA === 'undefined' || typeof AFSA.Dashboard === 'undefined')
            return;


        AFSA.log('AFSA DB Prestashop Container');

        AFSA.version();

        AFSA.config().set(
                {
                    env: {
                        host: 'prestashop'
                    },
                    dashboard: {
                        parent: '#afsa_container',
                        icon_engine: 'FA4i',
                        do_not_parse: 0,
                        forced_theme: 'main'
                    },
                    ecom: {
                        currency: 'EUR'
                    },
                    ajax: {
                        data_context_enabled: 1,
                        client: 'AFSA:prestashop'
                    }

                }
        ).dump();



        window.setTimeout(function () {

            AFSA.Dashboard()
                    .init({
                        id: 'maindb',
                        calendar: 1,
                        widgets: [

                        ]
                    })
                    .run();
        }, 500);




    }(jQuery));
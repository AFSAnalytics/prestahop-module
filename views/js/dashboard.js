/* global AFSA */

$(function () {



    try {
        $('body').addClass('afsa_prestahop afsa_theme_main');
        $('.page-head').hide();
        $('#subtab-AdminAFSAMenu').click(function (e) {
            e.preventDefault;
            return false;
        });

    } catch (e) {

    }


    AFSA.log('AFSA DB Prestashop Container');


    AFSA.version();



    AFSA.log = console.log;

    AFSA.config().set(
            {
                dashboard: {
                    parent: '#afsa_container',
                    host: 'prestashop',
                    type: 'plugin',
                    icon_engine: 'FA4i',
                    container: {
                        template: 'ecom'
                    }
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



    AFSA.themes.manager()
            .setThemes(['main', 'dark'])
            .load()
            ;


    window.setTimeout(function () {
        AFSA.dashboard.container().run();
    }, 1);


});
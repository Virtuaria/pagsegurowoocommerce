function getUrlParameter(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
    results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
jQuery(document).ready(function($) {
    $('#woocommerce_virt_pagseguro_environment').on('change', function() {
        $('.woocommerce-save-button').click();
    });

    $('#woocommerce_virt_pagseguro_fee_setup').on('change', function() {
        $('#mainform').append('<input type="hidden" name="fee_setup_updated" value="yes" />');
        $('.woocommerce-save-button').click();
    });

    let connected = $('.forminp-auth > .connected' ).length > 0;
    if ( ( getUrlParameter( 'token' ) != '' && ! connected ) || ( getUrlParameter( 'access_revoked' ) != '' && connected ) ) {
        alert( 'Para efetivar a conexão/desconexão clique em "Salvar Alterações".' );
        $([document.documentElement, document.body]).animate({
            scrollTop: $("#woocommerce_virt_pagseguro_tecvirtuaria").offset().top
        }, 2000);
    }
});
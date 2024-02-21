jQuery(document).ready(function($) {
    $('#copy-barcode').on('click', function(e) {
        e.preventDefault();
        navigator.clipboard.writeText($('.barcode').html());
        alert( 'Código copiado!' );
    });
});
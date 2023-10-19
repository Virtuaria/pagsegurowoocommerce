jQuery(document).ready(function($) {
    $('#copy-qr').on('click', function(e) {
        e.preventDefault();
        navigator.clipboard.writeText($('.pix').html());
        alert( 'Código copiado!' );
    });
});
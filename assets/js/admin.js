jQuery(document).ready(function ($) {

    let frame;

    $('#pcpi-logo-upload').on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Select Agency Logo',
            button: { text: 'Use this logo' },
            multiple: false
        });

        frame.on('select', function () {
            const att = frame.state().get('selection').first().toJSON();
            const url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;

            $('#pcpi_logo_id').val(att.id);
            $('#pcpi-logo-preview')
                .attr('src', url)
                .removeClass('pcpi-hidden');

            $('#pcpi-logo-remove').show();
        });

        frame.open();
    });

    $('#pcpi-logo-remove').on('click', function (e) {
        e.preventDefault();

        $('#pcpi_logo_id').val('');
        $('#pcpi-logo-preview')
            .attr('src', '')
            .addClass('pcpi-hidden');

        $(this).hide();
    });

    $('#pcpi_phone').on('input', function () {
    let val = $(this).val().replace(/\D/g, '');

    if (val.length > 10) val = val.substring(0, 10);

    let formatted = val;

    if (val.length >= 6) {
        formatted = `(${val.substring(0,3)}) ${val.substring(3,6)}-${val.substring(6)}`;
    } else if (val.length >= 3) {
        formatted = `(${val.substring(0,3)}) ${val.substring(3)}`;
    }

    $(this).val(formatted);
});

});
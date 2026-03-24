jQuery(function($) {

    function openModal(id) {
        $('#' + id).fadeIn(150);
    }

    function closeModal() {
        $('.sr-modal').fadeOut(150);

        // Reset formularzy + wyczyszczenie "id"
        $('.sr-modal form').each(function() {
            this.reset();
            $(this).find('input[name="id"]').val('');
        });
    }

    $('.sr-open-modal').on('click', function(e) {
        e.preventDefault();
        var modalId = $(this).data('modal');
        openModal(modalId);
    });

    $('.sr-close-modal').on('click', function(e) {
        e.preventDefault();
        closeModal();
    });

    $(document).on('click', '.sr-modal', function(e) {
        if ($(e.target).hasClass('sr-modal')) {
            closeModal();
        }
    });

    // Edycja – cennik (RADIO / TV)
    $('.sr-edit-cennik').on('click', function(e) {
        e.preventDefault();

        var $btn    = $(this);
        var modalId = $btn.data('modal');
        var $modal  = $('#' + modalId);

        $modal.find('input[name="id"]').val($btn.data('id'));
        $modal.find('input[name="godzina"]').val($btn.data('godzina'));
        $modal.find('input[name="cena"]').val($btn.data('cena'));
        $modal.find('input[name="cena_weekend"]').val($btn.data('cena_weekend'));

        if ($btn.data('kanal') === 'radio') {
            var start = $btn.data('start');
            $modal.find('input[name="start_reklamy"][value="' + start + '"]').prop('checked', true);
        }

        $modal.find('input[name="aktywna"]').prop(
            'checked',
            $btn.data('aktywna') === 1 || $btn.data('aktywna') === '1'
        );

        openModal(modalId);
    });

    // Edycja – przelicznik czasu
    $('.sr-edit-przelicznik').on('click', function(e) {
        e.preventDefault();

        var $btn    = $(this);
        var modalId = $btn.data('modal');
        var $modal  = $('#' + modalId);

        $modal.find('input[name="id"]').val($btn.data('id'));
        $modal.find('input[name="dlugosc"]').val($btn.data('dlugosc'));
        $modal.find('input[name="mnoznik"]').val($btn.data('mnoznik'));

        openModal(modalId);
    });

    // Edycja – przedmiot działalności
    $('.sr-edit-przedmiot').on('click', function(e) {
        e.preventDefault();

        var $btn    = $(this);
        var modalId = $btn.data('modal');
        var $modal  = $('#' + modalId);

        $modal.find('input[name="id"]').val($btn.data('id'));
        $modal.find('input[name="nazwa"]').val($btn.data('nazwa'));
        $modal.find('input[name="aktywna"]').prop(
            'checked',
            $btn.data('aktywna') === 1 || $btn.data('aktywna') === '1'
        );

        openModal(modalId);
    });
});
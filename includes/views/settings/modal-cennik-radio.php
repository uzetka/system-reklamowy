<div id="sr-modal-overlay"></div>

<div id="sr-modal-cennik-radio">
    <h3 id="sr-modal-cr-title">Dodaj godzinę RADIO</h3>

    <form method="post" id="sr-modal-cr-form">
        <?php wp_nonce_field('sr_settings_front', 'sr_settings_front_nonce'); ?>
        <input type="hidden" name="action" value="sr_save_cennik_radio">
        <input type="hidden" name="id" id="sr-modal-cr-id">

        <label>Godzina</label>
        <input type="time" name="godzina" id="sr-modal-cr-godzina">

        <label>Cena</label>
        <input type="text" name="cena" id="sr-modal-cr-cena">

        <label>Cena weekend</label>
        <input type="text" name="cena_weekend" id="sr-modal-cr-cena-weekend">

        <label>Start reklamy</label>
        <div>
            <input type="radio" name="start_reklamy" value=">>" checked> >>
            <input type="radio" name="start_reklamy" value="<<"> <<
        </div>

        <label>
            <input type="checkbox" name="aktywna" id="sr-modal-cr-aktywna" checked>
            Aktywna
        </label>

        <div class="sr-modal-actions">
            <button type="button" id="sr-modal-cr-cancel">Anuluj</button>
            <button type="submit">Zapisz</button>
        </div>
    </form>

    <form method="post" id="sr-modal-cr-del-form">
        <?php wp_nonce_field('sr_settings_front', 'sr_settings_front_nonce'); ?>
        <input type="hidden" name="action" value="sr_delete_cennik_radio">
        <input type="hidden" name="id" id="sr-modal-cr-del-id">

        <p>Czy na pewno usunąć?</p>

        <div class="sr-modal-actions">
            <button type="button" id="sr-modal-cr-del-cancel">Anuluj</button>
            <button type="submit">Usuń</button>
        </div>
    </form>
</div>
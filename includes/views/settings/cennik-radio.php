<?php
global $wpdb;

$rows = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}sr_cennik
    WHERE kanal = 'radio'
    ORDER BY godzina ASC
");
?>

<div class="sr-card">
    <div class="sr-card-head">
        <h3>Cennik emisji reklam RADIO</h3>
        <button class="sr-btn-add-cennik-radio">+ Dodaj</button>
    </div>

    <table class="sr-table">
        <thead>
        <tr>
            <th>Godzina</th>
            <th>Cena</th>
            <th>Cena weekend</th>
            <th>Start</th>
            <th>Aktywna</th>
            <th>Akcje</th>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr
                data-id="<?= esc_attr($r->id) ?>"
                data-godzina="<?= esc_attr($r->godzina) ?>"
                data-cena="<?= esc_attr($r->cena) ?>"
                data-cena-weekend="<?= esc_attr($r->cena_weekend) ?>"
                data-start="<?= esc_attr($r->start_reklamy) ?>"
                data-aktywna="<?= esc_attr($r->aktywna) ?>"
            >
                <td><?= esc_html($r->godzina) ?></td>
                <td><?= number_format($r->cena, 2, ',', ' ') ?> zł</td>
                <td><?= number_format($r->cena_weekend, 2, ',', ' ') ?> zł</td>
                <td><?= esc_html($r->start_reklamy) ?></td>
                <td><?= $r->aktywna ? 'ON' : 'OFF' ?></td>
                <td>
                    <button class="sr-btn-edit-cennik-radio">Edytuj</button>
                    <button class="sr-btn-del-cennik-radio">Usuń</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php include SR_PLUGIN_DIR . 'includes/views/settings/modal-cennik-radio.php'; ?>
</div>
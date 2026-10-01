(function($) {
    'use strict';

    $(document).ready(function() {


        $('#studio-open-privacy-modal').on('click', function(){ $('#studio-privacy-modal').css('display','flex').hide().fadeIn(120); });
        $('#studio-close-privacy-modal, #studio-privacy-modal').on('click', function(e){ if (e.target === this) $('#studio-privacy-modal').fadeOut(120); });
        $('.studio-modal-card').on('click', function(e){ e.stopPropagation(); });

        // 1. Calcolo automatico dati anagrafici dal Codice Fiscale
        const $cfInput = $('#studio_codice_fiscale');
        if ($cfInput.length) {
            $cfInput.on('input change blur', function() {
                const cf = $(this).val().trim().toUpperCase();
                if (cf.length === 16) {
                    $('#cf_decode_indicator').text(studioParams.i18n.calculating).show();

                    $.post(studioParams.ajaxUrl, {
                        action: 'studio_decode_cf',
                        nonce: studioParams.nonce,
                        cf: cf
                    }, function(res) {
                        $('#cf_decode_indicator').hide();
                        if (res.success && res.data) {
                            if (!$('#studio_data_nascita').val()) {
                                $('#studio_data_nascita').val(res.data.data_nascita);
                            }
                            if (!$('#studio_sesso').val()) {
                                $('#studio_sesso').val(res.data.sesso);
                            }
                        }
                    });
                }
            });
        }

        // 2. Tab Navigation nella scheda Paziente
        $('.studio-tab-btn').on('click', function(e) {
            e.preventDefault();
            const target = $(this).data('tab');
            $('.studio-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.studio-tab-content').removeClass('active');
            $('#' + target).addClass('active');
        });

        // 3. Salvataggio Rapido Anamnesi via AJAX
        $('#studio_btn_save_anamnesi').on('click', function() {
            const patientId = $(this).data('patient-id');
            const anamnesi = $('#studio_patient_anamnesi').val();
            const $msg = $('#studio_anamnesi_msg');

            $msg.text('Salvataggio in corso...').css('color', '#2563eb');

            $.post(studioParams.ajaxUrl, {
                action: 'studio_save_patient_anamnesi',
                nonce: studioParams.nonce,
                patient_id: patientId,
                anamnesi: anamnesi
            }, function(res) {
                if (res.success) {
                    $msg.text(res.data.message).css('color', '#16a34a');
                    setTimeout(() => $msg.text(''), 3000);
                } else {
                    $msg.text(res.data.message || 'Errore durante il salvataggio').css('color', '#dc2626');
                }
            });
        });

        // 4. Aggiunta Rapida Visita / Seduta via AJAX
        $('#studio_form_add_visita').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const data = $form.serialize() + '&action=studio_add_visita&nonce=' + studioParams.nonce;

            $.post(studioParams.ajaxUrl, data, function(res) {
                if (res.success) {
                    const v = res.data.visita;
                    const newRow = `
                        <tr id="visita-row-${v.id}">
                            <td><strong>${v.data_visita}</strong></td>
                            <td>${v.tipo}</td>
                            <td>${v.durata} min</td>
                            <td>${v.note}</td>
                            <td>
                                <button type="button" class="btn-studio btn-studio-danger btn-delete-visita" data-id="${v.id}" style="padding: 2px 6px; font-size: 11px;">Elimina</button>
                            </td>
                        </tr>
                    `;
                    $('#visite_table_body').prepend(newRow);
                    $('#visite_empty_row').hide();
                    $form[0].reset();
                } else {
                    alert(res.data.message || 'Errore');
                }
            });
        });

        // Eliminazione Visita
        $(document).on('click', '.btn-delete-visita', function() {
            if (!confirm(studioParams.i18n.confirmDelete)) return;
            const $btn = $(this);
            const id = $btn.data('id');

            $.post(studioParams.ajaxUrl, {
                action: 'studio_delete_visita',
                nonce: studioParams.nonce,
                visita_id: id
            }, function(res) {
                if (res.success) {
                    $('#visita-row-' + id).fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert(res.data.message || 'Errore');
                }
            });
        });

        // 5. Calcoli Fattura in tempo reale
        function ricalcolaTotaliFattura() {
            let imponibileTotale = 0;

            $('.invoice-item-row').each(function() {
                const q = parseFloat($(this).find('.item-qta').val()) || 0;
                const p = parseFloat($(this).find('.item-prezzo').val()) || 0;
                const sc = parseFloat($(this).find('.item-sconto').val()) || 0;

                let rowTot = q * p;
                if (sc !== 0) {
                    rowTot = rowTot - (rowTot * (sc / 100));
                }
                $(this).find('.item-totale-riga').text(rowTot.toFixed(2) + ' €');
                imponibileTotale += rowTot;
            });

            const cassaPerc = parseFloat($('#fattura_cassa_perc').val()) || 0;
            const ivaPerc = parseFloat($('#fattura_iva_perc').val()) || 0;
            const ritenutaPerc = parseFloat($('#fattura_ritenuta_perc').val()) || 0;
            const bolloValore = parseFloat($('#fattura_bollo_valore').val()) || 2.00;
            const bolloSoglia = parseFloat($('#fattura_bollo_soglia').val()) || 77.47;
            const forzaBollo = $('#fattura_applica_marca_bollo').is(':checked');

            const totaleCassa = (imponibileTotale * (cassaPerc / 100));
            const imponibileIvabile = imponibileTotale + totaleCassa;
            const totaleIva = (imponibileIvabile * (ivaPerc / 100));
            const totaleRitenuta = (imponibileTotale * (ritenutaPerc / 100));

            let totaleBollo = 0;
            if (forzaBollo) {
                totaleBollo = bolloValore;
            }

            const totaleFinale = (imponibileTotale + totaleCassa + totaleIva + totaleBollo) - totaleRitenuta;

            $('#display_totale_imponibile').text(imponibileTotale.toFixed(2) + ' €');
            $('#display_totale_cassa').text(totaleCassa.toFixed(2) + ' €');
            $('#display_totale_iva').text(totaleIva.toFixed(2) + ' €');
            $('#display_marca_bollo').text(totaleBollo.toFixed(2) + ' €');
            $('#display_totale_ritenuta').text('-' + totaleRitenuta.toFixed(2) + ' €');
            $('#display_totale_documento').text(Math.max(0, totaleFinale).toFixed(2) + ' €');
        }

        // Listener modifiche riga fattura
        $(document).on('input change', '.item-qta, .item-prezzo, .item-sconto, #fattura_cassa_perc, #fattura_iva_perc, #fattura_ritenuta_perc, #fattura_applica_marca_bollo', function() {
            ricalcolaTotaliFattura();
        });

        // Aggiungi Riga Fattura
        $('#btn_add_invoice_row').on('click', function() {
            const index = $('.invoice-item-row').length;
            const presetHtml = $('#preset_prestazioni_options').html();

            const row = `
                <tr class="invoice-item-row">
                    <td>
                        <input type="text" name="riga_descrizione[]" class="widefat item-desc" placeholder="Descrizione prestazione..." list="prestazioni_list" required>
                    </td>
                    <td>
                        <input type="number" step="0.5" min="1" name="riga_quantita[]" value="1" class="item-qta" style="width: 70px;">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" name="riga_prezzo[]" value="0.00" class="item-prezzo" style="width: 100px;">
                    </td>
                    <td>
                        <input type="number" step="1" name="riga_sconto[]" value="0" class="item-sconto" style="width: 70px;">
                    </td>
                    <td>
                        <span class="item-totale-riga" style="font-weight: 700;">0.00 €</span>
                    </td>
                    <td>
                        <button type="button" class="btn-studio btn-studio-danger btn-remove-row" style="padding: 2px 6px;">&times;</button>
                    </td>
                </tr>
            `;
            $('#invoice_items_tbody').append(row);
            ricalcolaTotaliFattura();
        });

        // Rimuovi Riga Fattura
        $(document).on('click', '.btn-remove-row', function() {
            if ($('.invoice-item-row').length > 1) {
                $(this).closest('tr').remove();
                ricalcolaTotaliFattura();
            } else {
                alert('La fattura deve avere almeno una riga prestazione.');
            }
        });

        // Compilazione rapida quando si sceglie una prestazione predefinita
        $(document).on('change', '.item-desc', function() {
            const val = $(this).val();
            const $row = $(this).closest('tr');
            $('#prestazioni_list option').each(function() {
                if ($(this).val() === val) {
                    const price = $(this).data('prezzo');
                    if (price) {
                        $row.find('.item-prezzo').val(price);
                        ricalcolaTotaliFattura();
                    }
                }
            });
        });

        // Conferma emissione
        $('.btn-confirm-issue').on('click', function(e) {
            if (!confirm(studioParams.i18n.confirmIssue)) {
                e.preventDefault();
            }
        });

        // Sezione Commercialista: Toggle Date Custom
        $('#export_periodo').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom_dates_wrapper').show();
            } else {
                $('#custom_dates_wrapper').hide();
            }
        });


        $(document).on('click', 'button[name="studio_export_accountant_email"]', function(e) {
            if (!confirm('Confermi la generazione e l’invio del report PDF al commercialista?')) { e.preventDefault(); }
        });

        // Inizializza calcoli al caricamento se nella pagina form fattura
        if ($('.invoice-items-table').length) {
            ricalcolaTotaliFattura();
        }

    });

})(jQuery);

/* Responsive enhancement v2.2.7: visual wrappers only */
jQuery(function($){
    $('.studio-wrap table').each(function(){
        var $table=$(this);
        if(!$table.parent().hasClass('studio-table-scroll')){
            $table.wrap('<div class="studio-table-scroll" role="region" aria-label="Tabella scorrevole" tabindex="0"></div>');
            $table.parent().before('<p class="studio-mobile-hint">Scorri orizzontalmente per visualizzare tutte le colonne.</p>');
        }
    });
});

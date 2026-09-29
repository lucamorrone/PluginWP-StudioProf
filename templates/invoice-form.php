<?php
if (!defined('ABSPATH')) exit;
$is_edit = !empty($invoice);
$selected_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : ($is_edit ? $invoice->paziente_id : 0);
$is_locked = ($is_edit && $invoice->stato === 'emessa');
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>
                <?php 
                if ($is_locked) {
                    echo 'Modifica Stato Pagamento Fattura N. ' . esc_html($invoice->codice_fattura);
                } elseif ($is_edit) {
                    echo 'Modifica Bozza Fattura #' . esc_html($invoice->id);
                } else {
                    echo 'Nuova Fattura Sanitaria';
                }
                ?>
            </h1>
            <p class="description">Compilazione assistita del documento fiscale con calcolo automatico cassa di previdenza, IVA e bollo.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture')); ?>" class="btn-studio btn-studio-secondary">&larr; Torna alle Fatture</a>
        </div>
    </div>

    <?php if ($is_locked): ?>
        <div class="notice notice-warning" style="margin-left:0; margin-bottom: 20px;">
            <p><strong>Fattura già emessa con numero definitivo:</strong> Per legge, gli importi, le prestazioni e i dati fiscali non possono più essere modificati. Puoi aggiornare la data, il metodo di pagamento e le note.</p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('studio_save_invoice', 'studio_save_invoice_nonce'); ?>
        <input type="hidden" name="invoice_id" value="<?php echo $is_edit ? esc_attr($invoice->id) : 0; ?>">

        <!-- Parametri nascosti per calcolo JS -->
        <input type="hidden" id="fattura_bollo_valore" value="<?php echo esc_attr($studio['marca_bollo']); ?>">
        <input type="hidden" id="fattura_bollo_soglia" value="<?php echo esc_attr($studio['marca_bollo_soglia']); ?>">

        <!-- Intestazione Documento -->
        <div class="studio-panel">
            <h2>Dati Testata Documento</h2>
            <div class="studio-form-grid">
                <div class="studio-field" style="grid-column: span 2;">
                    <label>Seleziona Paziente *</label>
                    <select name="paziente_id" required <?php disabled($is_locked); ?>>
                        <option value="">-- Seleziona un paziente --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo esc_attr($p->id); ?>" <?php selected($selected_patient, $p->id); ?>>
                                <?php echo esc_html($p->cognome . ' ' . $p->nome . ' (' . $p->codice_fiscale . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="studio-field"><label>Tipo fattura</label><select name="tipo_documento" <?php disabled($is_locked);?>><option value="sanitaria" <?php selected($invoice->tipo_documento??'sanitaria','sanitaria');?>>Sanitaria</option><option value="non_sanitaria" <?php selected($invoice->tipo_documento??'','non_sanitaria');?>>Non sanitaria</option></select></div>
                <div class="studio-field">
                    <label>Data Documento *</label>
                    <input type="date" name="data_documento" value="<?php echo $is_edit ? esc_attr($invoice->data_documento) : date('Y-m-d'); ?>" required <?php disabled($is_locked); ?>>
                </div>
                <div class="studio-field">
                    <label>Valuta</label>
                    <input type="text" value="EUR (€)" readonly disabled>
                </div>
            </div>
        </div>

        <!-- Righe Prestazioni -->
        <div class="studio-panel">
            <h2>Prestazioni Sanitarie & Voci di Tariffa</h2>
            <p class="description">Seleziona o digita il tipo di seduta. Importi e totali si aggiornano in tempo reale.</p>

            <!-- Datalist per autocompletamento prestazioni -->
            <datalist id="prestazioni_list">
                <?php foreach ($prestazioni as $pr): ?>
                    <option value="<?php echo esc_attr($pr['nome']); ?>" data-prezzo="<?php echo esc_attr($pr['prezzo']); ?>"><?php echo esc_html($pr['nome'] . ' - ' . number_format($pr['prezzo'], 2) . ' €'); ?></option>
                <?php endforeach; ?>
            </datalist>

            <table class="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Descrizione Prestazione</th>
                        <th style="width: 10%;">Quantità</th>
                        <th style="width: 15%;">Prezzo Unitario (&euro;)</th>
                        <th style="width: 10%;">Sconto (%)</th>
                        <th style="width: 10%;">Totale Riga</th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody id="invoice_items_tbody">
                    <?php if (!empty($righe)): ?>
                        <?php foreach ($righe as $r): ?>
                            <tr class="invoice-item-row">
                                <td>
                                    <input type="text" name="riga_descrizione[]" class="widefat item-desc" value="<?php echo esc_attr($r->descrizione); ?>" list="prestazioni_list" required <?php disabled($is_locked); ?>>
                                </td>
                                <td>
                                    <input type="number" step="0.5" min="1" name="riga_quantita[]" value="<?php echo esc_attr($r->quantita); ?>" class="item-qta" style="width: 70px;" <?php disabled($is_locked); ?>>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="riga_prezzo[]" value="<?php echo esc_attr($r->prezzo_unitario); ?>" class="item-prezzo" style="width: 100px;" <?php disabled($is_locked); ?>>
                                </td>
                                <td>
                                    <input type="number" step="1" name="riga_sconto[]" value="<?php echo esc_attr($r->sconto_maggiorazione_perc); ?>" class="item-sconto" style="width: 70px;" <?php disabled($is_locked); ?>>
                                </td>
                                <td>
                                    <span class="item-totale-riga" style="font-weight: 700;"><?php echo number_format($r->totale_riga, 2); ?> &euro;</span>
                                </td>
                                <td>
                                    <?php if (!$is_locked): ?>
                                        <button type="button" class="btn-studio btn-studio-danger btn-remove-row" style="padding: 2px 6px;">&times;</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Riga iniziale predefinita -->
                        <tr class="invoice-item-row">
                            <td>
                                <input type="text" name="riga_descrizione[]" class="widefat item-desc" value="Colloquio clinico / Seduta psicoterapia" list="prestazioni_list" required>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="1" name="riga_quantita[]" value="1" class="item-qta" style="width: 70px;">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="riga_prezzo[]" value="70.00" class="item-prezzo" style="width: 100px;">
                            </td>
                            <td>
                                <input type="number" step="1" name="riga_sconto[]" value="0" class="item-sconto" style="width: 70px;">
                            </td>
                            <td>
                                <span class="item-totale-riga" style="font-weight: 700;">70.00 &euro;</span>
                            </td>
                            <td>
                                <button type="button" class="btn-studio btn-studio-danger btn-remove-row" style="padding: 2px 6px;">&times;</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!$is_locked): ?>
                <button type="button" class="btn-studio btn-studio-secondary" id="btn_add_invoice_row">+ Aggiungi Riga Prestazione</button>
            <?php endif; ?>

            <!-- Calcolo e Parametri Fiscali -->
            <div style="display: flex; justify-content: space-between; margin-top: 30px; gap: 30px;">
                <div style="flex: 1; max-width: 450px;">
                    <h3>Aliquote e Parametri Documento</h3>
                    <div class="studio-form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="studio-field">
                            <label>Cassa Previdenza (%):</label>
                            <input type="number" step="0.01" name="percentuale_cassa" id="fattura_cassa_perc" value="<?php echo $is_edit ? esc_attr($invoice->percentuale_cassa) : esc_attr($studio['cassa_perc']); ?>" <?php disabled($is_locked); ?>>
                        </div>
                        <div class="studio-field">
                            <label>IVA (%):</label>
                            <input type="number" step="0.01" name="percentuale_iva" id="fattura_iva_perc" value="<?php echo $is_edit ? esc_attr($invoice->percentuale_iva) : '0.00'; ?>" <?php disabled($is_locked); ?>>
                            <span class="desc">0% per prestazioni sanitarie</span>
                        </div>
                        <div class="studio-field">
                            <label>Ritenuta d'acconto (%):</label>
                            <input type="number" step="0.01" name="percentuale_ritenuta" id="fattura_ritenuta_perc" value="<?php echo $is_edit ? esc_attr($invoice->percentuale_ritenuta) : esc_attr($studio['ritenuta_perc']); ?>" <?php disabled($is_locked); ?>>
                        </div>
                        <div class="studio-field" style="display: flex; align-items: flex-start; justify-content: center; padding-top: 20px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" name="applica_marca_bollo" id="fattura_applica_bollo" value="1" <?php checked($is_edit ? ($invoice->marca_bollo > 0) : true); ?> <?php disabled($is_locked); ?>>
                                <span>Marca da Bollo (2 &euro;)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="invoice-totals-box">
                    <div class="row-tot">
                        <span>Imponibile Prestazioni:</span>
                        <strong id="display_totale_imponibile">0.00 &euro;</strong>
                    </div>
                    <div class="row-tot">
                        <span>Contributo Cassa Previdenza:</span>
                        <span id="display_totale_cassa">0.00 &euro;</span>
                    </div>
                    <div class="row-tot">
                        <span>IVA:</span>
                        <span id="display_totale_iva">0.00 &euro;</span>
                    </div>
                    <div class="row-tot">
                        <span>Imposta di Bollo:</span>
                        <span id="display_marca_bollo">0.00 &euro;</span>
                    </div>
                    <div class="row-tot">
                        <span>Ritenuta d'acconto:</span>
                        <span id="display_totale_ritenuta">-0.00 &euro;</span>
                    </div>
                    <div class="row-tot grand">
                        <span>TOTALE FATTURA:</span>
                        <span id="display_totale_documento">0.00 &euro;</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagamento e Note -->
        <div class="studio-panel">
            <h2>Gestione Pagamento</h2>
            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Modalità di Pagamento:</label>
                    <select name="metodo_pagamento">
                        <option value="Bonifico" <?php selected($is_edit ? $invoice->metodo_pagamento : '', 'Bonifico'); ?>>Bonifico Bancario</option>
                        <option value="Carta" <?php selected($is_edit ? $invoice->metodo_pagamento : '', 'Carta'); ?>>Carta di Credito / Bancomat / POS</option>
                        <option value="Contanti" <?php selected($is_edit ? $invoice->metodo_pagamento : '', 'Contanti'); ?>>Contanti</option>
                    </select>
                </div>
                <div class="studio-field">
                    <label>Data Avvenuto Pagamento:</label>
                    <input type="date" name="data_pagamento" id="fattura_data_pagamento" value="<?php echo ($is_edit && $invoice->data_pagamento) ? esc_attr($invoice->data_pagamento) : ''; ?>">
                    <span class="desc">Lascia vuoto se il documento non è stato ancora saldato. Verrà etichettato come "DA PAGARE".</span>
                </div>
            </div>

            <div class="studio-field" style="margin-top: 15px;">
                <label>Note Interne o Testo Personalizzato nel Documento:</label>
                <textarea name="note_documento" rows="3"><?php echo $is_edit ? esc_textarea($invoice->note_documento) : ''; ?></textarea>
            </div>
        </div>

        <!-- Pulsanti Azione -->
        <div style="display: flex; gap: 15px; margin-top: 25px;">
            <?php if (!$is_locked): ?>
                <input type="submit" name="submit_draft" class="btn-studio btn-studio-secondary" value="Salva come Bozza / Proforma" style="padding: 10px 20px;">
                <input type="submit" name="submit_and_issue" class="btn-studio btn-studio-success btn-confirm-issue" value="&#10004; Emetti Fattura Definitiva" style="padding: 10px 24px;">
            <?php else: ?>
                <input type="submit" name="submit_update_payment" class="btn-studio btn-studio-primary" value="Aggiorna Dati Pagamento & Rigenera PDF" style="padding: 10px 24px;">
            <?php endif; ?>
        </div>
    </form>
</div>

<?php
if (!defined('ABSPATH')) exit;
$privacy_url = wp_nonce_url(admin_url('admin.php?page=studio-pazienti&action=studio_privacy_pdf&id=' . $patient->id), 'studio_privacy_pdf_' . $patient->id);
$delete_url = wp_nonce_url(admin_url('admin.php?page=studio-pazienti&action=delete&id=' . $patient->id), 'studio_delete_patient_' . $patient->id);
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Scheda Paziente: <?php echo esc_html($patient->cognome . ' ' . $patient->nome); ?></h1>
            <p class="description">Cartella clinica, storico sedute e situazione contabile.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url($privacy_url); ?>" target="_blank" class="btn-studio btn-studio-secondary">&#128462; Consenso Privacy PDF</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=new&patient_id=' . $patient->id)); ?>" class="btn-studio btn-studio-success">+ Nuova Fattura</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=edit&id=' . $patient->id)); ?>" class="btn-studio btn-studio-primary">&#9998; Modifica Anagrafica</a>
        </div>
    </div>

    <!-- Dati Anagrafici Card -->
    <div class="studio-panel" style="background: #f8fafc;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Codice Fiscale</span>
                <div style="font-size: 15px; font-weight: 700; font-family: monospace;"><?php echo esc_html($patient->codice_fiscale); ?></div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Data e Luogo Nascita</span>
                <div style="font-size: 14px; font-weight: 600;">
                    <?php echo $patient->data_nascita ? date('d/m/Y', strtotime($patient->data_nascita)) : '-'; ?> 
                    <?php if ($patient->luogo_nascita): ?>(<?php echo esc_html($patient->luogo_nascita . ' ' . $patient->provincia_nascita); ?>)<?php endif; ?>
                </div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Recapiti</span>
                <div style="font-size: 14px;">&#128222; <?php echo esc_html($patient->telefono); ?></div>
                <div style="font-size: 13px; color: #475569;">&#9993; <?php echo esc_html($patient->email ?: 'Nessuna email'); ?></div>
            </div>
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Residenza</span>
                <div style="font-size: 13px;">
                    <?php echo esc_html($patient->indirizzo_residenza); ?><br>
                    <?php echo esc_html($patient->cap_residenza . ' ' . $patient->citta_residenza . ' (' . $patient->provincia_residenza . ')'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheda a Tab -->
    <div class="studio-tabs">
        <button class="studio-tab-btn active" data-tab="tab-anamnesi">&#128221; Anamnesi Clinica</button>
        <button class="studio-tab-btn" data-tab="tab-visite">&#128197; Storico Visite / Sedute (<?php echo count($visite); ?>)</button>
        <button class="studio-tab-btn" data-tab="tab-contabilita">&#128179; Contabilità & Fatture (<?php echo count($fatture); ?>)</button>
    </div>

    <!-- TAB 1: ANAMNESI -->
    <div class="studio-tab-content active" id="tab-anamnesi">
        <div class="studio-panel">
            <h2>Anamnesi e Storia Clinica del Paziente</h2>
            <p class="description">Spazio riservato per la formulazione del caso clinico, diagnosi, obiettivi terapeutici e note riservate protette da segreto professionale.</p>
            
            <div class="studio-field" style="margin-top: 15px;">
                <textarea id="studio_patient_anamnesi" rows="12" style="font-family: inherit; font-size: 14px; line-height: 1.6;"><?php echo esc_textarea($patient->anamnesi); ?></textarea>
            </div>

            <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px;">
                <button type="button" class="btn-studio btn-studio-primary" id="studio_btn_save_anamnesi" data-patient-id="<?php echo esc_attr($patient->id); ?>">Salva Anamnesi</button>
                <span id="studio_anamnesi_msg" style="font-weight: 600; font-size: 13px;"></span>
            </div>
        </div>
    </div>

    <!-- TAB 2: VISITE E COLLOQUI -->
    <div class="studio-tab-content" id="tab-visite">
        <div class="studio-panel">
            <h2>Registra Nuova Seduta / Visita</h2>
            <form id="studio_form_add_visita">
                <input type="hidden" name="patient_id" value="<?php echo esc_attr($patient->id); ?>">
                <div class="studio-form-grid">
                    <div class="studio-field">
                        <label>Data e Ora:</label>
                        <input type="datetime-local" name="data_visita" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    <div class="studio-field">
                        <label>Tipo di Seduta:</label>
                        <input type="text" name="tipo_seduta" value="Colloquio clinico" required>
                    </div>
                    <div class="studio-field">
                        <label>Durata (minuti):</label>
                        <input type="number" name="durata_minuti" value="60" min="15" step="5">
                    </div>
                </div>
                <div class="studio-field">
                    <label>Note della seduta / Resoconto:</label>
                    <textarea name="note" rows="3" placeholder="Argomenti trattati, compiti assegnati, evoluzione..."></textarea>
                </div>
                <div style="margin-top: 15px;">
                    <button type="submit" class="btn-studio btn-studio-success">+ Salva Seduta nello Storico</button>
                </div>
            </form>
        </div>

        <div class="studio-panel">
            <h2>Storico Sedute Effettuate</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 20%;">Data e Ora</th>
                        <th style="width: 20%;">Tipologia</th>
                        <th style="width: 10%;">Durata</th>
                        <th style="width: 40%;">Note Seduta</th>
                        <th style="width: 10%;">Azione</th>
                    </tr>
                </thead>
                <tbody id="visite_table_body">
                    <?php if (!empty($visite)): ?>
                        <?php foreach ($visite as $v): ?>
                            <tr id="visita-row-<?php echo esc_attr($v->id); ?>">
                                <td><strong><?php echo date('d/m/Y H:i', strtotime($v->data_visita)); ?></strong></td>
                                <td><?php echo esc_html($v->tipo_seduta); ?></td>
                                <td><?php echo esc_html($v->durata_minuti); ?> min</td>
                                <td><?php echo nl2br(esc_html($v->note)); ?></td>
                                <td>
                                    <button type="button" class="btn-studio btn-studio-danger btn-delete-visita" data-id="<?php echo esc_attr($v->id); ?>" style="padding: 2px 6px; font-size: 11px;">Elimina</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="visite_empty_row"><td colspan="5" style="text-align: center; padding: 15px;">Nessuna seduta registrata fino ad ora.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: CONTABILITA PAZIENTE -->
    <div class="studio-tab-content" id="tab-contabilita">
        <div class="studio-grid-cards">
            <div class="studio-card-stat highlight">
                <div class="title">Totale Fatturato Emesso</div>
                <div class="value"><?php echo number_format($totale_fatturato, 2, ',', '.'); ?> &euro;</div>
            </div>
            <div class="studio-card-stat success">
                <div class="title">Totale Pagato</div>
                <div class="value"><?php echo number_format($totale_pagato, 2, ',', '.'); ?> &euro;</div>
            </div>
            <div class="studio-card-stat <?php echo $saldo_residuo > 0 ? 'warning' : ''; ?>">
                <div class="title">Saldo Residuo (Da Saldare)</div>
                <div class="value"><?php echo number_format($saldo_residuo, 2, ',', '.'); ?> &euro;</div>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Elenco Documenti & Fatture</h2>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Numero Documento</th>
                        <th>Data Documento</th>
                        <th>Totale</th>
                        <th>Stato Fiscale</th>
                        <th>Stato Pagamento</th>
                        <th>Metodo Pagamento</th>
                        <th style="text-align: right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($fatture)): ?>
                        <?php foreach ($fatture as $f): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=view&id=' . $f->id)); ?>" style="font-weight: 700;">
                                        <?php echo esc_html($f->codice_fattura ?: ('Bozza #' . $f->id)); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($f->data_documento)); ?></td>
                                <td><strong><?php echo number_format($f->totale_documento, 2, ',', '.'); ?> &euro;</strong></td>
                                <td>
                                    <?php if ($f->stato === 'emessa'): ?>
                                        <span class="studio-badge badge-issued">Emessa</span>
                                    <?php else: ?>
                                        <span class="studio-badge badge-draft">Bozza</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($f->stato_pagamento === 'pagata'): ?>
                                        <span class="studio-badge badge-paid">Saldato (<?php echo date('d/m/Y', strtotime($f->data_pagamento)); ?>)</span>
                                    <?php else: ?>
                                        <span class="studio-badge badge-unpaid">Da Pagare</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($f->metodo_pagamento ?: '-'); ?></td>
                                <td style="text-align: right;">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=view&id=' . $f->id)); ?>" class="button button-small">&#128065; Dettagli</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 15px;">Nessuna fattura presente per questo paziente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

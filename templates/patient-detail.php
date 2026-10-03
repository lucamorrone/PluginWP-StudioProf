<?php
if (!defined('ABSPATH')) exit;
$privacy_url = wp_nonce_url(admin_url('admin.php?page=studio-pazienti&action=studio_privacy_pdf&id=' . $patient->id), 'studio_privacy_pdf_' . $patient->id);
$privacy_email_url = wp_nonce_url(admin_url('admin.php?page=studio-pazienti&action=studio_privacy_email&id=' . $patient->id), 'studio_privacy_email_' . $patient->id);
$delete_url = wp_nonce_url(admin_url('admin.php?page=studio-pazienti&action=delete&id=' . $patient->id), 'studio_delete_patient_' . $patient->id);
?>
<div class="wrap studio-wrap">
<div id="studio-privacy-modal" class="studio-modal" style="display:none"><div class="studio-modal-card"><h2>Consenso privacy</h2><p>Scegli l’operazione da eseguire per il paziente.</p><div class="studio-actions"><a href="<?php echo esc_url($privacy_url);?>" target="_blank" class="btn-studio btn-studio-secondary">Stampa / Scarica PDF</a><a href="<?php echo esc_url($privacy_email_url);?>" class="btn-studio btn-studio-primary" onclick="return confirm('Inviare il consenso privacy a <?php echo esc_js($patient->email);?>?')">Invia via email</a><button type="button" class="btn-studio btn-studio-secondary" id="studio-close-privacy-modal">Annulla</button></div></div></div>

    <?php if(isset($_GET['privacy_email_sent'])):?><div class="notice notice-success is-dismissible"><p>Consenso privacy inviato con successo e registrato nel log.</p></div><?php endif;?>
    <div class="studio-header">
        <div>
            <h1>Scheda Paziente: <?php echo esc_html($patient->cognome.' '.$patient->nome.($patient_age!==''?' ('.$patient_age.')':'')); ?></h1>
            <p class="description">Cartella clinica, storico sedute e situazione contabile.</p>
        </div>
        <div class="studio-actions">
            <button type="button" class="btn-studio btn-studio-secondary" id="studio-open-privacy-modal">&#128462; Consenso Privacy</button>
            <?php if($can_view_clinical): ?><a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=clinical_profile&id='.$patient->id)); ?>" class="btn-studio btn-studio-primary">🧠 Inserisci / Modifica Profilo Clinico</a><?php endif; ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=new&patient_id=' . $patient->id)); ?>" class="btn-studio btn-studio-success">+ Nuova Fattura</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=edit&id=' . $patient->id)); ?>" class="btn-studio btn-studio-primary">&#9998; Modifica Anagrafica</a>
            <?php if(current_user_can(Studio_Roles::CAP_MANAGE_STUDIO) && $issued_invoice_count===0):?><a href="<?php echo esc_url($delete_url);?>" class="btn-studio btn-studio-danger" onclick="return confirm('Eliminare definitivamente il paziente e le eventuali bozze collegate?')">Elimina paziente</a><?php endif;?>
        </div>
    </div>
    <div class="notice notice-info inline"><p><strong>Consenso privacy:</strong> <?php if(!empty($patient->consenso_privacy_generato)): ?>generato il <?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($patient->consenso_privacy_data))); ?><?php else: ?>non ancora generato<?php endif; ?></p></div>

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
        <?php if($can_view_clinical): ?><button class="studio-tab-btn active" data-tab="tab-anamnesi">&#128221; Anamnesi Clinica</button><?php endif; ?>
        <?php if($can_view_clinical): ?><button class="studio-tab-btn" data-tab="tab-visite">&#128197; Storico Visite / Sedute (<?php echo count($visite); ?>)</button><?php endif; ?>
        <button class="studio-tab-btn <?php echo $can_view_clinical?'':'active'; ?>" data-tab="tab-contabilita">&#128179; Contabilità & Fatture (<?php echo count($fatture); ?>)</button>
    </div>

    <!-- TAB 1: ANAMNESI -->
    <?php if($can_view_clinical): ?><div class="studio-tab-content active" id="tab-anamnesi">
        <div class="studio-panel">
            <?php if ($can_view_clinical) : ?>
            <section class="clinical-profile-overview" aria-labelledby="clinical-profile-overview-title">
                <div class="clinical-profile-overview-header">
                    <h2 id="clinical-profile-overview-title">🧠 Profilo Clinico</h2>
                    <a class="clinical-profile-edit-link" href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=clinical_profile&id=' . $patient->id)); ?>">Modifica profilo</a>
                </div>
                <?php
                $clinical_summary = array();
                foreach ($clinical_categories as $clinical_category) {
                    $category_id = isset($clinical_category['id']) ? $clinical_category['id'] : '';
                    if ($category_id === '' || empty($clinical_profile[$category_id]) || !is_array($clinical_profile[$category_id])) {
                        continue;
                    }
                    $profile_values = array_values(array_filter(array_map('sanitize_text_field', $clinical_profile[$category_id])));
                    if (empty($profile_values)) {
                        continue;
                    }
                    $clinical_summary[] = array(
                        'name'   => isset($clinical_category['name']) ? $clinical_category['name'] : $category_id,
                        'values' => $profile_values,
                    );
                }
                ?>
                <?php if (!empty($clinical_summary)) : ?>
                    <div class="clinical-summary-inline">
                        <?php foreach ($clinical_summary as $clinical_index => $clinical_item) : ?>
                            <?php if ($clinical_index > 0) : ?><span class="clinical-summary-separator" aria-hidden="true"> – </span><?php endif; ?>
                            <span class="clinical-summary-item"><strong><?php echo esc_html($clinical_item['name']); ?>:</strong> <em><?php echo esc_html(implode(', ', $clinical_item['values'])); ?></em></span>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="clinical-summary-empty">Nessun profilo clinico compilato.</p>
                <?php endif; ?>
            </section>
            <?php endif; ?>

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

    <?php endif; ?>

    <?php if($can_view_clinical): ?>
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

    <?php endif; ?>

    <!-- TAB 3: CONTABILITA PAZIENTE -->
    <div class="studio-tab-content <?php echo $can_view_clinical?'':'active'; ?>" id="tab-contabilita">
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

<?php if(current_user_can(Studio_Roles::CAP_MANAGE_DOCUMENTS)): ?>
<div class="studio-panel"><h2>Documenti clinici PDF</h2><form method="post" enctype="multipart/form-data"><?php wp_nonce_field('studio_upload_document_'.$patient->id);?><input type="hidden" name="patient_id" value="<?php echo (int)$patient->id;?>"><input type="file" name="clinical_document" accept="application/pdf" required> <input name="document_description" placeholder="Descrizione documento"> <button class="button button-primary" name="studio_upload_document">Carica PDF</button></form><table class="widefat striped" style="margin-top:15px"><thead><tr><th>Documento</th><th>Descrizione</th><th>Data</th><th>Operatore</th><th>Azioni</th></tr></thead><tbody><?php foreach($documenti as $d):$down=wp_nonce_url(admin_url('admin.php?page=studio-pazienti&patient_id='.$patient->id.'&document_action=download&document_id='.$d->id),'studio_download_document_'.$d->id);$del=wp_nonce_url(admin_url('admin.php?page=studio-pazienti&patient_id='.$patient->id.'&document_action=delete&document_id='.$d->id),'studio_delete_document_'.$d->id);?><tr><td><?php echo esc_html($d->nome_file);?></td><td><?php echo esc_html($d->descrizione);?></td><td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($d->data_caricamento)));?></td><td><?php echo esc_html($d->display_name);?></td><td><a class="button" href="<?php echo esc_url($down);?>">Scarica</a> <a class="button" href="<?php echo esc_url($del);?>" onclick="return confirm('Eliminare il documento?')">Elimina</a></td></tr><?php endforeach;?></tbody></table></div>
<?php endif; ?>
<div class="studio-panel"><h2>Storico comunicazioni e invii</h2><table class="widefat striped"><thead><tr><th>Data</th><th>Tipo</th><th>Operatore</th><th>Dettagli</th></tr></thead><tbody><?php foreach($email_logs as $log):?><tr><td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($log->data_evento)));?></td><td><?php echo esc_html($log->tipo);?></td><td><?php echo esc_html($log->display_name);?></td><td><?php $details=json_decode($log->dettagli,true);echo esc_html(is_array($details)?implode(' | ',array_map('strval',$details)):$log->dettagli);?></td></tr><?php endforeach;?></tbody></table></div>
</div>

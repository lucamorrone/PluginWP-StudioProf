<?php
if (!defined('ABSPATH')) exit;
$updated = isset($_GET['updated']);
$repaired = isset($_GET['db_repaired']);
$repair_url = wp_nonce_url(admin_url('admin.php?page=studio-impostazioni&action=repair_db'), 'studio_repair_db');
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Anagrafica Studio Professionale & Parametri</h1>
            <p class="description">Configura i dati fiscali, bancari, le aliquote e le prestazioni dello studio che compariranno nelle fatture.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url($repair_url); ?>" class="btn-studio btn-studio-secondary">&#128260; Ripara / Verifica Tabelle DB</a>
        </div>
    </div>

    <?php if ($updated): ?>
        <div class="notice notice-success is-dismissible" style="margin-left: 0;">
            <p><strong>Dati dello studio aggiornati con successo!</strong></p>
        </div>
    <?php endif; ?>

    <?php if ($repaired): ?>
        <div class="notice notice-success is-dismissible" style="margin-left: 0;">
            <p><strong>Verifica e creazione tabelle database completata con successo!</strong></p>
        </div>
    <?php endif; ?>

    <!-- Diagnostica Tabelle Database -->
    <div class="studio-panel" style="background: #f8fafc; border-left: 4px solid #2563eb; padding: 14px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <strong style="font-size: 14px;">Stato Tabelle Database Studio:</strong>
                <div style="display: flex; gap: 10px; margin-top: 6px; flex-wrap: wrap;">
                    <?php if (!empty($tables_status)): ?>
                        <?php foreach ($tables_status as $tbl_name => $tbl_ok): ?>
                            <span style="font-size: 12px; padding: 3px 8px; border-radius: 4px; font-weight: 600; background: <?php echo $tbl_ok ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;'; ?>">
                                <?php echo $tbl_ok ? '&#10004;' : '&#10008;'; ?> <?php echo esc_html($tbl_name); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <a href="<?php echo esc_url($repair_url); ?>" class="btn-studio btn-studio-primary" style="padding: 6px 12px; font-size: 12px;">Forza Creazione Tabelle</a>
            </div>
        </div>
        <?php $db_err = get_transient('studio_db_error'); if ($db_err): ?>
            <div style="margin-top: 10px; color: #b91c1c; font-size: 12px;">
                <strong>Errore rilevato da MySQL:</strong> <code><?php echo esc_html($db_err); ?></code>
            </div>
        <?php endif; ?>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('studio_settings_verify'); ?>

        <div class="studio-panel">
            <h2>Dati Identificativi & Fiscali</h2>
            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Denominazione Studio:</label>
                    <input type="text" name="studio_denominazione" value="<?php echo esc_attr($studio['denominazione']); ?>" required>
                </div>
                <div class="studio-field">
                    <label>Nome e Cognome Professionista:</label>
                    <input type="text" name="studio_professionista" value="<?php echo esc_attr($studio['professionista']); ?>" required>
                </div>
                <div class="studio-field">
                    <label>Titolo Professionale:</label>
                    <input type="text" name="studio_titolo" value="<?php echo esc_attr($studio['titolo']); ?>" placeholder="Es. Psicologo, Psicoterapeuta">
                </div>
                <div class="studio-field">
                    <label>Codice Fiscale:</label>
                    <input type="text" name="studio_cf" value="<?php echo esc_attr($studio['codice_fiscale']); ?>" required>
                </div>
                <div class="studio-field">
                    <label>Partita IVA:</label>
                    <input type="text" name="studio_piva" value="<?php echo esc_attr($studio['partita_iva']); ?>">
                </div>
            </div>

            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Indirizzo Studio:</label>
                    <input type="text" name="studio_indirizzo" value="<?php echo esc_attr($studio['indirizzo']); ?>">
                </div>
                <div class="studio-field">
                    <label>CAP:</label>
                    <input type="text" name="studio_cap" value="<?php echo esc_attr($studio['cap']); ?>">
                </div>
                <div class="studio-field">
                    <label>Città:</label>
                    <input type="text" name="studio_citta" value="<?php echo esc_attr($studio['citta']); ?>">
                </div>
                <div class="studio-field">
                    <label>Provincia (Sigla):</label>
                    <input type="text" name="studio_provincia" value="<?php echo esc_attr($studio['provincia']); ?>" maxlength="5">
                </div>
            </div>

            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Telefono:</label>
                    <input type="text" name="studio_telefono" value="<?php echo esc_attr($studio['telefono']); ?>">
                </div>
                <div class="studio-field">
                    <label>Email Studio:</label>
                    <input type="email" name="studio_email" value="<?php echo esc_attr($studio['email']); ?>">
                </div>
                <div class="studio-field"><label>Email Commercialista:</label><input type="email" name="studio_commercialista_email" value="<?php echo esc_attr($studio['commercialista_email']); ?>"></div>
                <div class="studio-field">
                    <label>PEC:</label>
                    <input type="email" name="studio_pec" value="<?php echo esc_attr($studio['pec']); ?>">
                </div>
                <div class="studio-field">
                    <label>Sito Web:</label>
                    <input type="text" name="studio_sito_web" value="<?php echo esc_attr($studio['sito_web']); ?>">
                </div>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Dati Bancari e Pagamenti</h2>
            <div class="studio-form-grid">
                <div class="studio-field" style="grid-column: span 2;">
                    <label>IBAN:</label>
                    <input type="text" name="studio_iban" value="<?php echo esc_attr($studio['iban']); ?>" placeholder="IT00X0000000000000000000000">
                </div>
                <div class="studio-field">
                    <label>Istituto di Credito (Banca):</label>
                    <input type="text" name="studio_banca" value="<?php echo esc_attr($studio['banca']); ?>">
                </div>
                <div class="studio-field">
                    <label>BIC / SWIFT:</label>
                    <input type="text" name="studio_bic_swift" value="<?php echo esc_attr($studio['bic_swift']); ?>">
                </div>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Parametri Fiscali e Contributivi</h2>
            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Nome Cassa Previdenziale:</label>
                    <input type="text" name="studio_cassa_previdenza_nome" value="<?php echo esc_attr($studio['cassa_previdenza']); ?>" placeholder="es. ENPAP (2%)">
                </div>
                <div class="studio-field">
                    <label>Aliquota Cassa Previdenziale (%):</label>
                    <input type="number" step="0.01" name="studio_cassa_perc" value="<?php echo esc_attr($studio['cassa_perc']); ?>">
                </div>
                <div class="studio-field">
                    <label>Importo Marca da Bollo (&euro;):</label>
                    <input type="number" step="0.01" name="studio_marca_bollo_valore" value="<?php echo esc_attr($studio['marca_bollo']); ?>">
                </div>
                <div class="studio-field">
                    <label>Soglia Esenzione Bollo (&euro;):</label>
                    <input type="number" step="0.01" name="studio_marca_bollo_soglia" value="<?php echo esc_attr($studio['marca_bollo_soglia']); ?>">
                    <span class="desc">Standard per fatture esenti IVA: 77.47 &euro;</span>
                </div>
                <div class="studio-field">
                    <label>Ritenuta d'acconto standard (%):</label>
                    <input type="number" step="0.01" name="studio_ritenuta_perc" value="<?php echo esc_attr($studio['ritenuta_perc']); ?>">
                    <span class="desc">Lasciare a 0 se operi in regime forfettario o con privati.</span>
                </div>
            </div>

            <div class="studio-field" style="margin-top: 15px;">
                <label>Note Legali Standard in Fattura:</label>
                <textarea name="studio_note_legali" rows="3"><?php echo esc_textarea($studio['note_legali']); ?></textarea>
                <span class="desc">Verranno riportate a fondo pagina su ogni fattura (es. dicitura forfettario / esenzione art. 10).</span>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Tariffario Prestazioni Predefinite</h2>
            <p class="description">Definisci le prestazioni standard dello studio per inserirle al volo durante la compilazione delle fatture.</p>
            
            <table class="wp-list-table widefat striped" style="margin-top: 10px; margin-bottom: 15px;" id="table_prestazioni">
                <thead>
                    <tr>
                        <th>Nome Prestazione</th>
                        <th style="width: 150px;">Tariffa Standard (&euro;)</th>
                        <th style="width: 80px;">Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($prestazioni)): ?>
                        <?php foreach ($prestazioni as $pr): ?>
                            <tr>
                                <td><input type="text" name="prestazioni_nome[]" value="<?php echo esc_attr($pr['nome']); ?>" class="widefat"></td>
                                <td><input type="number" step="0.5" name="prestazioni_prezzo[]" value="<?php echo esc_attr($pr['prezzo']); ?>" class="widefat"></td>
                                <td><button type="button" class="btn-studio btn-studio-danger btn-remove-prestazione" style="padding: 2px 6px;">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="btn-studio btn-studio-secondary" id="btn_add_prestazione">+ Aggiungi Prestazione</button>
        </div>

        <div class="studio-panel">
            <h2>Testo Informativa Privacy (GDPR Sanità)</h2>
            <div class="studio-field">
                <label>Testo Informativa per Modulo Consenso Paziente:</label>
                <textarea name="studio_testo_privacy" rows="7"><?php echo esc_textarea($studio['testo_privacy']); ?></textarea>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <input type="submit" name="studio_settings_submit" class="btn-studio btn-studio-primary" value="Salva Impostazioni Studio" style="padding: 10px 24px; font-size: 15px;">
        </div>
    </form>
<h2>🧠 Vocabolario Profilo Clinico</h2><p>Modifica categorie, colori e tag disponibili nel Profilo Clinico. Inserisci una voce per riga.</p><form method="post"><?php wp_nonce_field('studio_save_clinical_vocabulary'); ?><input type="hidden" name="studio_save_clinical_vocabulary" value="1"><div id="clinical-vocabulary-settings"><?php foreach(Studio_Clinical_Profile::vocabulary() as $i=>$cc): ?><div class="studio-panel" style="padding:14px"><div class="studio-form-grid"><div class="studio-field"><label>Categoria</label><input name="clinical_category_name[]" value="<?php echo esc_attr($cc['name']); ?>"></div><div class="studio-field"><label>Colore</label><input type="color" name="clinical_category_color[]" value="<?php echo esc_attr($cc['color']); ?>"></div></div><div class="studio-field"><label>Voci, una per riga</label><textarea rows="5" name="clinical_category_items[]"><?php echo esc_textarea(implode("
",$cc['items'])); ?></textarea></div></div><?php endforeach; ?></div><button class="btn-studio btn-studio-primary" type="submit">Salva vocabolario clinico</button></form><hr style="margin:28px 0">

</div>

<script>
jQuery(document).ready(function($) {
    $('#btn_add_prestazione').on('click', function() {
        const row = `
            <tr>
                <td><input type="text" name="prestazioni_nome[]" placeholder="Nome prestazione..." class="widefat"></td>
                <td><input type="number" step="0.5" name="prestazioni_prezzo[]" value="0.00" class="widefat"></td>
                <td><button type="button" class="btn-studio btn-studio-danger btn-remove-prestazione" style="padding: 2px 6px;">&times;</button></td>
            </tr>
        `;
        $('#table_prestazioni tbody').append(row);
    });

    $(document).on('click', '.btn-remove-prestazione', function() {
        $(this).closest('tr').remove();
    });
});
</script>

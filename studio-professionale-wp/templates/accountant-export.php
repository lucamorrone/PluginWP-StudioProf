<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap studio-wrap">
<?php if(isset($_GET['report_sent'])):?><div class="notice notice-success is-dismissible"><p>Report PDF inviato con successo al commercialista.</p></div><?php endif;?>
    <div class="studio-header">
        <div>
            <h1>Esportazione Dati per il Commercialista</h1>
            <p class="description">Genera report trimestrali, mensili o annuali con elenco completo dei compensi, dati anagrafici e pacchetti ZIP con i documenti.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <!-- Card 1: Esportazione Dati / File -->
        <div class="studio-panel"><h2>Email commercialista</h2><form method="post"><?php wp_nonce_field('studio_export_accountant');?><input type="email" name="commercialista_email" value="<?php echo esc_attr(Studio_DB::get_setting('studio_commercialista_email'));?>" class="regular-text" placeholder="commercialista@example.com" required> <?php if(current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)):?><button class="button" name="studio_accountant_email_save">Salva email</button><?php endif;?></form></div>
<div class="studio-panel">
            <h2>Parametri di Esportazione</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('studio_export_accountant'); ?>

                <div class="studio-form-grid" style="grid-template-columns: 1fr;">
                    <div class="studio-field">
                        <label>Anno di Riferimento:</label>
                        <select name="export_anno">
                            <?php foreach ($anni_disponibili as $an): ?>
                                <option value="<?php echo esc_attr($an); ?>"><?php echo esc_html($an); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="studio-field">
                        <label>Periodo Fiscale:</label>
                        <select name="export_periodo" id="export_periodo">
                            <option value="year">Intero Anno Solare</option>
                            <optgroup label="Trimestri (IVA / Liquidazione)">
                                <option value="q1">1° Trimestre (Gennaio - Marzo)</option>
                                <option value="q2">2° Trimestre (Aprile - Giugno)</option>
                                <option value="q3">3° Trimestre (Luglio - Settembre)</option>
                                <option value="q4">4° Trimestre (Ottobre - Dicembre)</option>
                            </optgroup>
                            <optgroup label="Mesi Singoli">
                                <option value="m_1">Gennaio</option>
                                <option value="m_2">Febbraio</option>
                                <option value="m_3">Marzo</option>
                                <option value="m_4">Aprile</option>
                                <option value="m_5">Maggio</option>
                                <option value="m_6">Giugno</option>
                                <option value="m_7">Luglio</option>
                                <option value="m_8">Agosto</option>
                                <option value="m_9">Settembre</option>
                                <option value="m_10">Ottobre</option>
                                <option value="m_11">Novembre</option>
                                <option value="m_12">Dicembre</option>
                            </optgroup>
                            <option value="custom">Intervallo Date Personalizzato</option>
                        </select>
                    </div>

                    <div id="custom_dates_wrapper" style="display: none;">
                        <div class="studio-form-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="studio-field">
                                <label>Dalla data:</label>
                                <input type="date" name="custom_from">
                            </div>
                            <div class="studio-field">
                                <label>Alla data:</label>
                                <input type="date" name="custom_to">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px;">
                    <button type="submit" name="studio_export_accountant_csv" class="btn-studio btn-studio-primary" style="padding: 10px 16px;">
                        &#128196; Scarica File CSV / Excel (Tutti i Dati Fiscali e Pazienti)
                    </button>

                    <button type="submit" name="studio_export_accountant_zip" class="btn-studio btn-studio-success" style="padding: 10px 16px;">
                        &#128178; Scarica Pacchetto ZIP Completo (CSV + Tutte le Fatture PDF)
                    </button>

                    <button type="submit" formtarget="_blank" name="studio_export_accountant_summary" class="btn-studio btn-studio-secondary" style="padding: 10px 16px;">
                        &#128462; Genera Report PDF di Riepilogo Periodico
                    </button>
                    <button type="submit" name="studio_export_accountant_email" onclick="return confirm('Confermi l’invio del report PDF al commercialista?')" class="btn-studio btn-studio-success" style="padding:10px 16px;">Genera report e invia al commercialista</button>
                </div>
            </form>
        </div>

<div class="studio-panel"><h2>Log invii al commercialista</h2><table class="widefat striped"><thead><tr><th>Data</th><th>Operatore</th><th>Destinatario</th><th>Periodo</th><th>Fatture</th><th>Oggetto</th></tr></thead><tbody><?php if($commercialista_logs):foreach($commercialista_logs as $l):$d=json_decode($l->dettagli,true);?><tr><td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($l->data_evento)));?></td><td><?php echo esc_html($l->display_name);?></td><td><?php echo esc_html($d['destinatario']??'');?></td><td><?php echo esc_html($d['periodo']??'');?></td><td><?php echo esc_html($d['fatture']??'');?></td><td><?php echo esc_html($d['oggetto']??'');?></td></tr><?php endforeach;else:?><tr><td colspan="6">Nessun invio registrato.</td></tr><?php endif;?></tbody></table></div>
</div>

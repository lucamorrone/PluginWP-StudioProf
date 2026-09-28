<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Esportazione Dati per il Commercialista</h1>
            <p class="description">Genera report trimestrali, mensili o annuali con elenco completo dei compensi, dati anagrafici e pacchetti ZIP con i documenti.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <!-- Card 1: Esportazione Dati / File -->
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
                        &#128178; Scarica Pacchetto ZIP Completo (CSV + Tutte le Fatture HTML/PDF)
                    </button>

                    <button type="submit" formtarget="_blank" name="studio_export_accountant_summary" class="btn-studio btn-studio-secondary" style="padding: 10px 16px;">
                        &#128462; Genera Report Stampabile di Riepilogo Periodico
                    </button>
                </div>
            </form>
        </div>

        <!-- Card 2: Informazioni per il Commercialista -->
        <div class="studio-panel" style="background: #f8fafc;">
            <h2>Cosa Contengono gli Export</h2>
            <div style="font-size: 13px; line-height: 1.6; color: #334155;">
                <p>Gli strumenti di esportazione sono predisposti specificamente per la compilazione del modello dei redditi, calcolo imposta forfettaria, liquidazioni periodiche e trasmissione <strong>Sistema Tessera Sanitaria (STS)</strong>.</p>

                <h4 style="margin-bottom: 5px; color: #0f172a;">Nel file Excel/CSV:</h4>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li>Numero progressivo e data fattura</li>
                    <li>Dati anagrafici completi paziente (Nome, Cognome, Codice Fiscale, Comune di nascita e residenza)</li>
                    <li>Totale imponibile prestazioni</li>
                    <li>Quote cassa di previdenza sanitaria (es. ENPAP)</li>
                    <li>Eventuale IVA applicata o indicazione esenzione sanitaria</li>
                    <li>Marca da bollo assolta virtualmente</li>
                    <li>Data incasso effettivo e metodo di pagamento per principio di cassa</li>
                </ul>

                <h4 style="margin-bottom: 5px; color: #0f172a; margin-top: 15px;">Nel Pacchetto ZIP:</h4>
                <p>Include la cartella contenente ciascuna copia della fattura emessa nel periodo, perfettamente archiviata e pronta da allegare alla contabilità.</p>
            </div>
        </div>
    </div>
</div>

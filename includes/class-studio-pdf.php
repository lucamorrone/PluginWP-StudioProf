<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_PDF {

    public static function get_storage_dir() {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . '/studio-professionale-docs';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        return $dir;
    }

    public static function generate_invoice_pdf($invoice_id) {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');
        $t_righe = Studio_DB::table('fatture_righe');

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT f.*, p.nome, p.cognome, p.codice_fiscale, p.email, p.telefono, p.indirizzo_residenza, p.citta_residenza, p.cap_residenza, p.provincia_residenza FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id WHERE f.id = %d", $invoice_id));
        if (!$invoice) return false;

        $righe = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_righe WHERE fattura_id = %d ORDER BY ordine ASC, id ASC", $invoice_id));
        $studio = Studio_DB::get_studio_data();

        $html = self::build_invoice_html($invoice, $righe, $studio);

        $filename = 'Fattura_' . (!empty($invoice->codice_fattura) ? str_replace('/', '-', $invoice->codice_fattura) : 'Bozza_' . $invoice->id) . '.html';
        $filepath = self::get_storage_dir() . '/' . $filename;
        file_put_contents($filepath, $html);

        $upload_dir = wp_upload_dir();
        $url = $upload_dir['baseurl'] . '/studio-professionale-docs/' . $filename;

        $wpdb->update($t_fatture, array(
            'pdf_path' => $filepath,
            'pdf_url'  => $url
        ), array('id' => $invoice_id));

        return $filepath;
    }

    public static function output_invoice_pdf($invoice_id) {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');
        $t_righe = Studio_DB::table('fatture_righe');

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT f.*, p.nome, p.cognome, p.codice_fiscale, p.email, p.telefono, p.indirizzo_residenza, p.citta_residenza, p.cap_residenza, p.provincia_residenza FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id WHERE f.id = %d", $invoice_id));
        if (!$invoice) {
            wp_die(__('Fattura non trovata.', 'studio-professionale'));
        }

        $righe = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_righe WHERE fattura_id = %d ORDER BY ordine ASC, id ASC", $invoice_id));
        $studio = Studio_DB::get_studio_data();

        $html = self::build_invoice_html($invoice, $righe, $studio, true);

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function build_invoice_html($invoice, $righe, $studio, $auto_print = false) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="utf-8">
            <title><?php echo esc_html(!empty($invoice->codice_fattura) ? 'Fattura n. ' . $invoice->codice_fattura : 'Bozza Documento'); ?></title>
            <style>
                @page { size: A4; margin: 20mm; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    color: #2c3338;
                    font-size: 13px;
                    line-height: 1.5;
                    margin: 0;
                    padding: 20px;
                    background: #fff;
                }
                .invoice-box {
                    max-width: 800px;
                    margin: auto;
                    background: #fff;
                }
                .header-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 25px;
                }
                .header-table td {
                    vertical-align: top;
                }
                .studio-info h2 {
                    margin: 0 0 5px 0;
                    font-size: 20px;
                    color: #0f172a;
                }
                .studio-info .profession {
                    font-size: 13px;
                    color: #64748b;
                    font-weight: 600;
                    margin-bottom: 8px;
                }
                .recipient-card {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 15px;
                    margin-left: 20px;
                }
                .recipient-card h3 {
                    margin: 0 0 8px 0;
                    font-size: 14px;
                    color: #0f172a;
                    border-bottom: 1px solid #e2e8f0;
                    padding-bottom: 4px;
                }
                .document-meta-box {
                    background: #f1f5f9;
                    border-radius: 6px;
                    padding: 12px 18px;
                    margin-bottom: 25px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .meta-title {
                    font-size: 18px;
                    font-weight: 700;
                    color: #0f172a;
                }
                .meta-badge {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 4px;
                    font-weight: 700;
                    font-size: 12px;
                    text-transform: uppercase;
                }
                .badge-paid { background: #dcfce7; color: #166534; }
                .badge-unpaid { background: #fee2e2; color: #991b1b; }
                .badge-draft { background: #fef3c7; color: #92400e; }

                table.items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 25px;
                }
                table.items-table th {
                    background: #f8fafc;
                    border-bottom: 2px solid #cbd5e1;
                    padding: 10px;
                    text-align: left;
                    font-size: 12px;
                    text-transform: uppercase;
                    color: #475569;
                }
                table.items-table td {
                    padding: 10px;
                    border-bottom: 1px solid #e2e8f0;
                }
                table.items-table .text-right { text-align: right; }
                table.items-table .text-center { text-align: center; }

                .totals-wrapper {
                    width: 100%;
                    margin-bottom: 30px;
                }
                .totals-table {
                    width: 320px;
                    margin-left: auto;
                    border-collapse: collapse;
                }
                .totals-table td {
                    padding: 6px 10px;
                    border-bottom: 1px solid #f1f5f9;
                }
                .totals-table tr.grand-total td {
                    font-size: 16px;
                    font-weight: 700;
                    color: #0f172a;
                    border-top: 2px solid #0f172a;
                    border-bottom: 2px solid #0f172a;
                    background: #f8fafc;
                }

                .legal-footer {
                    border-top: 1px solid #e2e8f0;
                    padding-top: 15px;
                    font-size: 11px;
                    color: #64748b;
                    line-height: 1.6;
                }
                .payment-box {
                    background: #f8fafc;
                    border-left: 4px solid #3b82f6;
                    padding: 10px 14px;
                    margin-bottom: 20px;
                    font-size: 12px;
                }
                .print-bar {
                    background: #0f172a;
                    color: #fff;
                    padding: 10px 20px;
                    margin-bottom: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: 6px;
                }
                .print-btn {
                    background: #3b82f6;
                    color: #fff;
                    border: none;
                    padding: 8px 16px;
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 4px;
                    cursor: pointer;
                }
                @media print {
                    .print-bar { display: none !important; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-box">
                <div class="print-bar">
                    <span><strong>Anteprima Ufficiale Documento</strong> - Pronto per stampa o salvataggio PDF</span>
                    <button class="print-btn" onclick="window.print()">Stampa / Salva come PDF</button>
                </div>

                <!-- Intestazione Studio e Paziente -->
                <table class="header-table">
                    <tr>
                        <td class="studio-info" style="width: 55%;">
                            <h2><?php echo esc_html($studio['professionista']); ?></h2>
                            <div class="profession"><?php echo esc_html($studio['titolo']); ?></div>
                            <div><?php echo esc_html($studio['indirizzo']); ?></div>
                            <div><?php echo esc_html($studio['cap'] . ' ' . $studio['citta'] . ' (' . $studio['provincia'] . ')'); ?></div>
                            <div><strong>C.F.:</strong> <?php echo esc_html($studio['codice_fiscale']); ?></div>
                            <?php if (!empty($studio['partita_iva'])): ?>
                                <div><strong>P. IVA:</strong> <?php echo esc_html($studio['partita_iva']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($studio['telefono'])): ?><div><strong>Tel:</strong> <?php echo esc_html($studio['telefono']); ?></div><?php endif; ?>
                            <?php if (!empty($studio['email'])): ?><div><strong>Email:</strong> <?php echo esc_html($studio['email']); ?></div><?php endif; ?>
                        </td>
                        <td style="width: 45%;">
                            <div class="recipient-card">
                                <h3>Spett.le Paziente</h3>
                                <div style="font-weight: 700; font-size: 15px; color: #0f172a; margin-bottom: 4px;">
                                    <?php echo esc_html($invoice->cognome . ' ' . $invoice->nome); ?>
                                </div>
                                <div><strong>C.F.:</strong> <?php echo esc_html($invoice->codice_fiscale); ?></div>
                                <?php if (!empty($invoice->indirizzo_residenza)): ?>
                                    <div><?php echo esc_html($invoice->indirizzo_residenza); ?></div>
                                    <div><?php echo esc_html($invoice->cap_residenza . ' ' . $invoice->citta_residenza . ' (' . $invoice->provincia_residenza . ')'); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($invoice->email)): ?>
                                    <div><strong>Email:</strong> <?php echo esc_html($invoice->email); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Meta Dati Fattura -->
                <div class="document-meta-box">
                    <div>
                        <span class="meta-title">
                            <?php if ($invoice->stato === 'emessa'): ?>
                                Fattura Sanitaria N. <?php echo esc_html($invoice->codice_fattura); ?>
                            <?php else: ?>
                                PROFORMA / BOZZA N. <?php echo esc_html($invoice->id); ?>
                            <?php endif; ?>
                        </span>
                        <div style="color: #64748b; font-size: 12px; margin-top: 4px;">
                            Data Emissione: <strong><?php echo date('d/m/Y', strtotime($invoice->data_documento)); ?></strong> | Valuta: EUR
                        </div>
                    </div>
                    <div>
                        <?php if ($invoice->stato === 'bozza'): ?>
                            <span class="meta-badge badge-draft">Bozza non fiscale</span>
                        <?php elseif ($invoice->stato_pagamento === 'pagata'): ?>
                            <span class="meta-badge badge-paid">Documento Saldato (<?php echo esc_html($invoice->metodo_pagamento); ?> - <?php echo date('d/m/Y', strtotime($invoice->data_pagamento)); ?>)</span>
                        <?php else: ?>
                            <span class="meta-badge badge-unpaid">Da Pagare</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tabella Righe -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 55%;">Descrizione Prestazione Sanitaria</th>
                            <th class="text-center" style="width: 10%;">Q.tà</th>
                            <th class="text-right" style="width: 15%;">Prezzo Unit.</th>
                            <th class="text-center" style="width: 10%;">Sconto %</th>
                            <th class="text-right" style="width: 15%;">Importo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($righe as $r): ?>
                            <tr>
                                <td><?php echo esc_html($r->descrizione); ?></td>
                                <td class="text-center"><?php echo number_format($r->quantita, 1); ?></td>
                                <td class="text-right"><?php echo number_format($r->prezzo_unitario, 2, ',', '.'); ?> &euro;</td>
                                <td class="text-center"><?php echo $r->sconto_maggiorazione_perc != 0 ? number_format($r->sconto_maggiorazione_perc, 0) . '%' : '-'; ?></td>
                                <td class="text-right"><strong><?php echo number_format($r->totale_riga, 2, ',', '.'); ?> &euro;</strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Totali Fiscali -->
                <div class="totals-wrapper">
                    <table class="totals-table">
                        <tr>
                            <td>Totale Imponibile:</td>
                            <td class="text-right"><strong><?php echo number_format($invoice->totale_imponibile, 2, ',', '.'); ?> &euro;</strong></td>
                        </tr>
                        <?php if ($invoice->totale_cassa > 0): ?>
                            <tr>
                                <td>Contributo <?php echo esc_html($studio['cassa_previdenza']); ?>:</td>
                                <td class="text-right"><?php echo number_format($invoice->totale_cassa, 2, ',', '.'); ?> &euro;</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($invoice->totale_iva > 0): ?>
                            <tr>
                                <td>IVA (<?php echo number_format($invoice->percentuale_iva, 0); ?>%):</td>
                                <td class="text-right"><?php echo number_format($invoice->totale_iva, 2, ',', '.'); ?> &euro;</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($invoice->marca_bollo > 0): ?>
                            <tr>
                                <td>Imposta di Bollo (D.M. 17/06/2014):</td>
                                <td class="text-right"><?php echo number_format($invoice->marca_bollo, 2, ',', '.'); ?> &euro;</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($invoice->totale_ritenuta > 0): ?>
                            <tr>
                                <td>Ritenuta d'acconto (<?php echo number_format($invoice->percentuale_ritenuta, 0); ?>%):</td>
                                <td class="text-right">- <?php echo number_format($invoice->totale_ritenuta, 2, ',', '.'); ?> &euro;</td>
                            </tr>
                        <?php endif; ?>
                        <tr class="grand-total">
                            <td>Totale Documento:</td>
                            <td class="text-right"><?php echo number_format($invoice->totale_documento, 2, ',', '.'); ?> &euro;</td>
                        </tr>
                    </table>
                </div>

                <!-- Box Coordinate Pagamento -->
                <div class="payment-box">
                    <strong>Modalità di saldo e coordinate bancarie:</strong><br>
                    <?php if (!empty($studio['iban'])): ?>
                        IBAN: <strong><?php echo esc_html($studio['iban']); ?></strong> - Banca: <?php echo esc_html($studio['banca']); ?><br>
                    <?php endif; ?>
                    Stato pagamento: 
                    <?php if ($invoice->stato_pagamento === 'pagata'): ?>
                        <strong style="color: #166534;">SALDATO in data <?php echo date('d/m/Y', strtotime($invoice->data_pagamento)); ?> tramite <?php echo esc_html($invoice->metodo_pagamento); ?></strong>
                    <?php else: ?>
                        <strong style="color: #991b1b;">DA PAGARE</strong> (Metodo previsto: <?php echo esc_html($invoice->metodo_pagamento ?: 'Bonifico bancario'); ?>)
                    <?php endif; ?>
                </div>

                <!-- Note Legali e Deontologiche -->
                <div class="legal-footer">
                    <?php if (!empty($studio['note_legali'])): ?>
                        <div><?php echo nl2br(esc_html($studio['note_legali'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($invoice->note_documento)): ?>
                        <div style="margin-top: 8px;"><strong>Note:</strong> <?php echo nl2br(esc_html($invoice->note_documento)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($auto_print): ?>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    // window.print();
                });
            </script>
            <?php endif; ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    public static function output_privacy_pdf($patient_id) {
        global $wpdb;
        $t_pazienti = Studio_DB::table('pazienti');
        $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_pazienti WHERE id = %d", $patient_id));
        if (!$patient) {
            wp_die(__('Paziente non trovato.', 'studio-professionale'));
        }

        $studio = Studio_DB::get_studio_data();

        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="utf-8">
            <title>Consenso Privacy - <?php echo esc_html($patient->cognome . ' ' . $patient->nome); ?></title>
            <style>
                @page { size: A4; margin: 20mm; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    color: #1e293b;
                    font-size: 13px;
                    line-height: 1.6;
                    padding: 20px;
                    background: #fff;
                }
                .container { max-width: 800px; margin: auto; }
                .header { text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px; }
                .header h1 { margin: 0 0 5px 0; font-size: 18px; text-transform: uppercase; color: #0f172a; }
                .header p { margin: 0; font-size: 12px; color: #64748b; }
                .patient-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 20px; }
                .patient-box h3 { margin: 0 0 8px 0; font-size: 14px; color: #0f172a; }
                .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
                .privacy-content { font-size: 12px; text-align: justify; margin-bottom: 30px; }
                .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
                .sig-box { width: 45%; border-top: 1px solid #000; text-align: center; padding-top: 6px; font-size: 12px; }
                .print-bar {
                    background: #0f172a;
                    color: #fff;
                    padding: 10px 20px;
                    margin-bottom: 20px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: 6px;
                }
                .print-btn {
                    background: #3b82f6;
                    color: #fff;
                    border: none;
                    padding: 8px 16px;
                    font-size: 13px;
                    font-weight: 600;
                    border-radius: 4px;
                    cursor: pointer;
                }
                @media print {
                    .print-bar { display: none !important; }
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="print-bar">
                    <span><strong>Modulo Consenso Privacy Precompilato</strong></span>
                    <button class="print-btn" onclick="window.print()">Stampa Modulo</button>
                </div>

                <div class="header">
                    <h1><?php echo esc_html($studio['denominazione']); ?></h1>
                    <p><?php echo esc_html($studio['professionista'] . ' - ' . $studio['titolo']); ?> | P.IVA: <?php echo esc_html($studio['partita_iva'] ?: $studio['codice_fiscale']); ?></p>
                </div>

                <div class="patient-box">
                    <h3>Dati Identificativi del Paziente</h3>
                    <div class="grid">
                        <div><strong>Nome e Cognome:</strong> <?php echo esc_html($patient->cognome . ' ' . $patient->nome); ?></div>
                        <div><strong>Codice Fiscale:</strong> <?php echo esc_html($patient->codice_fiscale); ?></div>
                        <div><strong>Nato/a il:</strong> <?php echo $patient->data_nascita ? date('d/m/Y', strtotime($patient->data_nascita)) : '-'; ?> a <?php echo esc_html($patient->luogo_nascita . ' (' . $patient->provincia_nascita . ')'); ?></div>
                        <div><strong>Residenza:</strong> <?php echo esc_html($patient->indirizzo_residenza . ' - ' . $patient->cap_residenza . ' ' . $patient->citta_residenza . ' (' . $patient->provincia_residenza . ')'); ?></div>
                        <div><strong>Telefono:</strong> <?php echo esc_html($patient->telefono); ?></div>
                        <div><strong>Email:</strong> <?php echo esc_html($patient->email); ?></div>
                    </div>
                </div>

                <div class="privacy-content">
                    <?php echo nl2br(esc_html($studio['testo_privacy'])); ?>
                </div>

                <div class="signatures">
                    <div class="sig-box">
                        Luogo e Data<br><br>
                        <?php echo esc_html($studio['citta'] ?: 'Lì'); ?>, <?php echo date('d/m/Y'); ?>
                    </div>
                    <div class="sig-box">
                        Firma leggibile del Paziente (o genitore/tutore)<br><br><br>
                        __________________________________________
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    public static function send_invoice_email($invoice_id) {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');
        $t_righe = Studio_DB::table('fatture_righe');

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT f.*, p.nome, p.cognome, p.email FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id WHERE f.id = %d", $invoice_id));
        if (!$invoice || empty($invoice->email)) {
            return new WP_Error('no_recipient', __('Il paziente non ha un indirizzo email configurato.', 'studio-professionale'));
        }

        $studio = Studio_DB::get_studio_data();

        // Genera/aggiorna HTML
        $righe = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_righe WHERE fattura_id = %d ORDER BY ordine ASC, id ASC", $invoice_id));
        $html_file = self::generate_invoice_pdf($invoice_id);

        $to = $invoice->email;
        $subject = sprintf('Fattura Sanitaria N. %s - %s', $invoice->codice_fattura ?: ('Bozza ' . $invoice->id), $studio['professionista']);
        
        $body = "Gentile " . esc_html($invoice->nome . ' ' . $invoice->cognome) . ",\n\n";
        $body .= "In allegato Le trasmettiamo la documentazione fiscale relativa alla prestazione sanitaria ricevuta.\n\n";
        $body .= "Riepilogo:\n";
        $body .= "Documento: " . ($invoice->codice_fattura ?: 'Bozza ' . $invoice->id) . "\n";
        $body .= "Data: " . date('d/m/Y', strtotime($invoice->data_documento)) . "\n";
        $body .= "Importo totale: " . number_format($invoice->totale_documento, 2, ',', '.') . " EUR\n";
        $body .= "Stato pagamento: " . ($invoice->stato_pagamento === 'pagata' ? 'SALDATO' : 'DA SALDARE') . "\n\n";
        if ($invoice->stato_pagamento !== 'pagata' && !empty($studio['iban'])) {
            $body .= "Coordinate per il pagamento:\nIBAN: " . $studio['iban'] . " (" . $studio['banca'] . ")\n\n";
        }
        $body .= "Cordiali saluti,\n" . $studio['professionista'] . "\n" . $studio['denominazione'];

        $headers = array(
            'From: ' . $studio['professionista'] . ' <' . $studio['email'] . '>',
            'Reply-To: ' . $studio['email'],
        );

        $attachments = array($html_file);

        $sent = wp_mail($to, $subject, $body, $headers, $attachments);
        if (!$sent) {
            return new WP_Error('mail_failed', __('Invio email fallito. Verifica la configurazione SMTP del server.', 'studio-professionale'));
        }

        return true;
    }
}

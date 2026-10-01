<?php
if (!defined('ABSPATH')) exit;
$pdf_url = wp_nonce_url(admin_url('admin.php?page=studio-fatture&action=studio_invoice_pdf&id=' . $invoice->id), 'studio_invoice_pdf_' . $invoice->id);
$issue_url = wp_nonce_url(admin_url('admin.php?page=studio-fatture&action=issue&id=' . $invoice->id), 'studio_issue_invoice_' . $invoice->id);
$email_url = wp_nonce_url(admin_url('admin.php?page=studio-fatture&action=send_email&id=' . $invoice->id), 'studio_send_email_' . $invoice->id);
$delete_url = wp_nonce_url(admin_url('admin.php?page=studio-fatture&action=delete&id=' . $invoice->id), 'studio_delete_invoice_' . $invoice->id);

$msg = isset($_GET['msg']) ? sanitize_text_field($_GET['msg']) : '';
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>
                <?php echo ($invoice->stato === 'emessa') ? 'Fattura N. ' . esc_html($invoice->codice_fattura) : 'Bozza Fattura #' . esc_html($invoice->id); ?>
            </h1>
            <p class="description">Dettaglio documento fiscale, stato saldi, anteprima di stampa ed invio al paziente.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="btn-studio btn-studio-secondary">&#128462; Stampa / Scarica PDF</a>
            
            <?php if (!empty($invoice->email)): ?>
                <a href="<?php echo esc_url($email_url); ?>" class="btn-studio btn-studio-primary" onclick="return confirm('Confermi l\'invio del documento via email a <?php echo esc_js($invoice->email); ?>?');">&#9993; Invia per Email</a>
            <?php endif; ?>

            <?php if ($invoice->stato === 'bozza'): ?>
                <a href="<?php echo esc_url($issue_url); ?>" class="btn-studio btn-studio-success btn-confirm-issue">&#10004; Emetti Fattura</a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=edit&id=' . $invoice->id)); ?>" class="btn-studio btn-studio-secondary">&#9998; Modifica</a>
                <a href="<?php echo esc_url($delete_url); ?>" class="btn-studio btn-studio-danger" onclick="return confirm('Sei sicuro di voler eliminare questa bozza?');">&#128465; Elimina Bozza</a>
            <?php else: ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=edit&id=' . $invoice->id)); ?>" class="btn-studio btn-studio-secondary">Aggiorna Pagamento</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($msg === 'issued'): ?>
        <div class="notice notice-success is-dismissible" style="margin-left:0;">
            <p><strong>Fattura emessa con successo!</strong> È stato assegnato il numero progressivo ufficiale <strong><?php echo esc_html($invoice->codice_fattura); ?></strong> ed è stato generato il documento pronto per l'invio.</p>
        </div>
    <?php elseif ($msg === 'email_sent'): ?>
        <div class="notice notice-success is-dismissible" style="margin-left:0;">
            <p><strong>Documento inviato con successo via email</strong> all'indirizzo del paziente: <?php echo esc_html($invoice->email); ?></p>
        </div>
    <?php elseif ($msg === 'email_error'): ?>
        <div class="notice notice-error is-dismissible" style="margin-left:0;">
            <p><strong>Errore durante l'invio dell'email:</strong> <?php echo esc_html(isset($_GET['err']) ? sanitize_text_field($_GET['err']) : 'Verificare la configurazione del server SMTP'); ?></p>
        </div>
    <?php elseif ($msg === 'payment_updated'): ?>
        <div class="notice notice-success is-dismissible" style="margin-left:0;">
            <p><strong>Stato pagamento aggiornato con successo!</strong> Il PDF è stato rigenerato con la nuova quietanza.</p>
        </div>
    <?php endif; ?>

    <!-- Riepilogo Rapido -->
    <div class="studio-grid-cards">
        <div class="studio-card-stat highlight">
            <div class="title">Totale Documento</div>
            <div class="value"><?php echo number_format($invoice->totale_documento, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="studio-card-stat">
            <div class="title">Stato Fiscale</div>
            <div class="value" style="font-size: 20px;">
                <?php if ($invoice->stato === 'emessa'): ?>
                    <span class="studio-badge badge-issued">Emessa Ufficiale</span>
                <?php else: ?>
                    <span class="studio-badge badge-draft">Bozza / Proforma</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="studio-card-stat <?php echo ($invoice->stato_pagamento === 'pagata') ? 'success' : 'warning'; ?>">
            <div class="title">Stato Incasso</div>
            <div class="value" style="font-size: 20px;">
                <?php if ($invoice->stato_pagamento === 'pagata'): ?>
                    <span class="studio-badge badge-paid">Saldato</span>
                <?php else: ?>
                    <span class="studio-badge badge-unpaid">Da Pagare</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="studio-card-stat">
            <div class="title">Paziente Intestatario</div>
            <div class="value" style="font-size: 16px; font-weight: 600;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=view&id=' . $invoice->paziente_id)); ?>">
                    <?php echo esc_html($invoice->cognome . ' ' . $invoice->nome); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Anteprima Scheda Documento -->
    <div class="studio-panel">
        <h2>Dettaglio Prestazioni e Importi</h2>
        
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Descrizione Prestazione</th>
                    <th style="width: 10%; text-align: center;">Quantità</th>
                    <th style="width: 15%; text-align: right;">Prezzo Unitario</th>
                    <th style="width: 10%; text-align: center;">Sconto</th>
                    <th style="width: 15%; text-align: right;">Totale Riga</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($righe as $r): ?>
                    <tr>
                        <td><strong><?php echo esc_html($r->descrizione); ?></strong></td>
                        <td style="text-align: center;"><?php echo number_format($r->quantita, 1); ?></td>
                        <td style="text-align: right;"><?php echo number_format($r->prezzo_unitario, 2, ',', '.'); ?> &euro;</td>
                        <td style="text-align: center;"><?php echo $r->sconto_maggiorazione_perc != 0 ? number_format($r->sconto_maggiorazione_perc, 0) . '%' : '-'; ?></td>
                        <td style="text-align: right;"><strong><?php echo number_format($r->totale_riga, 2, ',', '.'); ?> &euro;</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totali Riepilogativi -->
        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <div class="invoice-totals-box" style="width: 350px;">
                <div class="row-tot">
                    <span>Imponibile:</span>
                    <strong><?php echo number_format($invoice->totale_imponibile, 2, ',', '.'); ?> &euro;</strong>
                </div>
                <?php if ($invoice->totale_cassa > 0): ?>
                    <div class="row-tot">
                        <span>Cassa Previdenza (<?php echo number_format($invoice->percentuale_cassa, 1); ?>%):</span>
                        <span><?php echo number_format($invoice->totale_cassa, 2, ',', '.'); ?> &euro;</span>
                    </div>
                <?php endif; ?>
                <?php if ($invoice->totale_iva > 0): ?>
                    <div class="row-tot">
                        <span>IVA (<?php echo number_format($invoice->percentuale_iva, 0); ?>%):</span>
                        <span><?php echo number_format($invoice->totale_iva, 2, ',', '.'); ?> &euro;</span>
                    </div>
                <?php endif; ?>
                <?php if ($invoice->marca_bollo > 0): ?>
                    <div class="row-tot">
                        <span>Marca da Bollo:</span>
                        <span><?php echo number_format($invoice->marca_bollo, 2, ',', '.'); ?> &euro;</span>
                    </div>
                <?php endif; ?>
                <?php if ($invoice->totale_ritenuta > 0): ?>
                    <div class="row-tot">
                        <span>Ritenuta d'acconto:</span>
                        <span>- <?php echo number_format($invoice->totale_ritenuta, 2, ',', '.'); ?> &euro;</span>
                    </div>
                <?php endif; ?>
                <div class="row-tot grand">
                    <span>TOTALE DOVUTO:</span>
                    <span><?php echo number_format($invoice->totale_documento, 2, ',', '.'); ?> &euro;</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Sezione Pagamento Rapido -->
    <div class="studio-panel">
        <h2>Stato di Pagamento e Registrazione Quietanza</h2>
        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div>
                <strong>Stato attuale:</strong>
                <?php if ($invoice->stato_pagamento === 'pagata'): ?>
                    <span style="color: #166534; font-weight: 700;">SALDATO</span> in data <?php echo date('d/m/Y', strtotime($invoice->data_pagamento)); ?> con <?php echo esc_html($invoice->metodo_pagamento); ?>
                <?php else: ?>
                    <span style="color: #dc2626; font-weight: 700;">DA SALDARE</span> (Dovuti: <?php echo number_format($invoice->totale_documento, 2, ',', '.'); ?> &euro;)
                <?php endif; ?>
            </div>
            <div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=edit&id=' . $invoice->id)); ?>" class="btn-studio btn-studio-secondary">Aggiorna o Cambia Modalità Pagamento</a>
            </div>
        </div>
    </div>
</div>

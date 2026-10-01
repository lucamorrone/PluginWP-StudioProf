<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Importazione Pazienti da CSV</h1>
            <p class="description">Carica un file CSV per importare anagrafiche in modo massivo con rilevamento intelligente dei dati.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti')); ?>" class="btn-studio btn-studio-secondary">&larr; Torna ai Pazienti</a>
        </div>
    </div>

    <div class="studio-panel" style="max-width: 750px;">
        <h2>Caricamento File CSV</h2>
        
        <form method="post" enctype="multipart/form-data" action="">
            <?php wp_nonce_field('studio_import_csv', 'studio_import_csv_nonce'); ?>

            <div class="studio-field" style="margin-bottom: 20px;">
                <label>Seleziona File CSV:</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" required style="padding: 10px; border: 1px dashed #94a3b8; border-radius: 6px;">
            </div>

            <div class="studio-field" style="margin-bottom: 20px;">
                <label>Separatore dei campi:</label>
                <select name="csv_delimiter" style="max-width: 200px;">
                    <option value=",">Virgola (,)</option>
                    <option value=";">Punto e virgola (;)</option>
                    <option value="\t">Tabulazione (TAB)</option>
                </select>
            </div>

            <div style="background: #f1f5f9; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 13px;">
                <strong>&#128161; Formato e Colonne Riconosciute Automaticamente:</strong><br>
                Il sistema riconosce sia i file esportati da Excel sia da altri gestionali medici. Colonne supportate nell'intestazione:
                <ul style="margin: 8px 0 0 20px; list-style-type: disc;">
                    <li><code>Cognome</code>, <code>Nome</code> (obbligatori)</li>
                    <li><code>CodiceFiscale</code> o <code>CF</code> (estrae in automatico data di nascita e sesso se mancanti)</li>
                    <li><code>Telefono</code> o <code>Cellulare</code>, <code>Email</code></li>
                    <li><code>Indirizzo</code>, <code>CAP</code>, <code>Citta</code>, <code>Provincia</code></li>
                    <li><code>Note</code></li>
                </ul>
            </div>

            <input type="submit" class="btn-studio btn-studio-primary" value="Avvia Importazione Massiva" style="padding: 10px 24px;">
        </form>
    </div>
</div>

<?php
if (!defined('ABSPATH')) exit;
$is_edit = !empty($patient);
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1><?php echo $is_edit ? 'Modifica Paziente' : 'Nuovo Paziente'; ?></h1>
            <p class="description">Inserisci i dati identificativi. Il codice fiscale calcolerà automaticamente sesso e data di nascita.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti')); ?>" class="btn-studio btn-studio-secondary">&larr; Torna all'elenco</a>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('studio_save_patient', 'studio_save_patient_nonce'); ?>
        <input type="hidden" name="patient_id" value="<?php echo $is_edit ? esc_attr($patient->id) : 0; ?>">

        <div class="studio-panel">
            <h2>Dati Anagrafici e Fiscali</h2>
            
            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Cognome *</label>
                    <input type="text" name="cognome" value="<?php echo $is_edit ? esc_attr($patient->cognome) : ''; ?>" required>
                </div>
                <div class="studio-field">
                    <label>Nome *</label>
                    <input type="text" name="nome" value="<?php echo $is_edit ? esc_attr($patient->nome) : ''; ?>" required>
                </div>
                <div class="studio-field">
                    <label>Codice Fiscale * (16 caratteri)</label>
                    <input type="text" name="codice_fiscale" id="studio_codice_fiscale" value="<?php echo $is_edit ? esc_attr($patient->codice_fiscale) : ''; ?>" maxlength="16" required style="text-transform: uppercase; font-family: monospace;">
                    <span id="cf_decode_indicator" style="display:none; color: #2563eb; font-size: 11px;"></span>
                </div>
            </div>

            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Data di Nascita</label>
                    <input type="date" name="data_nascita" id="studio_data_nascita" value="<?php echo $is_edit ? esc_attr($patient->data_nascita) : ''; ?>">
                    <span class="desc">Compilata automaticamente dal Codice Fiscale</span>
                </div>
                <div class="studio-field">
                    <label>Sesso</label>
                    <select name="sesso" id="studio_sesso">
                        <option value="">-- Seleziona --</option>
                        <option value="M" <?php selected($is_edit ? $patient->sesso : '', 'M'); ?>>Maschio (M)</option>
                        <option value="F" <?php selected($is_edit ? $patient->sesso : '', 'F'); ?>>Femmina (F)</option>
                    </select>
                </div>
                <div class="studio-field">
                    <label>Luogo di Nascita (Comune)</label>
                    <input type="text" name="luogo_nascita" value="<?php echo $is_edit ? esc_attr($patient->luogo_nascita) : ''; ?>">
                </div>
                <div class="studio-field">
                    <label>Provincia di Nascita (Sigla)</label>
                    <input type="text" name="provincia_nascita" maxlength="5" value="<?php echo $is_edit ? esc_attr($patient->provincia_nascita) : ''; ?>" style="text-transform: uppercase;">
                </div>
                <div class="studio-field">
                    <label>Stato di Nascita</label>
                    <input type="text" name="stato_nascita" value="<?php echo $is_edit ? esc_attr($patient->stato_nascita) : 'Italia'; ?>">
                </div>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Recapiti e Residenza</h2>
            <div class="studio-form-grid">
                <div class="studio-field">
                    <label>Cellulare / Telefono *</label>
                    <input type="text" name="telefono" value="<?php echo $is_edit ? esc_attr($patient->telefono) : ''; ?>" required>
                </div>
                <div class="studio-field">
                    <label>Email (per invio fatture e consensi)</label>
                    <input type="email" name="email" value="<?php echo $is_edit ? esc_attr($patient->email) : ''; ?>">
                </div>
            </div>

            <div class="studio-form-grid">
                <div class="studio-field" style="grid-column: span 2;">
                    <label>Indirizzo di Residenza (Via/Piazza e civico)</label>
                    <input type="text" name="indirizzo_residenza" value="<?php echo $is_edit ? esc_attr($patient->indirizzo_residenza) : ''; ?>">
                </div>
                <div class="studio-field">
                    <label>CAP</label>
                    <input type="text" name="cap_residenza" maxlength="10" value="<?php echo $is_edit ? esc_attr($patient->cap_residenza) : ''; ?>">
                </div>
                <div class="studio-field">
                    <label>Città di Residenza</label>
                    <input type="text" name="citta_residenza" value="<?php echo $is_edit ? esc_attr($patient->citta_residenza) : ''; ?>">
                </div>
                <div class="studio-field">
                    <label>Provincia Residenza (Sigla)</label>
                    <input type="text" name="provincia_residenza" maxlength="5" value="<?php echo $is_edit ? esc_attr($patient->provincia_residenza) : ''; ?>" style="text-transform: uppercase;">
                </div>
            </div>
        </div>

        <div class="studio-panel">
            <h2>Note Libere e Amministrative</h2>
            <div class="studio-field">
                <label>Note Generali:</label>
                <textarea name="note" rows="3"><?php echo $is_edit ? esc_textarea($patient->note) : ''; ?></textarea>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <input type="submit" class="btn-studio btn-studio-primary" value="<?php echo $is_edit ? 'Salva Modifiche Paziente' : 'Crea Scheda Paziente'; ?>" style="padding: 10px 24px; font-size: 15px;">
        </div>
    </form>
</div>

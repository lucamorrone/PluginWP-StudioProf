<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Dashboard Studio Professionale</h1>
            <p class="description">Panoramica dell'attività dello studio, pazienti registrati e stato della fatturazione.</p>
        </div>
        <form method="get" class="studio-year-filter"><input type="hidden" name="page" value="studio-professionale"><label for="dashboard_anno"><strong>Anno:</strong></label><select id="dashboard_anno" name="dashboard_anno" onchange="this.form.submit()"><?php foreach($anni_dashboard as $year_option):?><option value="<?php echo (int)$year_option;?>" <?php selected($anno_corrente,$year_option);?>><?php echo (int)$year_option;?></option><?php endforeach;?></select></form>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=new')); ?>" class="btn-studio btn-studio-primary">+ Nuovo Paziente</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=new')); ?>" class="btn-studio btn-studio-success">+ Nuova Fattura</a>
        </div>
    </div>

    <!-- Statistiche Principali -->
    <div class="studio-grid-cards">
        <div class="studio-card-stat highlight">
            <div class="title">Totale Pazienti</div>
            <div class="value"><?php echo esc_html($tot_pazienti); ?></div><div class="studio-stat-sub"><span>Maschi: <strong><?php echo (int)$tot_maschi;?></strong></span><span>Femmine: <strong><?php echo (int)$tot_femmine;?></strong></span></div>
        </div>
        <div class="studio-card-stat">
            <div class="title">Fatture Gestite</div>
            <div class="value"><?php echo esc_html($tot_fatture); ?></div><div class="studio-stat-sub"><span>Emesse: <strong><?php echo (int)$fatture_emesse;?></strong></span><span>In bozza: <strong><?php echo (int)$fatture_bozza;?></strong></span></div>
        </div>
        <div class="studio-card-stat success">
            <div class="title">Incassato Anno <?php echo esc_html($anno_corrente); ?></div>
            <div class="value"><?php echo number_format($incasso_anno, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="studio-card-stat warning">
            <div class="title">Da Incassare Anno <?php echo (int)$anno_corrente; ?></div>
            <div class="value"><?php echo number_format($da_incassare, 2, ',', '.'); ?> &euro;</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Ultime Fatture -->
        <div class="studio-panel">
            <h2>Ultime Fatture Registrate</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Numero/Bozza</th>
                        <th>Paziente</th>
                        <th>Data</th>
                        <th>Totale</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ultime_fatture)): ?>
                        <?php foreach ($ultime_fatture as $f): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=view&id=' . $f->id)); ?>" style="font-weight: 600;">
                                        <?php echo esc_html($f->codice_fattura ?: ('Bozza #' . $f->id)); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($f->cognome . ' ' . $f->nome); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($f->data_documento)); ?></td>
                                <td><strong><?php echo number_format($f->totale_documento, 2, ',', '.'); ?> &euro;</strong></td>
                                <td>
                                    <?php if ($f->stato === 'bozza'): ?>
                                        <span class="studio-badge badge-draft">Bozza</span>
                                    <?php elseif ($f->stato_pagamento === 'pagata'): ?>
                                        <span class="studio-badge badge-paid">Saldato</span>
                                    <?php else: ?>
                                        <span class="studio-badge badge-unpaid">Da pagare</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">Nessuna fattura registrata.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture')); ?>">Visualizza tutte le fatture &rarr;</a>
            </div>
        </div>

        <!-- Ultimi Pazienti -->
        <div class="studio-panel">
            <h2>Pazienti Registrati di Recente</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nominativo</th>
                        <th>Codice Fiscale</th>
                        <th>Recapito</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($ultimi_pazienti)): ?>
                        <?php foreach ($ultimi_pazienti as $p): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($p->cognome . ' ' . $p->nome); ?></strong>
                                </td>
                                <td><code><?php echo esc_html($p->codice_fiscale); ?></code></td>
                                <td><?php echo esc_html($p->telefono ?: $p->email); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=view&id=' . $p->id)); ?>" class="btn-studio btn-studio-secondary" style="padding: 2px 8px; font-size: 11px;">Cartella</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">Nessun paziente presente.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti')); ?>">Visualizza elenco completo &rarr;</a>
            </div>
        </div>
    </div>
</div>

<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Fatturazione e Contabilità</h1>
            <p class="description">Gestione fatture sanitarie, proforma/bozze, stati di pagamento e archiviazione documenti.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=new')); ?>" class="btn-studio btn-studio-success">+ Nuova Fattura</a>
        </div>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="studio-panel" style="padding: 14px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="studio-fatture">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cerca numero o paziente..." style="flex: 1; min-width: 200px;">
                
                <select name="stato">
                    <option value="">Tutti gli stati documento</option>
                    <option value="bozza" <?php selected($status, 'bozza'); ?>>Solo Bozze</option>
                    <option value="emessa" <?php selected($status, 'emessa'); ?>>Solo Emesse</option>
                </select>

                <select name="pagamento">
                    <option value="">Tutti i pagamenti</option>
                    <option value="da_pagare" <?php selected($payment_status, 'da_pagare'); ?>>Da Pagare</option>
                    <option value="pagata" <?php selected($payment_status, 'pagata'); ?>>Pagate</option>
                </select>

                <?php if (!empty($years)): ?>
                    <select name="anno">
                        <option value="">Tutti gli anni</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo esc_attr($y); ?>" <?php selected($year, $y); ?>><?php echo esc_html($y); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <input type="submit" class="btn-studio btn-studio-primary" value="Filtra">
                <?php if (!empty($search) || !empty($status) || !empty($payment_status) || !empty($year)): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture')); ?>" class="btn-studio btn-studio-secondary">Azzera</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="studio-panel" style="padding: 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;">Numero Fattura</th>
                    <th style="width: 12%;">Data</th>
                    <th style="width: 25%;">Paziente</th>
                    <th style="width: 12%;">Totale</th>
                    <th style="width: 12%;">Stato Fiscale</th>
                    <th style="width: 14%;">Stato Pagamento</th>
                    <th style="width: 10%; text-align: right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=view&id=' . $inv->id)); ?>" style="font-weight: 700;">
                                    <?php echo esc_html(!empty($inv->codice_fattura) ? $inv->codice_fattura : ('Bozza #' . $inv->id)); ?>
                                </a>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($inv->data_documento)); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=view&id=' . $inv->paziente_id)); ?>">
                                    <?php echo esc_html($inv->cognome . ' ' . $inv->nome); ?>
                                </a>
                                <div style="font-size: 11px; color: #64748b; font-family: monospace;"><?php echo esc_html($inv->codice_fiscale); ?></div>
                            </td>
                            <td><strong><?php echo number_format($inv->totale_documento, 2, ',', '.'); ?> &euro;</strong></td>
                            <td>
                                <?php if ($inv->stato === 'emessa'): ?>
                                    <span class="studio-badge badge-issued">Emessa</span>
                                <?php else: ?>
                                    <span class="studio-badge badge-draft">Bozza</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($inv->stato_pagamento === 'pagata'): ?>
                                    <span class="studio-badge badge-paid">Saldato (<?php echo date('d/m/Y', strtotime($inv->data_pagamento)); ?>)</span>
                                <?php else: ?>
                                    <span class="studio-badge badge-unpaid">Da Pagare</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=view&id=' . $inv->id)); ?>" class="button button-small" title="Visualizza Scheda">&#128065;</a>
                                <?php if ($inv->stato === 'bozza'): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-fatture&action=edit&id=' . $inv->id)); ?>" class="button button-small" title="Modifica">&#9998;</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 20px; text-align: center;">Nessuna fattura trovata con i filtri selezionati.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($total>$per_page):?><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(array('base'=>add_query_arg('paged','%#%'),'current'=>$page,'total'=>ceil($total/$per_page),'prev_text'=>'&laquo;','next_text'=>'&raquo;')));?></div><?php endif;?>

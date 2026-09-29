<?php
if (!defined('ABSPATH')) exit;
$notice = get_transient('studio_import_notice');
if ($notice) {
    delete_transient('studio_import_notice');
}
?>
<div class="wrap studio-wrap">
    <div class="studio-header">
        <div>
            <h1>Anagrafica Pazienti</h1>
            <p class="description">Elenco completo dei pazienti in cura, cartelle cliniche e contabilità associata.</p>
        </div>
        <div class="studio-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=import')); ?>" class="btn-studio btn-studio-secondary">&#128196; Importa CSV</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=new')); ?>" class="btn-studio btn-studio-primary">+ Nuovo Paziente</a>
        </div>
    </div>

    <?php if ($notice): ?>
        <div class="notice notice-info is-dismissible" style="margin-left: 0;">
            <p><?php echo esc_html($notice); ?></p>
        </div>
    <?php endif; ?>

    <!-- Ricerca -->
    <div class="studio-panel" style="padding: 14px;">
        <form method="get" action="">
            <input type="hidden" name="page" value="studio-pazienti">
            <div style="display: flex; gap: 10px;">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cerca per cognome, nome, CF, telefono o email..." style="flex: 1; padding: 6px 12px;">
                <input type="submit" class="btn-studio btn-studio-primary" value="Cerca">
                <?php if (!empty($search)): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti')); ?>" class="btn-studio btn-studio-secondary">Azzera</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="studio-panel" style="padding: 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Paziente</th>
                    <th style="width: 18%;">Codice Fiscale</th>
                    <th style="width: 12%;">Nascita</th>
                    <th style="width: 20%;">Recapiti</th>
                    <th style="width: 15%;">Comune Residenza</th>
                    <th style="width: 10%; text-align: right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $p): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=view&id=' . $p->id)); ?>" style="font-weight: 700; font-size: 14px;">
                                    <?php echo esc_html($p->cognome . ' ' . $p->nome); ?>
                                </a>
                                <?php if (!empty($p->sesso)): ?>
                                    <span style="color: #64748b; font-size: 11px;">(<?php echo esc_html($p->sesso); ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html($p->codice_fiscale); ?></code></td>
                            <td><?php echo $p->data_nascita ? date('d/m/Y', strtotime($p->data_nascita)) : '-'; ?></td>
                            <td>
                                <?php if (!empty($p->telefono)): ?><div>&#128222; <?php echo esc_html($p->telefono); ?></div><?php endif; ?>
                                <?php if (!empty($p->email)): ?><div>&#9993; <?php echo esc_html($p->email); ?></div><?php endif; ?>
                            </td>
                            <td><?php echo esc_html($p->citta_residenza . ($p->provincia_residenza ? ' (' . $p->provincia_residenza . ')' : '')); ?></td>
                            <td style="text-align: right;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=view&id=' . $p->id)); ?>" class="button button-small" title="Visualizza Cartella">&#128065;</a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=studio-pazienti&action=edit&id=' . $p->id)); ?>" class="button button-small" title="Modifica">&#9998;</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="padding: 20px; text-align: center;">Nessun paziente trovato. Inserisci il primo con il pulsante in alto a destra!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($total>$per_page):?><div class="tablenav-pages"><?php echo wp_kses_post(paginate_links(array('base'=>add_query_arg('paged','%#%'),'current'=>$page,'total'=>ceil($total/$per_page),'prev_text'=>'&laquo;','next_text'=>'&raquo;')));?></div><?php endif;?>

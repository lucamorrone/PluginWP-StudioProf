<?php
if (!defined('ABSPATH')) exit;
$tot_imponibile = 0;
$tot_cassa = 0;
$tot_iva = 0;
$tot_bollo = 0;
$tot_ritenuta = 0;
$tot_generale = 0;
$tot_pagato = 0;
$tot_da_pagare = 0;

foreach ($records as $r) {
    $tot_imponibile += $r->totale_imponibile;
    $tot_cassa += $r->totale_cassa;
    $tot_iva += $r->totale_iva;
    $tot_bollo += $r->marca_bollo;
    $tot_ritenuta += $r->totale_ritenuta;
    $tot_generale += $r->totale_documento;

    if ($r->stato_pagamento === 'pagata') {
        $tot_pagato += $r->totale_documento;
    } else {
        $tot_da_pagare += $r->totale_documento;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Report Contabile Periodico - <?php echo esc_html($studio['professionista']); ?></title>
    <style>
        @page { size: A4 landscape; margin: 15mm; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 15px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        table td {
            border: 1px solid #cbd5e1;
            padding: 6px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-boxes {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 6px;
            flex: 1;
        }
        .box-title { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px; }
        .box-val { font-size: 16px; font-weight: 800; color: #0f172a; }
        .print-btn {
            background: #2563eb;
            color: #fff;
            padding: 6px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Riepilogo Fiscale Fatturato - Studio Professionale</h1>
            <div><strong>Professionista:</strong> <?php echo esc_html($studio['professionista'] . ' (' . $studio['titolo'] . ')'); ?> | P.IVA: <?php echo esc_html($studio['partita_iva'] ?: $studio['codice_fiscale']); ?></div>
            <div><strong>Periodo:</strong> Anno <?php echo esc_html($anno); ?> - Rif: <?php echo strtoupper(esc_html($periodo)); ?></div>
        </div>
        <div class="no-print">
            <button class="print-btn" onclick="window.print()">Stampa / Salva PDF</button>
        </div>
    </div>

    <div class="summary-boxes">
        <div class="box">
            <div class="box-title">Documenti Emessi</div>
            <div class="box-val"><?php echo count($records); ?></div>
        </div>
        <div class="box">
            <div class="box-title">Totale Imponibile</div>
            <div class="box-val"><?php echo number_format($tot_imponibile, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="box">
            <div class="box-title">Cassa Previdenza</div>
            <div class="box-val"><?php echo number_format($tot_cassa, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="box">
            <div class="box-title">Bolli Applicati</div>
            <div class="box-val"><?php echo number_format($tot_bollo, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="box">
            <div class="box-title">Totale Generale</div>
            <div class="box-val" style="color: #2563eb;"><?php echo number_format($tot_generale, 2, ',', '.'); ?> &euro;</div>
        </div>
        <div class="box">
            <div class="box-title">Incassato Effettivo</div>
            <div class="box-val" style="color: #166534;"><?php echo number_format($tot_pagato, 2, ',', '.'); ?> &euro;</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 7%;">Numero</th>
                <th style="width: 7%;">Data</th>
                <th style="width: 18%;">Paziente</th>
                <th style="width: 14%;">Codice Fiscale</th>
                <th class="text-right" style="width: 9%;">Imponibile</th>
                <th class="text-right" style="width: 7%;">Cassa</th>
                <th class="text-right" style="width: 6%;">Bollo</th>
                <th class="text-right" style="width: 9%;">Totale</th>
                <th class="text-center" style="width: 9%;">Stato</th>
                <th style="width: 14%;">Pagamento</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($records)): ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><strong><?php echo esc_html($r->codice_fattura); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($r->data_documento)); ?></td>
                        <td><?php echo esc_html($r->cognome . ' ' . $r->nome); ?></td>
                        <td><code><?php echo esc_html($r->codice_fiscale); ?></code></td>
                        <td class="text-right"><?php echo number_format($r->totale_imponibile, 2, ',', '.'); ?> &euro;</td>
                        <td class="text-right"><?php echo number_format($r->totale_cassa, 2, ',', '.'); ?> &euro;</td>
                        <td class="text-right"><?php echo number_format($r->marca_bollo, 2, ',', '.'); ?> &euro;</td>
                        <td class="text-right"><strong><?php echo number_format($r->totale_documento, 2, ',', '.'); ?> &euro;</strong></td>
                        <td class="text-center">
                            <?php echo ($r->stato_pagamento === 'pagata') ? '<span style="color:#166534; font-weight:bold;">Saldato</span>' : '<span style="color:#dc2626; font-weight:bold;">Da pagare</span>'; ?>
                        </td>
                        <td>
                            <?php if ($r->data_pagamento): ?>
                                <?php echo date('d/m/Y', strtotime($r->data_pagamento)) . ' (' . esc_html($r->metodo_pagamento) . ')'; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="10" style="text-align: center; padding: 20px;">Nessuna fattura emessa per il periodo indicato.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

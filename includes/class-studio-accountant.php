<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Accountant {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'handle_export_csv'));
        add_action('admin_init', array($this, 'handle_export_zip'));
        add_action('admin_init', array($this, 'handle_export_summary_pdf'));
    }

    public function render_accountant_page() {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');

        $anno_corrente = intval(date('Y'));
        $anni_disponibili = $wpdb->get_col("SELECT DISTINCT anno FROM $t_fatture WHERE anno IS NOT NULL AND anno > 0 ORDER BY anno DESC");
        if (empty($anni_disponibili)) {
            $anni_disponibili = array($anno_corrente);
        }

        include STUDIO_PROF_PATH . 'templates/accountant-export.php';
    }

    private function get_export_query($anno, $periodo, $custom_from = '', $custom_to = '') {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');

        $where = "WHERE f.stato = 'emessa'";

        if ($periodo === 'q1') {
            $where .= $wpdb->prepare(" AND f.anno = %d AND MONTH(f.data_documento) BETWEEN 1 AND 3", $anno);
        } elseif ($periodo === 'q2') {
            $where .= $wpdb->prepare(" AND f.anno = %d AND MONTH(f.data_documento) BETWEEN 4 AND 6", $anno);
        } elseif ($periodo === 'q3') {
            $where .= $wpdb->prepare(" AND f.anno = %d AND MONTH(f.data_documento) BETWEEN 7 AND 9", $anno);
        } elseif ($periodo === 'q4') {
            $where .= $wpdb->prepare(" AND f.anno = %d AND MONTH(f.data_documento) BETWEEN 10 AND 12", $anno);
        } elseif (strpos($periodo, 'm_') === 0) {
            $mese = intval(substr($periodo, 2));
            $where .= $wpdb->prepare(" AND f.anno = %d AND MONTH(f.data_documento) = %d", $anno, $mese);
        } elseif ($periodo === 'custom' && !empty($custom_from) && !empty($custom_to)) {
            $where .= $wpdb->prepare(" AND f.data_documento BETWEEN %s AND %s", $custom_from, $custom_to);
        } else {
            // Intero anno
            $where .= $wpdb->prepare(" AND f.anno = %d", $anno);
        }

        $sql = "SELECT f.*, 
                       p.nome, p.cognome, p.codice_fiscale, p.sesso, p.data_nascita,
                       p.luogo_nascita, p.provincia_nascita, p.indirizzo_residenza,
                       p.cap_residenza, p.citta_residenza, p.provincia_residenza
                FROM $t_fatture f
                INNER JOIN $t_pazienti p ON f.paziente_id = p.id
                $where
                ORDER BY f.anno ASC, f.numero_fattura ASC";

        return $wpdb->get_results($sql);
    }

    public function handle_export_csv() {
        if (!isset($_POST['studio_export_accountant_csv'])) {
            return;
        }

        check_admin_referer('studio_export_accountant');

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_die(__('Accesso non autorizzato.', 'studio-professionale'));
        }

        $anno = intval($_POST['export_anno']);
        $periodo = sanitize_text_field($_POST['export_periodo']);
        $custom_from = isset($_POST['custom_from']) ? sanitize_text_field($_POST['custom_from']) : '';
        $custom_to = isset($_POST['custom_to']) ? sanitize_text_field($_POST['custom_to']) : '';

        $records = $this->get_export_query($anno, $periodo, $custom_from, $custom_to);

        $filename = 'Export_Commercialista_' . $anno . '_' . $periodo . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // BOM UTF-8 per compatibilità Excel
        fputs($out, "\xEF\xBB\xBF");

        fputcsv($out, array(
            'Numero Fattura',
            'Data Documento',
            'Anno',
            'Cognome Paziente',
            'Nome Paziente',
            'Codice Fiscale',
            'Data Nascita',
            'Luogo Nascita',
            'Provincia Nascita',
            'Indirizzo Residenza',
            'CAP',
            'Comune Residenza',
            'Provincia Residenza',
            'Imponibile (EUR)',
            'Cassa Previdenza (EUR)',
            'IVA (EUR)',
            'Bollo (EUR)',
            'Ritenuta (EUR)',
            'Totale Documento (EUR)',
            'Stato Pagamento',
            'Data Pagamento',
            'Metodo Pagamento'
        ), ';');

        foreach ($records as $r) {
            fputcsv($out, array(
                $r->codice_fattura,
                date('d/m/Y', strtotime($r->data_documento)),
                $r->anno,
                $r->cognome,
                $r->nome,
                $r->codice_fiscale,
                $r->data_nascita ? date('d/m/Y', strtotime($r->data_nascita)) : '',
                $r->luogo_nascita,
                $r->provincia_nascita,
                $r->indirizzo_residenza,
                $r->cap_residenza,
                $r->citta_residenza,
                $r->provincia_residenza,
                number_format($r->totale_imponibile, 2, ',', ''),
                number_format($r->totale_cassa, 2, ',', ''),
                number_format($r->totale_iva, 2, ',', ''),
                number_format($r->marca_bollo, 2, ',', ''),
                number_format($r->totale_ritenuta, 2, ',', ''),
                number_format($r->totale_documento, 2, ',', ''),
                $r->stato_pagamento === 'pagata' ? 'PAGATA' : 'DA PAGARE',
                $r->data_pagamento ? date('d/m/Y', strtotime($r->data_pagamento)) : '',
                $r->metodo_pagamento
            ), ';');
        }

        fclose($out);
        exit;
    }

    public function handle_export_zip() {
        if (!isset($_POST['studio_export_accountant_zip'])) {
            return;
        }

        check_admin_referer('studio_export_accountant');

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_die(__('Accesso non autorizzato.', 'studio-professionale'));
        }

        $anno = intval($_POST['export_anno']);
        $periodo = sanitize_text_field($_POST['export_periodo']);
        $custom_from = isset($_POST['custom_from']) ? sanitize_text_field($_POST['custom_from']) : '';
        $custom_to = isset($_POST['custom_to']) ? sanitize_text_field($_POST['custom_to']) : '';

        $records = $this->get_export_query($anno, $periodo, $custom_from, $custom_to);

        if (empty($records)) {
            wp_die(__('Nessuna fattura emessa trovata per il periodo selezionato.', 'studio-professionale'));
        }

        if (!class_exists('ZipArchive')) {
            wp_die(__('L\'estensione ZipArchive di PHP non è abilitata sul server.', 'studio-professionale'));
        }

        $zip = new ZipArchive();
        $zip_file = wp_tempnam('commercialista_') . '.zip';

        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die(__('Impossibile creare l\'archivio ZIP.', 'studio-professionale'));
        }

        $global_csv = "Numero Fattura;Data;Paziente;Codice Fiscale;Imponibile;Cassa;IVA;Bollo;Totale;Stato Pagamento;Data Pagamento\n";

        foreach ($records as $r) {
            // Assicura che il PDF/HTML esista
            $filepath = Studio_PDF::generate_invoice_pdf($r->id);
            if (file_exists($filepath)) {
                $doc_name = 'Fattura_' . str_replace('/', '-', $r->codice_fattura) . '_' . sanitize_title($r->cognome . '_' . $r->nome) . '.html';
                $zip->addFile($filepath, 'Fatture/' . $doc_name);
            }

            $global_csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                $r->codice_fattura,
                date('d/m/Y', strtotime($r->data_documento)),
                $r->cognome . ' ' . $r->nome,
                $r->codice_fiscale,
                number_format($r->totale_imponibile, 2, ',', ''),
                number_format($r->totale_cassa, 2, ',', ''),
                number_format($r->totale_iva, 2, ',', ''),
                number_format($r->marca_bollo, 2, ',', ''),
                number_format($r->totale_documento, 2, ',', ''),
                $r->stato_pagamento,
                $r->data_pagamento ? date('d/m/Y', strtotime($r->data_pagamento)) : ''
            );
        }

        $zip->addFromString('Riepilogo_Fiscale_' . $anno . '_' . $periodo . '.csv', "\xEF\xBB\xBF" . $global_csv);
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="Pacchetto_Commercialista_' . $anno . '_' . $periodo . '.zip"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        @unlink($zip_file);
        exit;
    }

    public function handle_export_summary_pdf() {
        if (!isset($_POST['studio_export_accountant_summary'])) {
            return;
        }

        check_admin_referer('studio_export_accountant');

        $anno = intval($_POST['export_anno']);
        $periodo = sanitize_text_field($_POST['export_periodo']);
        $custom_from = isset($_POST['custom_from']) ? sanitize_text_field($_POST['custom_from']) : '';
        $custom_to = isset($_POST['custom_to']) ? sanitize_text_field($_POST['custom_to']) : '';

        $records = $this->get_export_query($anno, $periodo, $custom_from, $custom_to);
        $studio = Studio_DB::get_studio_data();

        header('Content-Type: text/html; charset=utf-8');
        include STUDIO_PROF_PATH . 'templates/report-accountant-html.php';
        exit;
    }
}

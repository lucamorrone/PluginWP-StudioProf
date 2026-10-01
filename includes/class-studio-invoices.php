<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Invoices {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Actions
        add_action('admin_init', array($this, 'handle_invoice_save'));
        add_action('admin_init', array($this, 'handle_invoice_issue'));
        add_action('admin_init', array($this, 'handle_invoice_delete'));
        add_action('admin_init', array($this, 'handle_invoice_pdf_view'));
        add_action('admin_init', array($this, 'handle_invoice_email_send'));

        // AJAX
        add_action('wp_ajax_studio_update_payment_status', array($this, 'ajax_update_payment_status'));
    }

    public static function get_effective_stamp_duty($invoice, $studio = null) {
        if (empty($invoice->applica_marca_bollo)) return 0.00;
        if ($studio === null) $studio = Studio_DB::get_studio_data();
        $configured = isset($studio['marca_bollo']) ? round((float)$studio['marca_bollo'], 2) : 0.00;
        $stored = isset($invoice->marca_bollo) ? round((float)$invoice->marca_bollo, 2) : 0.00;
        if ($configured > 0) return $configured;
        if ($stored > 0) return $stored;
        return 2.00;
    }
    public static function sync_stamp_duty($invoice_id) {
        global $wpdb;
        $table=Studio_DB::table('fatture');
        $invoice=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$invoice_id));
        if(!$invoice) return false;
        $bollo=self::get_effective_stamp_duty($invoice);
        $total=max(0,round((float)$invoice->totale_imponibile+(float)$invoice->totale_cassa+(float)$invoice->totale_iva+$bollo-(float)$invoice->totale_ritenuta,2));
        $ok=$wpdb->update($table,array('marca_bollo'=>$bollo,'totale_documento'=>$total),array('id'=>$invoice_id),array('%f','%f'),array('%d'));
        return $ok!==false;
    }
    public function render_invoices_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'new' || $action === 'edit') {
            $this->render_invoice_form($invoice_id);
        } elseif ($action === 'view' && $invoice_id > 0) {
            $this->render_invoice_detail($invoice_id);
        } else {
            $this->render_invoices_list();
        }
    }

    public function render_invoices_list() {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');

        $status = isset($_GET['stato']) ? sanitize_text_field($_GET['stato']) : '';
        $payment_status = isset($_GET['pagamento']) ? sanitize_text_field($_GET['pagamento']) : '';
        $year = isset($_GET['anno']) ? intval($_GET['anno']) : 0;
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        if (!empty($status)) {
            $where .= $wpdb->prepare(" AND f.stato = %s", $status);
        }
        if (!empty($payment_status)) {
            $where .= $wpdb->prepare(" AND f.stato_pagamento = %s", $payment_status);
        }
        if ($year > 0) {
            $where .= $wpdb->prepare(" AND f.anno = %d", $year);
        }
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (f.codice_fattura LIKE %s OR p.nome LIKE %s OR p.cognome LIKE %s OR p.codice_fiscale LIKE %s)", $like, $like, $like, $like);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id $where");
        $sql = "SELECT f.*, p.nome, p.cognome, p.codice_fiscale 
                FROM $t_fatture f 
                LEFT JOIN $t_pazienti p ON f.paziente_id = p.id 
                $where 
                ORDER BY f.anno DESC, f.numero_fattura DESC, f.id DESC 
                LIMIT %d OFFSET %d";
        $invoices = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

        // Elenco anni per filtro
        $years = $wpdb->get_col("SELECT DISTINCT anno FROM $t_fatture WHERE anno IS NOT NULL AND anno > 0 ORDER BY anno DESC");

        include STUDIO_PROF_PATH . 'templates/invoices-list.php';
    }

    public function render_invoice_form($invoice_id = 0) {
        global $wpdb;
        $invoice = null;
        $righe = array();

        if ($invoice_id > 0) {
            $table = Studio_DB::table('fatture');
            $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $invoice_id));
            if ($invoice) {
                $table_righe = Studio_DB::table('fatture_righe');
                $righe = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_righe WHERE fattura_id = %d ORDER BY ordine ASC, id ASC", $invoice_id));
            }
        }

        $t_pazienti = Studio_DB::table('pazienti');
        $patients = $wpdb->get_results("SELECT id, cognome, nome, codice_fiscale FROM $t_pazienti ORDER BY cognome ASC, nome ASC");

        $studio = Studio_DB::get_studio_data();
        $prestazioni = Studio_DB::get_prestazioni();

        include STUDIO_PROF_PATH . 'templates/invoice-form.php';
    }

    public function render_invoice_detail($invoice_id) {
        global $wpdb;
        self::sync_stamp_duty($invoice_id);
        $t_fatture = Studio_DB::table('fatture');
        $t_pazienti = Studio_DB::table('pazienti');
        $t_righe = Studio_DB::table('fatture_righe');

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT f.*, p.nome, p.cognome, p.codice_fiscale, p.email, p.telefono, p.indirizzo_residenza, p.citta_residenza, p.cap_residenza, p.provincia_residenza FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id WHERE f.id = %d", $invoice_id));
        if (!$invoice) {
            wp_die(__('Fattura non trovata.', 'studio-professionale'));
        }

        $righe = $wpdb->get_results($wpdb->prepare("SELECT * FROM $t_righe WHERE fattura_id = %d ORDER BY ordine ASC, id ASC", $invoice_id));
        $studio = Studio_DB::get_studio_data();

        include STUDIO_PROF_PATH . 'templates/invoice-detail.php';
    }

    public function handle_invoice_save() {
        if (!isset($_POST['studio_save_invoice_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['studio_save_invoice_nonce'], 'studio_save_invoice')) {
            wp_die(__('Errore di sicurezza.', 'studio-professionale'));
        }

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_die(__('Permessi insufficienti.', 'studio-professionale'));
        }

        global $wpdb;
        $table_fatture = Studio_DB::table('fatture');
        $table_righe = Studio_DB::table('fatture_righe');

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        
        // Verifica se la fattura è già emessa e blocca modifiche non consentite
        $is_issued = false;
        if ($invoice_id > 0) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_fatture WHERE id = %d", $invoice_id));
            if ($existing && $existing->stato === 'emessa') {
                $is_issued = true;
            }
        }

        $paziente_id = intval($_POST['paziente_id']);
        $data_documento = sanitize_text_field($_POST['data_documento']);
        $valuta = 'EUR';
        $tipo_documento = isset($_POST['tipo_documento']) && $_POST['tipo_documento']==='non_sanitaria' ? 'non_sanitaria' : 'sanitaria';
        $metodo_pagamento = sanitize_text_field($_POST['metodo_pagamento']);
        $data_pagamento = !empty($_POST['data_pagamento']) ? sanitize_text_field($_POST['data_pagamento']) : null;
        $stato_pagamento = !empty($data_pagamento) ? 'pagata' : 'da_pagare';
        $note_documento = sanitize_textarea_field($_POST['note_documento']);

        // Righe e calcoli
        $descrizioni = isset($_POST['riga_descrizione']) ? array_map('sanitize_text_field', wp_unslash($_POST['riga_descrizione'])) : array();
        $quantita = isset($_POST['riga_quantita']) ? array_map('floatval', wp_unslash($_POST['riga_quantita'])) : array();
        $prezzi = isset($_POST['riga_prezzo']) ? array_map('floatval', wp_unslash($_POST['riga_prezzo'])) : array();
        $sconti = isset($_POST['riga_sconto']) ? array_map('floatval', wp_unslash($_POST['riga_sconto'])) : array();

        $totale_imponibile = 0.00;
        $righe_data = array();

        for ($i = 0; $i < count($descrizioni); $i++) {
            if (empty(trim($descrizioni[$i]))) continue;
            $q = isset($quantita[$i]) && $quantita[$i] > 0 ? $quantita[$i] : 1;
            $pu = isset($prezzi[$i]) ? max(0, $prezzi[$i]) : 0;
            $sc = isset($sconti[$i]) ? $sconti[$i] : 0;

            $subtot = $q * $pu;
            if ($sc != 0) {
                $subtot = $subtot - ($subtot * ($sc / 100));
            }
            $subtot = round($subtot, 2);
            $totale_imponibile += $subtot;

            $righe_data[] = array(
                'descrizione'              => $descrizioni[$i],
                'quantita'                 => $q,
                'prezzo_unitario'          => $pu,
                'sconto_maggiorazione_perc'=> $sc,
                'totale_riga'              => $subtot,
                'ordine'                   => $i
            );
        }

        // Parametri fiscali configurabili o passati
        $studio = Studio_DB::get_studio_data();
        $cassa_perc = isset($_POST['percentuale_cassa']) ? floatval($_POST['percentuale_cassa']) : $studio['cassa_perc'];
        $iva_perc = isset($_POST['percentuale_iva']) ? floatval($_POST['percentuale_iva']) : 0.00;
        $ritenuta_perc = isset($_POST['percentuale_ritenuta']) ? floatval($_POST['percentuale_ritenuta']) : $studio['ritenuta_perc'];

        $totale_cassa = round($totale_imponibile * ($cassa_perc / 100), 2);
        $base_iva = $totale_imponibile + $totale_cassa;
        $totale_iva = round($base_iva * ($iva_perc / 100), 2);
        $totale_ritenuta = round($totale_imponibile * ($ritenuta_perc / 100), 2);

        // Bollo: applicabile se supera la soglia di legge (77.47€) e previsto
        $applica_bollo = isset($_POST['applica_marca_bollo']) && (string)wp_unslash($_POST['applica_marca_bollo']) === '1' ? 1 : 0;
        $marca_bollo = 0.00;
        if ($applica_bollo) {
            $marca_bollo = $studio['marca_bollo'];
        }

        $totale_documento = ($totale_imponibile + $totale_cassa + $totale_iva + $marca_bollo) - $totale_ritenuta;
        $totale_documento = max(0, round($totale_documento, 2));

        if ($is_issued) {
            // Se già emessa, consenti solo aggiornamento stato pagamento e note
            $wpdb->update($table_fatture, array(
                'metodo_pagamento' => $metodo_pagamento,
                'data_pagamento'   => $data_pagamento,
                'stato_pagamento'  => $stato_pagamento,
                'note_documento'   => $note_documento
            ), array('id' => $invoice_id));

            // Rigenera PDF con il nuovo stato pagamento
            Studio_PDF::generate_invoice_pdf($invoice_id);

            wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'action' => 'view', 'id' => $invoice_id, 'msg' => 'payment_updated'), admin_url('admin.php')));
            exit;
        }

        $invoice_data = array(
            'paziente_id'          => $paziente_id,
            'stato'                => 'bozza',
            'tipo_documento'       => $tipo_documento,
            'data_documento'       => $data_documento,
            'valuta'               => $valuta,
            'totale_imponibile'    => $totale_imponibile,
            'percentuale_cassa'    => $cassa_perc,
            'totale_cassa'         => $totale_cassa,
            'percentuale_iva'      => $iva_perc,
            'totale_iva'           => $totale_iva,
            'percentuale_ritenuta' => $ritenuta_perc,
            'totale_ritenuta'      => $totale_ritenuta,
            'applica_marca_bollo' => $applica_bollo,
            'marca_bollo'          => $marca_bollo,
            'totale_documento'     => $totale_documento,
            'stato_pagamento'      => $stato_pagamento,
            'metodo_pagamento'     => $metodo_pagamento,
            'note_documento'       => $note_documento
        );

        if (!empty($data_pagamento)) {
            $invoice_data['data_pagamento'] = $data_pagamento;
        }

        if ($invoice_id > 0) {
            if (empty($data_pagamento)) {
                $wpdb->query($wpdb->prepare("UPDATE $table_fatture SET data_pagamento = NULL WHERE id = %d", $invoice_id));
            }
            $saved=$wpdb->update($table_fatture,$invoice_data,array('id'=>$invoice_id));
            if($saved===false) wp_die(__('Errore nel salvataggio della fattura: ','studio-professionale').esc_html($wpdb->last_error));
            $target_id = $invoice_id;
            // Elimina vecchie righe e reinserisce
            $wpdb->delete($table_righe, array('fattura_id' => $invoice_id));
        } else {
            $invoice_data['data_creazione'] = current_time('mysql');
            $wpdb->insert($table_fatture, $invoice_data);
            $target_id = $wpdb->insert_id;
        }

        // Inserimento righe
        foreach ($righe_data as $rd) {
            $rd['fattura_id'] = $target_id;
            $wpdb->insert($table_righe, $rd);
        }
        self::sync_stamp_duty($target_id);

        // Se l'utente ha premuto "Salva ed Emetti Subito"
        if (isset($_POST['submit_and_issue'])) {
            self::issue_invoice($target_id);
            wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'action' => 'view', 'id' => $target_id, 'msg' => 'issued'), admin_url('admin.php')));
            exit;
        }

        wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'action' => 'view', 'id' => $target_id, 'msg' => 'saved'), admin_url('admin.php')));
        exit;
    }

    public function handle_invoice_issue() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'issue' || !isset($_GET['id'])) {
            return;
        }

        $id = intval($_GET['id']);
        check_admin_referer('studio_issue_invoice_' . $id);

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_die(__('Permessi insufficienti.', 'studio-professionale'));
        }

        self::issue_invoice($id);

        wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'action' => 'view', 'id' => $id, 'msg' => 'issued'), admin_url('admin.php')));
        exit;
    }

    public static function issue_invoice($invoice_id) {
        global $wpdb;
        $table = Studio_DB::table('fatture');

        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $invoice_id));
        if (!$invoice || $invoice->stato === 'emessa') {
            return false;
        }

        self::sync_stamp_duty($invoice_id);
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $invoice_id));
        $anno = !empty($invoice->data_documento) ? intval(date('Y', strtotime($invoice->data_documento))) : intval(date('Y'));

        // Trova prossimo progressivo per quest'anno
        $max_num = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(numero_fattura) FROM $table WHERE anno = %d AND stato = 'emessa'",
            $anno
        ));
        $prossimo_numero = $max_num + 1;
        $codice_fattura = sprintf('%d/%d', $prossimo_numero, $anno);

        $wpdb->update($table, array(
            'stato'          => 'emessa',
            'numero_fattura' => $prossimo_numero,
            'anno'           => $anno,
            'codice_fattura' => $codice_fattura,
            'data_emissione' => current_time('mysql')
        ), array('id' => $invoice_id));

        // Genera subito il file PDF
        Studio_PDF::generate_invoice_pdf($invoice_id);

        return true;
    }

    public function handle_invoice_delete() {
        $is_post=isset($_POST['studio_delete_invoice']);$action=$is_post?'delete':sanitize_key($_GET['action']??'');$id=$is_post?intval($_POST['invoice_id']??0):intval($_GET['id']??0);if($action!=='delete'||!$id)return;

        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            wp_die(__('Solo gli amministratori possono cancellare una bozza.', 'studio-professionale'));
        }

        if($is_post)check_admin_referer('studio_delete_invoice_'.$id,'studio_delete_invoice_nonce');else check_admin_referer('studio_delete_invoice_'.$id);

        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');
        $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM $t_fatture WHERE id = %d", $id));

        if (!$invoice || $invoice->stato !== 'bozza' || !empty($invoice->numero_fattura)) { wp_die(__('È possibile eliminare esclusivamente una fattura non emessa nello stato Bozza.','studio-professionale')); }

        $wpdb->delete(Studio_DB::table('fatture_righe'), array('fattura_id' => $id));
        $wpdb->delete($t_fatture, array('id' => $id));
        Studio_Security::audit('eliminazione_bozza_fattura','fattura',$id);

        wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'msg' => 'deleted'), admin_url('admin.php')));
        exit;
    }

    public function handle_invoice_pdf_view() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'studio_invoice_pdf' || !isset($_GET['id'])) {
            return;
        }

        check_admin_referer('studio_invoice_pdf_' . intval($_GET['id']));

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_die(__('Accesso negato.', 'studio-professionale'));
        }

        $invoice_id = intval($_GET['id']);
        Studio_PDF::output_invoice_pdf($invoice_id);
        exit;
    }

    public function handle_invoice_email_send() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'send_email' || !isset($_GET['id'])) {
            return;
        }

        $id = intval($_GET['id']);
        check_admin_referer('studio_send_email_' . $id);

        if (!current_user_can(Studio_Roles::CAP_SEND_EMAILS)) {
            wp_die(__('Permessi insufficienti.', 'studio-professionale'));
        }

        $res = Studio_PDF::send_invoice_email($id);

        if (is_wp_error($res)) {
            $msg = 'email_error';
            $extra = '&err=' . urlencode($res->get_error_message());
        } else {
            $msg = 'email_sent';
            $extra = '';
        }

        wp_redirect(add_query_arg(array('page' => 'studio-fatture', 'action' => 'view', 'id' => $id, 'msg' => $msg), admin_url('admin.php')) . $extra);
        exit;
    }

    public function ajax_update_payment_status() {
        check_ajax_referer('studio_admin_nonce', 'nonce');

        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) {
            wp_send_json_error(array('message' => 'Permessi non sufficienti.'));
        }

        $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
        $metodo = isset($_POST['metodo_pagamento']) ? sanitize_text_field($_POST['metodo_pagamento']) : 'Bonifico';
        $data_pagamento = !empty($_POST['data_pagamento']) ? sanitize_text_field($_POST['data_pagamento']) : date('Y-m-d');
        $stato_pagamento = sanitize_text_field($_POST['stato_pagamento']);

        if (!$invoice_id) {
            wp_send_json_error(array('message' => 'Fattura non valida.'));
        }

        global $wpdb;
        $table = Studio_DB::table('fatture');

        if ($stato_pagamento === 'da_pagare') {
            $data_pagamento = null;
        }

        $wpdb->update($table, array(
            'stato_pagamento'  => $stato_pagamento,
            'metodo_pagamento' => $metodo,
            'data_pagamento'   => $data_pagamento
        ), array('id' => $invoice_id));

        self::sync_stamp_duty($invoice_id);
        // Rigenera PDF dopo la sincronizzazione del bollo e del totale
        Studio_PDF::generate_invoice_pdf($invoice_id);

        wp_send_json_success(array(
            'message' => 'Stato pagamento aggiornato con successo!',
            'stato'   => $stato_pagamento,
            'data'    => $data_pagamento ? date('d/m/Y', strtotime($data_pagamento)) : '-'
        ));
    }
}

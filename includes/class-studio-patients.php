<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Patients {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // AJAX Endpoints
        add_action('wp_ajax_studio_decode_cf', array($this, 'ajax_decode_cf'));
        add_action('wp_ajax_studio_save_patient_anamnesi', array($this, 'ajax_save_patient_anamnesi'));
        add_action('wp_ajax_studio_add_visita', array($this, 'ajax_add_visita'));
        add_action('wp_ajax_studio_delete_visita', array($this, 'ajax_delete_visita'));

        // Form Handlers
        add_action('admin_init', array($this, 'handle_patient_save'));
        add_action('admin_init', array($this, 'handle_patient_delete'));
        add_action('admin_init', array($this, 'handle_patient_csv_import'));
        add_action('admin_init', array($this, 'handle_privacy_pdf_download'));
        add_action('admin_init', array($this, 'handle_clinical_documents'));
    }

    public function render_patients_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action === 'view' && $patient_id > 0) {
            $this->render_patient_detail($patient_id);
        } elseif ($action === 'edit' || $action === 'new') {
            $this->render_patient_form($patient_id);
        } elseif ($action === 'import') {
            $this->render_patient_import();
        } else {
            $this->render_patients_list();
        }
    }

    public function render_patients_list() {
        global $wpdb;
        $table = Studio_DB::table('pazienti');

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $where = "WHERE 1=1";
        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (nome LIKE %s OR cognome LIKE %s OR codice_fiscale LIKE %s OR telefono LIKE %s OR email LIKE %s)", $like, $like, $like, $like, $like);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table $where");
        $patients = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table $where ORDER BY cognome ASC, nome ASC LIMIT %d OFFSET %d", $per_page, $offset));

        include STUDIO_PROF_PATH . 'templates/patients-list.php';
    }

    public function render_patient_form($patient_id = 0) {
        global $wpdb;
        $patient = null;
        if ($patient_id > 0) {
            $table = Studio_DB::table('pazienti');
            $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $patient_id));
        }

        include STUDIO_PROF_PATH . 'templates/patient-form.php';
    }

    public function render_patient_detail($patient_id) {
        global $wpdb;
        $table_pazienti = Studio_DB::table('pazienti');
        $table_visite = Studio_DB::table('visite');
        $table_fatture = Studio_DB::table('fatture');

        $patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pazienti WHERE id = %d", $patient_id));
        if (!$patient) {
            wp_die(__('Paziente non trovato.', 'studio-professionale'));
        }

        // Visite
        $visite = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_visite WHERE paziente_id = %d ORDER BY data_visita DESC",
            $patient_id
        ));

        // Fatture
        $fatture = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_fatture WHERE paziente_id = %d AND stato != 'annullata' ORDER BY id DESC",
            $patient_id
        ));

        $documenti = current_user_can(Studio_Roles::CAP_MANAGE_DOCUMENTS) ? $wpdb->get_results($wpdb->prepare('SELECT d.*,u.display_name FROM '.Studio_DB::table('documenti').' d LEFT JOIN '.$wpdb->users.' u ON u.ID=d.caricato_da WHERE d.paziente_id=%d ORDER BY d.id DESC',$patient_id)) : array();
        $email_logs = $wpdb->get_results($wpdb->prepare('SELECT l.*,u.display_name FROM '.Studio_DB::table('log').' l LEFT JOIN '.$wpdb->users.' u ON u.ID=l.user_id WHERE l.paziente_id=%d ORDER BY l.id DESC',$patient_id));
        $can_view_clinical = current_user_can(Studio_Roles::CAP_VIEW_CLINICAL);
        $issued_invoice_count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_fatture WHERE paziente_id=%d AND stato='emessa'",$patient_id));
        // Riepilogo contabile
        $totale_fatturato = 0.00;
        $totale_pagato = 0.00;
        foreach ($fatture as $f) {
            if ($f->stato === 'emessa') {
                $totale_fatturato += (float) $f->totale_documento;
                if ($f->stato_pagamento === 'pagata') {
                    $totale_pagato += (float) $f->totale_documento;
                }
            }
        }
        $saldo_residuo = $totale_fatturato - $totale_pagato;

        include STUDIO_PROF_PATH . 'templates/patient-detail.php';
    }

    public function render_patient_import() {
        include STUDIO_PROF_PATH . 'templates/patient-import.php';
    }

    public function handle_patient_save() {
        if (!isset($_POST['studio_save_patient_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['studio_save_patient_nonce'], 'studio_save_patient')) {
            wp_die(__('Verifica di sicurezza fallita.', 'studio-professionale'));
        }

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_die(__('Permessi insufficienti.', 'studio-professionale'));
        }

        global $wpdb;
        $table = Studio_DB::table('pazienti');

        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $nome = sanitize_text_field($_POST['nome']);
        $cognome = sanitize_text_field($_POST['cognome']);
        $cf = strtoupper(sanitize_text_field(str_replace(' ', '', $_POST['codice_fiscale'])));
        $sesso = sanitize_text_field($_POST['sesso']);
        $data_nascita = !empty($_POST['data_nascita']) ? sanitize_text_field($_POST['data_nascita']) : null;
        $luogo_nascita = sanitize_text_field($_POST['luogo_nascita']);
        $provincia_nascita = strtoupper(sanitize_text_field($_POST['provincia_nascita']));
        $stato_nascita = sanitize_text_field($_POST['stato_nascita']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $email = sanitize_email($_POST['email']);
        $indirizzo = sanitize_text_field($_POST['indirizzo_residenza']);
        $citta = sanitize_text_field($_POST['citta_residenza']);
        $cap = sanitize_text_field($_POST['cap_residenza']);
        $provincia = strtoupper(sanitize_text_field($_POST['provincia_residenza']));
        $note = sanitize_textarea_field($_POST['note']);
        if (!self::is_valid_cf($cf)) wp_die(__('Codice fiscale non valido. Verificare tutti i 16 caratteri e il carattere di controllo.','studio-professionale'));
        $duplicate_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE codice_fiscale=%s AND id<>%d LIMIT 1",$cf,$patient_id));
        if($duplicate_id) wp_die(__('Esiste già un paziente con lo stesso Codice Fiscale.','studio-professionale'));
        if(empty($telefono) || !preg_match('/^[+0-9][0-9 .()\/-]{5,24}$/',$telefono)) wp_die(__('Inserire un numero di telefono valido.','studio-professionale'));
        if(empty($email) || !is_email($email)) wp_die(__('Inserire un indirizzo email valido.','studio-professionale'));

        $data = array(
            'nome'               => $nome,
            'cognome'            => $cognome,
            'codice_fiscale'     => $cf,
            'sesso'              => $sesso,
            'luogo_nascita'      => $luogo_nascita,
            'provincia_nascita'  => $provincia_nascita,
            'stato_nascita'      => $stato_nascita,
            'telefono'           => $telefono,
            'email'              => $email,
            'indirizzo_residenza'=> $indirizzo,
            'citta_residenza'    => $citta,
            'cap_residenza'      => $cap,
            'provincia_residenza'=> $provincia,
            'note'               => $note
        );

        if (!empty($data_nascita)) {
            $data['data_nascita'] = $data_nascita;
        }

        if ($patient_id > 0) {
            $old_patient = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d",$patient_id),ARRAY_A);
            $data['data_aggiornamento'] = current_time('mysql');
            if (empty($data_nascita)) {
                $wpdb->query($wpdb->prepare("UPDATE $table SET data_nascita = NULL WHERE id = %d", $patient_id));
            }
            $wpdb->update($table, $data, array('id' => $patient_id));
            $target_id = $patient_id;
            Studio_Security::audit('modifica_anagrafica','paziente',$patient_id,array('prima'=>$old_patient,'dopo'=>$data));
        } else {
            $data['data_creazione'] = current_time('mysql');
            $data['data_aggiornamento'] = current_time('mysql');
            $wpdb->insert($table, $data);
            $target_id = $wpdb->insert_id;
            Studio_Security::audit('creazione_anagrafica','paziente',$target_id,array('dati'=>$data));
        }

        wp_redirect(add_query_arg(array(
            'page' => 'studio-pazienti',
            'action' => 'view',
            'id' => $target_id,
            'message' => 'saved'
        ), admin_url('admin.php')));
        exit;
    }

    public function handle_patient_delete() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'delete' || !isset($_GET['id'])) {
            return;
        }

        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            wp_die(__('Solo gli amministratori possono eliminare un paziente.', 'studio-professionale'));
        }

        $id = intval($_GET['id']);
        check_admin_referer('studio_delete_patient_' . $id);

        global $wpdb;
        $issued=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Studio_DB::table('fatture').' WHERE paziente_id=%d AND stato=%s',$id,'emessa'));
        if($issued>0) wp_die(__('Impossibile eliminare il paziente: sono presenti fatture emesse.','studio-professionale'));
        $draft_ids=$wpdb->get_col($wpdb->prepare('SELECT id FROM '.Studio_DB::table('fatture').' WHERE paziente_id=%d',$id));
        foreach($draft_ids as $draft_id)$wpdb->delete(Studio_DB::table('fatture_righe'),array('fattura_id'=>$draft_id));
        $wpdb->delete(Studio_DB::table('fatture'),array('paziente_id'=>$id));
        $wpdb->delete(Studio_DB::table('visite'),array('paziente_id'=>$id));
        $wpdb->delete(Studio_DB::table('documenti'),array('paziente_id'=>$id));
        $wpdb->delete(Studio_DB::table('pazienti'),array('id'=>$id));
        Studio_Security::audit('eliminazione_anagrafica','paziente',$id);

        wp_redirect(add_query_arg(array('page' => 'studio-pazienti', 'message' => 'deleted'), admin_url('admin.php')));
        exit;
    }

    public function handle_patient_csv_import() {
        if (!isset($_POST['studio_import_csv_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['studio_import_csv_nonce'], 'studio_import_csv')) {
            wp_die(__('Errore di sicurezza.', 'studio-professionale'));
        }

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_die(__('Permessi insufficienti.', 'studio-professionale'));
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            wp_die(__('Carica un file CSV valido.', 'studio-professionale'));
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            wp_die(__('Impossibile aprire il file caricato.', 'studio-professionale'));
        }

        global $wpdb;
        $table = Studio_DB::table('pazienti');
        $delimiter = sanitize_text_field($_POST['csv_delimiter']);
        if (empty($delimiter)) $delimiter = ',';

        // Legge prima riga come intestazione
        $headers = fgetcsv($handle, 4096, $delimiter);
        if (!$headers) {
            fclose($handle);
            wp_die(__('File CSV vuoto o malformato.', 'studio-professionale'));
        }

        // Normalizza headers
        $clean_headers = array_map(function($h) {
            return strtolower(trim(str_replace(array(' ', '_', '-'), '', $h)));
        }, $headers);

        $inserted = 0;
        $errors = array();
        $row_idx = 1;

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== FALSE) {
            $row_idx++;
            $row_data = array_combine($clean_headers, array_pad($row, count($clean_headers), ''));
            
            $nome = isset($row_data['nome']) ? sanitize_text_field($row_data['nome']) : '';
            $cognome = isset($row_data['cognome']) ? sanitize_text_field($row_data['cognome']) : '';
            $cf = isset($row_data['codicefiscale']) ? strtoupper(sanitize_text_field(str_replace(' ', '', $row_data['codicefiscale']))) : '';
            if (empty($cf) && isset($row_data['cf'])) {
                $cf = strtoupper(sanitize_text_field(str_replace(' ', '', $row_data['cf'])));
            }

            if (empty($nome) || empty($cognome)) {
                $errors[] = "Riga $row_idx: Nome o cognome mancante.";
                continue;
            }

            // Decodifica CF automatica se data di nascita assente
            $data_nascita = isset($row_data['datanascita']) ? sanitize_text_field($row_data['datanascita']) : null;
            $sesso = isset($row_data['sesso']) ? strtoupper(sanitize_text_field($row_data['sesso'])) : '';
            if (!empty($cf) && strlen($cf) === 16 && empty($data_nascita)) {
                $decoded = self::calculate_from_cf($cf);
                if ($decoded) {
                    $data_nascita = $decoded['data_nascita'];
                    if (empty($sesso)) $sesso = $decoded['sesso'];
                }
            }

            $csv_patient_data = array(
                'nome'                => $nome,
                'cognome'             => $cognome,
                'codice_fiscale'      => $cf,
                'sesso'               => $sesso,
                'luogo_nascita'       => isset($row_data['luogonascita']) ? sanitize_text_field($row_data['luogonascita']) : '',
                'provincia_nascita'   => isset($row_data['provincianascita']) ? strtoupper(sanitize_text_field($row_data['provincianascita'])) : '',
                'stato_nascita'       => isset($row_data['statonascita']) ? sanitize_text_field($row_data['statonascita']) : 'Italia',
                'telefono'            => isset($row_data['telefono']) ? sanitize_text_field($row_data['telefono']) : (isset($row_data['cellulare']) ? sanitize_text_field($row_data['cellulare']) : ''),
                'email'               => isset($row_data['email']) ? sanitize_email($row_data['email']) : '',
                'indirizzo_residenza' => isset($row_data['indirizzo']) ? sanitize_text_field($row_data['indirizzo']) : '',
                'citta_residenza'     => isset($row_data['citta']) ? sanitize_text_field($row_data['citta']) : '',
                'cap_residenza'       => isset($row_data['cap']) ? sanitize_text_field($row_data['cap']) : '',
                'provincia_residenza' => isset($row_data['provincia']) ? strtoupper(sanitize_text_field($row_data['provincia'])) : '',
                'note'                => isset($row_data['note']) ? sanitize_textarea_field($row_data['note']) : '',
                'data_creazione'      => current_time('mysql'),
                'data_aggiornamento'  => current_time('mysql')
            );
            if (!empty($data_nascita)) {
                $csv_patient_data['data_nascita'] = $data_nascita;
            }

            $wpdb->insert($table, $csv_patient_data);

            if ($wpdb->insert_id) {
                $inserted++;
            }
        }
        fclose($handle);

        $msg = "Importati con successo $inserted pazienti.";
        if (!empty($errors)) {
            $msg .= " Errori riscontrati in " . count($errors) . " righe.";
        }

        set_transient('studio_import_notice', $msg, 60);
        wp_redirect(add_query_arg(array('page' => 'studio-pazienti', 'import_done' => '1'), admin_url('admin.php')));
        exit;
    }

    public function handle_privacy_pdf_download() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'studio_privacy_pdf' || !isset($_GET['id'])) {
            return;
        }

        check_admin_referer('studio_privacy_pdf_' . intval($_GET['id']));

        if (!current_user_can(Studio_Roles::CAP_VIEW_PATIENTS)) {
            wp_die(__('Accesso negato.', 'studio-professionale'));
        }

        $patient_id = intval($_GET['id']);
        global $wpdb;
        $wpdb->update(Studio_DB::table('pazienti'),array('consenso_privacy_generato'=>1,'consenso_privacy_data'=>current_time('mysql'),'consenso_privacy_utente'=>get_current_user_id()),array('id'=>$patient_id));
        Studio_Security::audit('genera_consenso_privacy','paziente',$patient_id);
        Studio_PDF::output_privacy_pdf($patient_id);
        exit;
    }

    public function handle_clinical_documents(){
        if (!isset($_GET['page']) || $_GET['page']!=='studio-pazienti') return;
        $patient_id=intval($_REQUEST['patient_id']??0); if(!$patient_id)return;
        if(isset($_POST['studio_upload_document'])) Studio_Documents::upload($patient_id);
        if(isset($_GET['document_action'],$_GET['document_id'])){ $id=intval($_GET['document_id']); if($_GET['document_action']==='delete')Studio_Documents::delete($id,$patient_id); if($_GET['document_action']==='download')Studio_Documents::download($id,$patient_id); }
        if(isset($_POST['studio_upload_document']) || isset($_GET['document_action'])){wp_safe_redirect(admin_url('admin.php?page=studio-pazienti&action=view&id='.$patient_id));exit;}
    }

    public function ajax_decode_cf() {
        check_ajax_referer('studio_admin_nonce', 'nonce');

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_send_json_error(array('message' => 'Permessi non sufficienti.'));
        }

        $cf = isset($_POST['cf']) ? strtoupper(sanitize_text_field(str_replace(' ', '', $_POST['cf']))) : '';
        if (!self::is_valid_cf($cf)) {
            wp_send_json_error(array('message' => 'Codice fiscale non valido.'));
        }

        $res = self::calculate_from_cf($cf);
        if ($res) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error(array('message' => 'Impossibile estrarre data o sesso dal codice fiscale inserito.'));
        }
    }

    public static function is_valid_cf($cf){
        $cf=strtoupper(trim($cf));if(!preg_match('/^[A-Z0-9]{16}$/',$cf))return false;
        $odd=array('0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23);
        $sum=0;for($i=0;$i<15;$i++){$c=$cf[$i];$sum+=($i%2===0)?$odd[$c]:(ctype_digit($c)?intval($c):ord($c)-65);}return chr(65+($sum%26))===$cf[15];
    }
    public static function calculate_from_cf($cf) {
        $cf = strtoupper(trim($cf));
        if (strlen($cf) !== 16) {
            return false;
        }

        // Anno: pos 6-7 (0-based)
        $year_sub = substr($cf, 6, 2);
        // Mese: pos 8
        $month_code = substr($cf, 8, 1);
        // Giorno: pos 9-10
        $day_val = intval(substr($cf, 9, 2));

        $months = array(
            'A' => '01', 'B' => '02', 'C' => '03', 'D' => '04', 'E' => '05', 'H' => '06',
            'L' => '07', 'M' => '08', 'P' => '09', 'R' => '10', 'S' => '11', 'T' => '12'
        );

        if (!isset($months[$month_code])) {
            return false;
        }

        $sesso = 'M';
        if ($day_val > 40) {
            $sesso = 'F';
            $day_val -= 40;
        }

        if ($day_val < 1 || $day_val > 31) {
            return false;
        }

        $day_str = str_pad($day_val, 2, '0', STR_PAD_LEFT);
        $month_str = $months[$month_code];

        // Anno di nascita (stima del secolo in base all'anno attuale)
        $cur_year = intval(date('Y'));
        $cur_century = intval(substr(date('Y'), 0, 2)) * 100;
        $two_digit_cur = intval(date('y'));

        $year_int = intval($year_sub);
        if ($year_int <= $two_digit_cur) {
            $full_year = $cur_century + $year_int;
        } else {
            $full_year = ($cur_century - 100) + $year_int;
        }

        $data_nascita = sprintf('%04d-%02d-%02d', $full_year, intval($month_str), intval($day_str));

        return array(
            'data_nascita' => $data_nascita,
            'sesso'        => $sesso,
            'giorno'       => $day_str,
            'mese'         => $month_str,
            'anno'         => $full_year
        );
    }

    public function ajax_save_patient_anamnesi() {
        check_ajax_referer('studio_admin_nonce', 'nonce');

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_send_json_error(array('message' => 'Permessi non sufficienti.'));
        }

        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $anamnesi = isset($_POST['anamnesi']) ? wp_kses_post($_POST['anamnesi']) : '';

        if (!$patient_id) {
            wp_send_json_error(array('message' => 'ID Paziente non valido.'));
        }

        global $wpdb;
        $old_anamnesi=$wpdb->get_var($wpdb->prepare('SELECT anamnesi FROM '.Studio_DB::table('pazienti').' WHERE id=%d',$patient_id));
        $wpdb->update(
            Studio_DB::table('pazienti'),
            array('anamnesi' => $anamnesi),
            array('id' => $patient_id)
        );

        Studio_Security::audit('modifica_anamnesi','paziente',$patient_id,array('prima'=>$old_anamnesi,'dopo'=>$anamnesi));
        wp_send_json_success(array('message' => 'Anamnesi aggiornata con successo!'));
    }

    public function ajax_add_visita() {
        check_ajax_referer('studio_admin_nonce', 'nonce');

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_send_json_error(array('message' => 'Permessi non sufficienti.'));
        }

        $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
        $data_visita = isset($_POST['data_visita']) ? sanitize_text_field($_POST['data_visita']) : date('Y-m-d H:i');
        $durata = isset($_POST['durata_minuti']) ? intval($_POST['durata_minuti']) : 60;
        $tipo = isset($_POST['tipo_seduta']) ? sanitize_text_field($_POST['tipo_seduta']) : 'Colloquio clinico';
        $note = isset($_POST['note']) ? wp_kses_post($_POST['note']) : '';

        if (!$patient_id) {
            wp_send_json_error(array('message' => 'ID Paziente non valido.'));
        }

        global $wpdb;
        $table = Studio_DB::table('visite');
        $wpdb->insert($table, array(
            'paziente_id'    => $patient_id,
            'data_visita'    => $data_visita,
            'durata_minuti'  => $durata,
            'tipo_seduta'    => $tipo,
            'note'           => $note,
            'completata'     => 1,
            'data_creazione' => current_time('mysql')
        ));

        $insert_id = $wpdb->insert_id;
        Studio_Security::audit('aggiunta_visita','paziente',$patient_id,array('visita_id'=>$insert_id,'data'=>$data_visita,'tipo'=>$tipo));

        wp_send_json_success(array(
            'message' => 'Visita registrata con successo!',
            'visita'  => array(
                'id'          => $insert_id,
                'data_visita' => date('d/m/Y H:i', strtotime($data_visita)),
                'tipo'        => esc_html($tipo),
                'durata'      => $durata,
                'note'        => nl2br(esc_html($note))
            )
        ));
    }

    public function ajax_delete_visita() {
        check_ajax_referer('studio_admin_nonce', 'nonce');

        if (!current_user_can(Studio_Roles::CAP_EDIT_PATIENTS)) {
            wp_send_json_error(array('message' => 'Permessi non sufficienti.'));
        }

        $id = isset($_POST['visita_id']) ? intval($_POST['visita_id']) : 0;
        if (!$id) {
            wp_send_json_error(array('message' => 'ID visita non valido.'));
        }

        global $wpdb;
        $old=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.Studio_DB::table('visite').' WHERE id=%d',$id),ARRAY_A);
        $wpdb->delete(Studio_DB::table('visite'), array('id' => $id));
        Studio_Security::audit('eliminazione_visita','visita',$id,array('prima'=>$old));
        wp_send_json_success(array('message' => 'Seduta eliminata.'));
    }
}

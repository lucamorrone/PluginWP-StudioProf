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
        add_action('admin_init', array($this, 'handle_email_report'));
        add_action('admin_init', array($this, 'handle_accountant_email_save'));
    }

    public function render_accountant_page() {
        global $wpdb;
        $t_fatture = Studio_DB::table('fatture');

        $anno_corrente = intval(date('Y'));
        $anni_disponibili = $wpdb->get_col("SELECT DISTINCT anno FROM $t_fatture WHERE anno IS NOT NULL AND anno > 0 ORDER BY anno DESC");
        if (empty($anni_disponibili)) { $anni_disponibili=array($anno_corrente); }
        $commercialista_logs=$wpdb->get_results("SELECT l.*,u.display_name FROM ".Studio_DB::table('log')." l LEFT JOIN {$wpdb->users} u ON u.ID=l.user_id WHERE l.tipo='invio_report_commercialista' ORDER BY l.id DESC LIMIT 100");

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

        $records=$wpdb->get_results($sql);
        foreach($records as $record){
            if(class_exists('Studio_Invoices')) Studio_Invoices::sync_stamp_duty($record->id);
        }
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
                $doc_name = 'Fattura_' . str_replace('/', '-', $r->codice_fattura) . '_' . sanitize_title($r->cognome . '_' . $r->nome) . '.pdf';
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
        if (!isset($_POST['studio_export_accountant_summary'])) return;
        check_admin_referer('studio_export_accountant');
        if (!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES)) wp_die(__('Accesso non autorizzato.','studio-professionale'));
        $anno=intval($_POST['export_anno']);$periodo=sanitize_text_field($_POST['export_periodo']);$from=sanitize_text_field($_POST['custom_from']??'');$to=sanitize_text_field($_POST['custom_to']??'');$records=$this->get_export_query($anno,$periodo,$from,$to);if(!$records)wp_die(__('Nessuna fattura emessa nel periodo selezionato.','studio-professionale'));
        $file=$this->build_report_pdf($records,$anno,$this->period_label($periodo,$anno,$from,$to));header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="Report_Fatture_'.sanitize_file_name($this->period_label($periodo,$anno,$from,$to)).'.pdf"');readfile($file);@unlink($file);exit;
    }
    private function period_label($periodo,$anno,$from='',$to=''){
        $labels=array('year'=>'Anno intero','q1'=>'1° trimestre','q2'=>'2° trimestre','q3'=>'3° trimestre','q4'=>'4° trimestre');
        $months=array(1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre');
        if(strpos($periodo,'m_')===0)return ($months[intval(substr($periodo,2))]??$periodo).' '.$anno;
        if($periodo==='custom')return 'dal '.date_i18n('d/m/Y',strtotime($from)).' al '.date_i18n('d/m/Y',strtotime($to));
        return ($labels[$periodo]??'Anno intero').' '.$anno;
    }
    private function build_printable_report_html($records,$anno,$periodo){$studio=Studio_DB::get_studio_data();ob_start();include STUDIO_PROF_PATH.'templates/report-accountant-html.php';return ob_get_clean();}
    private function build_report_pdf($records,$anno,$periodo){$studio=Studio_DB::get_studio_data();$pdf=new Studio_PDF_Engine('landscape');$tot=array('count'=>count($records),'imp'=>0,'cassa'=>0,'iva'=>0,'bollo'=>0,'tot'=>0);foreach($records as $r){$tot['imp']+=(float)$r->totale_imponibile;$tot['cassa']+=(float)$r->totale_cassa;$tot['iva']+=(float)$r->totale_iva;$tot['bollo']+=(float)$r->marca_bollo;$tot['tot']+=(float)$r->totale_documento;}$page=1;$renderHeader=function($first)use($pdf,$studio,$periodo,$tot,&$page){$pdf->text(20,25,'Riepilogo Fiscale Fatturato - Studio Professionale',14,true,'#000000');$pdf->text(20,43,'Professionista: '.$studio['professionista'].' | P.IVA: '.$studio['partita_iva'],8,false,'#000000');$pdf->text(20,59,'Periodo: '.$periodo.' | Pagina '.$page,8,true,'#000000');$y=76;if($first){$labs=array('Documenti'=>$tot['count'],'Imponibile'=>number_format($tot['imp'],2,',','.').' EUR','Cassa'=>number_format($tot['cassa'],2,',','.').' EUR','IVA'=>number_format($tot['iva'],2,',','.').' EUR','Bolli'=>number_format($tot['bollo'],2,',','.').' EUR','Totale'=>number_format($tot['tot'],2,',','.').' EUR');$x=20;foreach($labs as $l=>$v){$pdf->rect($x,$y,128,45,false);$pdf->text($x+5,$y+15,$l,7,true,'#000000');$pdf->text($x+5,$y+34,$v,9,true,'#000000');$x+=132;}$y=132;}$cols=array(array(15,48,'Numero'),array(63,45,'Data'),array(108,105,'Paziente'),array(213,90,'Codice Fiscale'),array(303,70,'Imponibile'),array(373,55,'Cassa'),array(428,50,'Bollo'),array(478,55,'IVA'),array(533,70,'Totale'),array(603,65,'Stato'),array(668,159,'Pagamento'));foreach($cols as $c){$pdf->rect($c[0],$y,$c[1],22,false);$pdf->text($c[0]+3,$y+15,$c[2],6.4,true,'#000000');}return array($cols,$y+22);};list($cols,$y)=$renderHeader(true);foreach($records as $r){if($y>555){$pdf->add_page();$page++;list($cols,$y)=$renderHeader(false);}$vals=array($r->codice_fattura,date_i18n('d/m/Y',strtotime($r->data_documento)),$r->cognome.' '.$r->nome,$r->codice_fiscale,number_format($r->totale_imponibile,2,',','.').' EUR',number_format($r->totale_cassa,2,',','.').' EUR',number_format($r->marca_bollo,2,',','.').' EUR',number_format($r->totale_iva,2,',','.').' EUR',number_format($r->totale_documento,2,',','.').' EUR',$r->stato_pagamento==='pagata'?'Saldato':'Da pagare',$r->data_pagamento?date_i18n('d/m/Y',strtotime($r->data_pagamento)).' '.$r->metodo_pagamento:'-');foreach($cols as $i=>$c){$pdf->rect($c[0],$y,$c[1],21,false);$v=$vals[$i];$mx=max(5,(int)($c[1]/4.3));if(strlen($v)>$mx)$v=substr($v,0,$mx-2).'..';$pdf->text($c[0]+3,$y+14,$v,6.2,false,'#000000');}$y+=21;}$file=wp_tempnam('report-fatture-').'.pdf';file_put_contents($file,$pdf->output());return $file;}
    public function handle_accountant_email_save(){if(!isset($_POST['studio_accountant_email_save']))return;check_admin_referer('studio_export_accountant');if(!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO))wp_die('Solo un amministratore può modificare l’email del commercialista.');Studio_DB::update_setting('studio_commercialista_email',sanitize_email($_POST['commercialista_email']));wp_safe_redirect(admin_url('admin.php?page=studio-commercialista&email_saved=1'));exit;}
    public function handle_email_report(){
        if(!isset($_POST['studio_export_accountant_email']))return;check_admin_referer('studio_export_accountant');if(!current_user_can(Studio_Roles::CAP_MANAGE_INVOICES))wp_die('Accesso negato');
        global $wpdb;$anno=intval($_POST['export_anno']);$periodo=sanitize_text_field($_POST['export_periodo']);$from=sanitize_text_field($_POST['custom_from']??'');$to=sanitize_text_field($_POST['custom_to']??'');$records=$this->get_export_query($anno,$periodo,$from,$to);if(!$records)wp_die('Nessuna fattura emessa nel periodo selezionato.');
        $email=sanitize_email(Studio_DB::get_setting('studio_commercialista_email'));if(!is_email($email))wp_die('Configura prima un indirizzo email valido del commercialista.');$label=$this->period_label($periodo,$anno,$from,$to);$file=$this->build_report_pdf($records,$anno,$label);$studio=Studio_DB::get_studio_data();$count=count($records);$subject='Report Fatture - '.$label;$body="Gentile Professionista,\n\ntrasmettiamo in allegato il report PDF delle fatture emesse relativo al periodo: {$label}.\n\nNumero totale di fatture emesse incluse nel report: {$count}.\n\nCordiali saluti,\n{$studio['professionista']}";$ok=wp_mail($email,$subject,$body,array('From: '.$studio['professionista'].' <'.$studio['email'].'>'),array($file));@unlink($file);if(!$ok)wp_die('Invio non riuscito. Verifica FluentSMTP.');
        $details=array('destinatario'=>$email,'oggetto'=>$subject,'periodo'=>$label,'fatture'=>$count,'formato'=>'PDF');$wpdb->insert(Studio_DB::table('log'),array('user_id'=>get_current_user_id(),'tipo'=>'invio_report_commercialista','dettagli'=>wp_json_encode($details),'data_evento'=>current_time('mysql')));Studio_Security::audit('invio_report_commercialista','report',0,$details);wp_safe_redirect(admin_url('admin.php?page=studio-commercialista&report_sent=1'));exit;
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_studio_settings_save'));
        add_action('admin_init', array($this, 'handle_db_repair'));
        add_action('admin_notices', array($this, 'check_db_notices'));
    }

    public function check_db_notices() {
        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            return;
        }

        $db_error = get_transient('studio_db_error');

        if (!Studio_DB::all_tables_exist()) {
            $repair_url = wp_nonce_url(admin_url('admin.php?page=studio-impostazioni&action=repair_db'), 'studio_repair_db');
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>[Studio Professionale] Attenzione:</strong> Una o più tabelle nel database non sono ancora state create. ';
            echo '<a href="' . esc_url($repair_url) . '" class="button button-primary" style="margin-left: 10px;">Crea / Ripristina Tabelle Ora</a>';
            if ($db_error) {
                echo '<br><code style="color: #b91c1c; display:block; margin-top: 5px;">Dettaglio errore MySQL: ' . esc_html($db_error) . '</code>';
            }
            echo '</p></div>';
        } elseif ($db_error) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>[Studio Professionale] Nota DB:</strong> <code>' . esc_html($db_error) . '</code></p>';
            echo '</div>';
        }
    }

    public function handle_db_repair() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'repair_db') {
            return;
        }

        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            wp_die(__('Accesso negato.', 'studio-professionale'));
        }

        check_admin_referer('studio_repair_db');

        Studio_Activator::create_tables();
        Studio_Activator::create_upload_folders();
        Studio_Roles::add_roles();

        wp_redirect(add_query_arg(array('page' => 'studio-impostazioni', 'db_repaired' => '1'), admin_url('admin.php')));
        exit;
    }

    public function register_menus() {
        // Menu principale: Studio Professionale
        add_menu_page(
            __('Studio Professionale', 'studio-professionale'),
            __('Studio', 'studio-professionale'),
            Studio_Roles::CAP_VIEW_PATIENTS,
            'studio-professionale',
            array($this, 'render_dashboard'),
            'dashicons-clipboard',
            25
        );

        // Sotto-menu: Dashboard / Panoramica
        add_submenu_page(
            'studio-professionale',
            __('Dashboard', 'studio-professionale'),
            __('Dashboard', 'studio-professionale'),
            Studio_Roles::CAP_VIEW_PATIENTS,
            'studio-professionale',
            array($this, 'render_dashboard')
        );

        // Sotto-menu: Anagrafica Pazienti
        add_submenu_page(
            'studio-professionale',
            __('Anagrafica Pazienti', 'studio-professionale'),
            __('Pazienti', 'studio-professionale'),
            Studio_Roles::CAP_VIEW_PATIENTS,
            'studio-pazienti',
            array(Studio_Patients::get_instance(), 'render_patients_page')
        );

        // Sotto-menu: Fatturazione
        add_submenu_page(
            'studio-professionale',
            __('Fatture e Contabilità', 'studio-professionale'),
            __('Fatture', 'studio-professionale'),
            Studio_Roles::CAP_MANAGE_INVOICES,
            'studio-fatture',
            array(Studio_Invoices::get_instance(), 'render_invoices_page')
        );

        // Sotto-menu: Commercialista
        add_submenu_page(
            'studio-professionale',
            __('Esportazione Commercialista', 'studio-professionale'),
            __('Commercialista', 'studio-professionale'),
            Studio_Roles::CAP_MANAGE_INVOICES,
            'studio-commercialista',
            array(Studio_Accountant::get_instance(), 'render_accountant_page')
        );

        // Sotto-menu: Anagrafica Studio / Impostazioni
        add_submenu_page(
            'studio-professionale',
            __('Anagrafica Studio', 'studio-professionale'),
            __('Anagrafica Studio', 'studio-professionale'),
            Studio_Roles::CAP_MANAGE_STUDIO,
            'studio-impostazioni',
            array($this, 'render_studio_settings_page')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'studio-') === false) {
            return;
        }

        wp_enqueue_style(
            'studio-admin-css',
            STUDIO_PROF_URL . 'assets/css/studio-admin.css',
            array(),
            STUDIO_PROF_VERSION
        );

        wp_enqueue_script(
            'studio-admin-js',
            STUDIO_PROF_URL . 'assets/js/studio-admin.js',
            array('jquery'),
            STUDIO_PROF_VERSION,
            true
        );

        wp_localize_script('studio-admin-js', 'studioParams', array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('studio_admin_nonce'),
            'i18n'      => array(
                'confirmDelete' => __('Sei sicuro di voler procedere con l\'eliminazione?', 'studio-professionale'),
                'confirmIssue'  => __('Sei sicuro di voler emettere la fattura? L\'emissione assegnerà il numero progressivo definitivo e bloccherà la modifica dei dati fiscali.', 'studio-professionale'),
                'calculating'   => __('Calcolo in corso...', 'studio-professionale'),
            )
        ));
    }

    public function render_dashboard() {
        global $wpdb;
        $t_pazienti = Studio_DB::table('pazienti');
        $t_fatture = Studio_DB::table('fatture');
        $t_visite = Studio_DB::table('visite');

        $tot_pazienti = (int) $wpdb->get_var("SELECT COUNT(*) FROM $t_pazienti");
        $tot_maschi=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t_pazienti WHERE UPPER(sesso)='M'");$tot_femmine=(int)$wpdb->get_var("SELECT COUNT(*) FROM $t_pazienti WHERE UPPER(sesso)='F'");
        $anno_corrente = isset($_GET['dashboard_anno']) ? intval($_GET['dashboard_anno']) : intval(date('Y'));
        $anni_dashboard = $wpdb->get_col("SELECT DISTINCT anno FROM $t_fatture WHERE anno IS NOT NULL AND anno > 0 ORDER BY anno DESC");
        if (empty($anni_dashboard)) $anni_dashboard = array(intval(date('Y')));
        $tot_fatture = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t_fatture WHERE stato != 'annullata' AND ((anno = %d) OR (anno IS NULL AND YEAR(data_documento) = %d))",$anno_corrente,$anno_corrente));
        $fatture_emesse=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t_fatture WHERE stato='emessa' AND ((anno=%d) OR (anno IS NULL AND YEAR(data_documento)=%d))",$anno_corrente,$anno_corrente));$fatture_bozza=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t_fatture WHERE stato='bozza' AND ((anno=%d) OR (anno IS NULL AND YEAR(data_documento)=%d))",$anno_corrente,$anno_corrente));
        $incasso_anno = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(totale_documento) FROM $t_fatture WHERE stato = 'emessa' AND stato_pagamento = 'pagata' AND anno = %d",
            $anno_corrente
        ));
        
        $da_incassare = (float) $wpdb->get_var(
            "SELECT SUM(totale_documento) FROM $t_fatture WHERE stato = 'emessa' AND stato_pagamento = 'da_pagare' AND anno = " . intval($anno_corrente)
        );

        $ultime_fatture = $wpdb->get_results(
            "SELECT f.*, p.nome, p.cognome FROM $t_fatture f LEFT JOIN $t_pazienti p ON f.paziente_id = p.id ORDER BY f.id DESC LIMIT 5"
        );

        $ultimi_pazienti = $wpdb->get_results(
            "SELECT * FROM $t_pazienti ORDER BY id DESC LIMIT 5"
        );

        include STUDIO_PROF_PATH . 'templates/dashboard.php';
    }

    public function handle_studio_settings_save() {
        if (!isset($_POST['studio_settings_submit'])) {
            return;
        }

        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            wp_die(__('Non hai i permessi per eseguire questa azione.', 'studio-professionale'));
        }

        check_admin_referer('studio_settings_verify');

        $fields = array(
            'studio_denominazione'        => 'sanitize_text_field',
            'studio_professionista'       => 'sanitize_text_field',
            'studio_titolo'               => 'sanitize_text_field',
            'studio_piva'                 => 'sanitize_text_field',
            'studio_cf'                   => 'sanitize_text_field',
            'studio_indirizzo'            => 'sanitize_text_field',
            'studio_cap'                  => 'sanitize_text_field',
            'studio_citta'                => 'sanitize_text_field',
            'studio_provincia'            => 'sanitize_text_field',
            'studio_telefono'             => 'sanitize_text_field',
            'studio_email'                => 'sanitize_email',
            'studio_pec'                  => 'sanitize_email',
            'studio_sito_web'             => 'esc_url_raw',
            'studio_iban'                 => 'sanitize_text_field',
            'studio_banca'                => 'sanitize_text_field',
            'studio_bic_swift'            => 'sanitize_text_field',
            'studio_cassa_previdenza_nome'=> 'sanitize_text_field',
            'studio_cassa_perc'           => 'sanitize_text_field',
            'studio_marca_bollo_valore'   => 'sanitize_text_field',
            'studio_marca_bollo_soglia'   => 'sanitize_text_field',
            'studio_rivalsa_inps_perc'    => 'sanitize_text_field',
            'studio_ritenuta_perc'        => 'sanitize_text_field',
            'studio_testo_privacy'        => 'sanitize_textarea_field',
            'studio_note_legali'          => 'sanitize_textarea_field',
        );

        foreach ($fields as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                $val = call_user_func($sanitizer, wp_unslash($_POST[$key]));
                Studio_DB::update_setting($key, $val);
            }
        }

        // Gestione prestazioni configurabili
        if (isset($_POST['prestazioni_nome']) && is_array($_POST['prestazioni_nome'])) {
            $prestazioni = array();
            $nomi = array_map('sanitize_text_field', wp_unslash($_POST['prestazioni_nome']));
            $prezzi = array_map('floatval', wp_unslash($_POST['prestazioni_prezzo']));

            for ($i = 0; $i < count($nomi); $i++) {
                if (!empty(trim($nomi[$i]))) {
                    $prestazioni[] = array(
                        'nome'   => trim($nomi[$i]),
                        'prezzo' => isset($prezzi[$i]) ? max(0, $prezzi[$i]) : 0.00
                    );
                }
            }
            Studio_DB::update_setting('studio_prestazioni', $prestazioni);
        }

        wp_redirect(add_query_arg(array('page' => 'studio-impostazioni', 'updated' => 'true'), admin_url('admin.php')));
        exit;
    }

    public function render_studio_settings_page() {
        if (!current_user_can(Studio_Roles::CAP_MANAGE_STUDIO)) {
            wp_die(__('Accesso negato.', 'studio-professionale'));
        }

        $tables_status = Studio_DB::check_tables_status();
        $studio = Studio_DB::get_studio_data();
        $prestazioni = Studio_DB::get_prestazioni();
        include STUDIO_PROF_PATH . 'templates/studio-settings.php';
    }
}

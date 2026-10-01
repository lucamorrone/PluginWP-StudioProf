<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Activator {

    const DB_VERSION = '2.2.7';

    public static function activate() {
        self::create_tables();
        Studio_Roles::add_roles();
        self::create_upload_folders();
        update_option('studio_prof_db_version', self::DB_VERSION);
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public static function check_and_create_tables_if_missing() {
        global $wpdb;
        $table_pazienti = $wpdb->prefix . 'studio_pazienti';
        
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_pazienti));
        $installed_ver = get_option('studio_prof_db_version');

        if (!$exists || $installed_ver !== self::DB_VERSION) {
            self::create_tables();
            self::create_upload_folders();
            Studio_Roles::add_roles();
            update_option('studio_prof_db_version', self::DB_VERSION);
        }
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Tabella Pazienti (Compatibile MySQL 5.0+, SENZA DEFAULT CURRENT_TIMESTAMP su datetime)
        $table_pazienti = $wpdb->prefix . 'studio_pazienti';
        $sql_pazienti = "CREATE TABLE $table_pazienti (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nome varchar(100) NOT NULL,
            cognome varchar(100) NOT NULL,
            codice_fiscale varchar(16) NOT NULL,
            sesso varchar(1) DEFAULT '',
            data_nascita date DEFAULT NULL,
            luogo_nascita varchar(100) DEFAULT '',
            provincia_nascita varchar(5) DEFAULT '',
            stato_nascita varchar(60) DEFAULT 'Italia',
            telefono varchar(30) DEFAULT '',
            email varchar(100) DEFAULT '',
            indirizzo_residenza varchar(255) DEFAULT '',
            citta_residenza varchar(100) DEFAULT '',
            cap_residenza varchar(10) DEFAULT '',
            provincia_residenza varchar(5) DEFAULT '',
            anamnesi longtext,
            note text,
            consenso_privacy_generato tinyint(1) DEFAULT 0,
            consenso_privacy_data datetime DEFAULT NULL,
            consenso_privacy_utente bigint(20) unsigned DEFAULT NULL,
            data_creazione datetime DEFAULT NULL,
            data_aggiornamento datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY codice_fiscale (codice_fiscale),
            KEY cognome_nome (cognome, nome)
        ) $charset_collate;";

        // 2. Tabella Visite / Colloqui
        $table_visite = $wpdb->prefix . 'studio_visite';
        $sql_visite = "CREATE TABLE $table_visite (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            paziente_id bigint(20) unsigned NOT NULL,
            data_visita datetime NOT NULL,
            durata_minuti int(11) DEFAULT 60,
            tipo_seduta varchar(100) DEFAULT 'Colloquio clinico',
            note longtext,
            completata tinyint(1) DEFAULT 1,
            data_creazione datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY paziente_id (paziente_id)
        ) $charset_collate;";

        // 3. Tabella Fatture
        $table_fatture = $wpdb->prefix . 'studio_fatture';
        $sql_fatture = "CREATE TABLE $table_fatture (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            numero_fattura int(11) DEFAULT NULL,
            anno int(4) DEFAULT NULL,
            codice_fattura varchar(50) DEFAULT '',
            paziente_id bigint(20) unsigned NOT NULL,
            stato varchar(20) NOT NULL DEFAULT 'bozza',
            tipo_documento varchar(30) NOT NULL DEFAULT 'sanitaria',
            data_documento date NOT NULL,
            valuta varchar(3) DEFAULT 'EUR',
            totale_imponibile decimal(10,2) NOT NULL DEFAULT 0.00,
            percentuale_cassa decimal(5,2) DEFAULT 0.00,
            totale_cassa decimal(10,2) DEFAULT 0.00,
            percentuale_iva decimal(5,2) DEFAULT 0.00,
            totale_iva decimal(10,2) DEFAULT 0.00,
            percentuale_ritenuta decimal(5,2) DEFAULT 0.00,
            totale_ritenuta decimal(10,2) DEFAULT 0.00,
            applica_marca_bollo tinyint(1) NOT NULL DEFAULT 0,
            marca_bollo decimal(10,2) DEFAULT 0.00,
            totale_documento decimal(10,2) NOT NULL DEFAULT 0.00,
            stato_pagamento varchar(20) DEFAULT 'da_pagare',
            metodo_pagamento varchar(50) DEFAULT '',
            data_pagamento date DEFAULT NULL,
            note_pagamento varchar(255) DEFAULT '',
            note_documento text,
            pdf_path varchar(255) DEFAULT '',
            pdf_url varchar(255) DEFAULT '',
            data_emissione datetime DEFAULT NULL,
            data_creazione datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY paziente_id (paziente_id),
            KEY anno_numero (anno, numero_fattura)
        ) $charset_collate;";

        // 4. Tabella Righe Fattura
        $table_righe = $wpdb->prefix . 'studio_fatture_righe';
        $sql_righe = "CREATE TABLE $table_righe (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            fattura_id bigint(20) unsigned NOT NULL,
            descrizione varchar(255) NOT NULL,
            quantita decimal(10,2) NOT NULL DEFAULT 1.00,
            prezzo_unitario decimal(10,2) NOT NULL DEFAULT 0.00,
            sconto_maggiorazione_perc decimal(5,2) DEFAULT 0.00,
            totale_riga decimal(10,2) NOT NULL DEFAULT 0.00,
            ordine int(11) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY fattura_id (fattura_id)
        ) $charset_collate;";

        // 5. Tabella Impostazioni
        $table_impostazioni = $wpdb->prefix . 'studio_impostazioni';
        $sql_impostazioni = "CREATE TABLE $table_impostazioni (
            chiave varchar(100) NOT NULL,
            valore longtext,
            PRIMARY KEY  (chiave)
        ) $charset_collate;";


        $table_documenti = $wpdb->prefix . 'studio_documenti';
        $sql_documenti = "CREATE TABLE $table_documenti (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,paziente_id bigint(20) unsigned NOT NULL,nome_file varchar(255) NOT NULL,percorso varchar(500) NOT NULL,mime varchar(100) DEFAULT 'application/pdf',descrizione varchar(255) DEFAULT '',caricato_da bigint(20) unsigned DEFAULT NULL,data_caricamento datetime NOT NULL,PRIMARY KEY (id),KEY paziente_id (paziente_id)) $charset_collate;";
        $table_log = $wpdb->prefix . 'studio_log';
        $sql_log = "CREATE TABLE $table_log (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,paziente_id bigint(20) unsigned DEFAULT NULL,fattura_id bigint(20) unsigned DEFAULT NULL,user_id bigint(20) unsigned DEFAULT NULL,tipo varchar(60) NOT NULL,dettagli longtext,data_evento datetime NOT NULL,PRIMARY KEY (id),KEY paziente_id (paziente_id),KEY fattura_id (fattura_id),KEY data_evento (data_evento)) $charset_collate;";
        $table_audit = $wpdb->prefix . 'studio_audit';
        $sql_audit = "CREATE TABLE $table_audit (id bigint(20) unsigned NOT NULL AUTO_INCREMENT,user_id bigint(20) unsigned DEFAULT NULL,azione varchar(80) NOT NULL,oggetto varchar(80) DEFAULT '',oggetto_id bigint(20) unsigned DEFAULT NULL,dettagli longtext,ip_hash varchar(64) DEFAULT '',data_evento datetime NOT NULL,PRIMARY KEY (id),KEY oggetto_id (oggetto_id),KEY data_evento (data_evento)) $charset_collate;";

        $errors = array();

        // 1. Eseguiamo con dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_pazienti);
        dbDelta($sql_visite);
        dbDelta($sql_fatture);
        dbDelta($sql_righe);
        dbDelta($sql_impostazioni);
        dbDelta($sql_documenti);
        dbDelta($sql_log);
        dbDelta($sql_audit);

        // 2. Fallback diretto con CREATE TABLE IF NOT EXISTS per MySQL 5.0+
        $tables = array(
            'pazienti'     => str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql_pazienti),
            'visite'       => str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql_visite),
            'fatture'      => str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql_fatture),
            'fatture_righe'=> str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql_righe),
            'documenti'=>str_replace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$sql_documenti),
            'log'=>str_replace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$sql_log),
            'audit'=>str_replace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$sql_audit),
            'impostazioni' => str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql_impostazioni),
        );

        foreach ($tables as $name => $query) {
            $wpdb->query($query);
            if (!empty($wpdb->last_error)) {
                $errors[] = "Errore tabella $name: " . $wpdb->last_error;
            }
        }

        if (!empty($errors)) {
            set_transient('studio_db_error', implode(' | ', $errors), 120);
        } else {
            delete_transient('studio_db_error');
        }
    }

    public static function create_upload_folders() {
        $upload_dir = wp_upload_dir();
        $studio_dir = $upload_dir['basedir'] . '/studio-professionale-docs';
        if (!file_exists($studio_dir)) {
            wp_mkdir_p($studio_dir);
            file_put_contents($studio_dir . '/index.php', '<?php // Silence is golden.');
            file_put_contents($studio_dir . '/.htaccess', "Options -Indexes\n");
        }
    }
}

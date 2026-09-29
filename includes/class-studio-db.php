<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_DB {

    public static function table($name) {
        global $wpdb;
        return $wpdb->prefix . 'studio_' . $name;
    }

    public static function get_setting($key, $default = '') {
        global $wpdb;
        $table = self::table('impostazioni');
        $val = $wpdb->get_var($wpdb->prepare("SELECT valore FROM $table WHERE chiave = %s", $key));
        if ($val === null) {
            return $default;
        }
        $unserialized = maybe_unserialize($val);
        return $unserialized !== false ? $unserialized : $val;
    }

    public static function update_setting($key, $value) {
        global $wpdb;
        $table = self::table('impostazioni');
        $val = maybe_serialize($value);
        return $wpdb->replace($table, array(
            'chiave' => $key,
            'valore' => $val
        ), array('%s', '%s'));
    }

    public static function get_studio_data() {
        return array(
            'denominazione'    => self::get_setting('studio_denominazione', 'Studio Professionale'),
            'professionista'   => self::get_setting('studio_professionista', 'Dott. Mario Rossi'),
            'titolo'           => self::get_setting('studio_titolo', 'Psicologo - Psicoterapeuta'),
            'partita_iva'      => self::get_setting('studio_piva', ''),
            'codice_fiscale'   => self::get_setting('studio_cf', ''),
            'indirizzo'        => self::get_setting('studio_indirizzo', ''),
            'cap'              => self::get_setting('studio_cap', ''),
            'citta'            => self::get_setting('studio_citta', ''),
            'provincia'        => self::get_setting('studio_provincia', ''),
            'telefono'         => self::get_setting('studio_telefono', ''),
            'email'            => self::get_setting('studio_email', get_option('admin_email')),
            'pec'              => self::get_setting('studio_pec', ''),
            'sito_web'         => self::get_setting('studio_sito_web', get_site_url()),
            'iban'             => self::get_setting('studio_iban', ''),
            'banca'            => self::get_setting('studio_banca', ''),
            'bic_swift'        => self::get_setting('studio_bic_swift', ''),
            'cassa_previdenza' => self::get_setting('studio_cassa_previdenza_nome', 'ENPAP (2%)'),
            'cassa_perc'       => floatval(self::get_setting('studio_cassa_perc', '2.00')),
            'marca_bollo'      => floatval(self::get_setting('studio_marca_bollo_valore', '2.00')),
            'marca_bollo_soglia'=> floatval(self::get_setting('studio_marca_bollo_soglia', '77.47')),
            'rivalsa_inps_perc'=> floatval(self::get_setting('studio_rivalsa_inps_perc', '0.00')),
            'ritenuta_perc'    => floatval(self::get_setting('studio_ritenuta_perc', '0.00')),
            'testo_privacy'    => self::get_setting('studio_testo_privacy', self::default_privacy_text()),
            'commercialista_email'=> self::get_setting('studio_commercialista_email',''),
            'note_legali'      => self::get_setting('studio_note_legali', 'Operazione effettuata ai sensi dell\'art. 1, commi da 54 a 89 della Legge n. 190/2014 - Regime forfettario. Prestazione sanitaria esente IVA ex art. 10, n. 18, D.P.R. 633/72.'),
        );
    }

    public static function get_prestazioni() {
        $preset = array(
            array('nome' => 'Colloquio clinico / Seduta psicoterapia', 'prezzo' => 70.00),
            array('nome' => 'Seduta di terapia di coppia', 'prezzo' => 90.00),
            array('nome' => 'Consulenza psicologica online', 'prezzo' => 70.00),
            array('nome' => 'Valutazione psicodiagnostica', 'prezzo' => 150.00),
            array('nome' => 'Relazione clinica / perizia', 'prezzo' => 200.00),
        );
        return self::get_setting('studio_prestazioni', $preset);
    }

    public static function check_tables_status() {
        global $wpdb;
        $tables = array(
            'pazienti'      => self::table('pazienti'),
            'visite'        => self::table('visite'),
            'fatture'       => self::table('fatture'),
            'fatture_righe' => self::table('fatture_righe'),
            'impostazioni'  => self::table('impostazioni'),
            'documenti'=>self::table('documenti'),'log'=>self::table('log'),'audit'=>self::table('audit'),
        );
        $status = array();
        foreach ($tables as $key => $table_name) {
            $found = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            $status[$key] = ($found === $table_name);
        }
        return $status;
    }

    public static function all_tables_exist() {
        $status = self::check_tables_status();
        foreach ($status as $exists) {
            if (!$exists) {
                return false;
            }
        }
        return true;
    }

    public static function default_privacy_text() {
        return "INFORMATIVA E CONSENSO AL TRATTAMENTO DEI DATI PERSONALI E SANITARI\n\nAi sensi del Regolamento UE 2016/679 (GDPR) e della normativa italiana vigente, i dati personali e le informazioni relative allo stato di salute da Lei forniti formeranno oggetto di trattamento nel rispetto dei principi di correttezza, liceità, trasparenza e di tutela della Sua riservatezza e dei Suoi diritti.\n\nFinalità del trattamento:\n1. Esecuzione della prestazione professionale sanitaria richiesta e finalità di diagnosi/cura.\n2. Adempimento degli obblighi fiscali, contabili ed amministrativi di legge (es. trasmissione Sistema Tessera Sanitaria).\n\nI dati saranno conservati secondo i tempi previsti dal codice deontologico e dalla legge tributaria.\n\nIl/La sottoscritto/a dichiara di aver ricevuto e compreso l'informativa sopra riportata e presta il proprio consenso informato al trattamento dei dati comuni e sanitari per le finalità indicate.";
    }
}

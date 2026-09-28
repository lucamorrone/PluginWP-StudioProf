<?php
/**
 * Plugin Name: Studio Professionale - Gestione Pazienti & Fatturazione
 * Plugin URI: https://studio-professionale.local
 * Description: Sistema avanzato per la gestione anagrafica pazienti, storico visite/anamnesi, fatturazione con calcolo automatico, pagamenti, esportazione commercialista e generazione PDF.
 * Version: 2.0.0
 * Author: Antigravity
 * Text Domain: studio-professionale
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Sicurezza: accesso diretto non consentito
}

// Costanti del Plugin
define('STUDIO_PROF_VERSION', '2.0.0');
define('STUDIO_PROF_PATH', plugin_dir_path(__FILE__));
define('STUDIO_PROF_URL', plugin_dir_url(__FILE__));
define('STUDIO_PROF_BASENAME', plugin_basename(__FILE__));

// Caricamento classi
require_once STUDIO_PROF_PATH . 'includes/class-studio-activator.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-roles.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-db.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-admin.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-patients.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-invoices.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-pdf.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-accountant.php';
require_once STUDIO_PROF_PATH . 'includes/class-studio-google-calendar.php';

// Hook attivazione e disattivazione
register_activation_hook(__FILE__, array('Studio_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('Studio_Activator', 'deactivate'));

// Avvio del plugin
function studio_prof_init() {
    Studio_Activator::check_and_create_tables_if_missing();
    Studio_Roles::init();
    Studio_Admin::get_instance();
    Studio_Patients::get_instance();
    Studio_Invoices::get_instance();
    Studio_Accountant::get_instance();
    Studio_Google_Calendar::get_instance();
}
add_action('plugins_loaded', 'studio_prof_init');

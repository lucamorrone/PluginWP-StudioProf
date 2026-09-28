<?php
if (!defined('ABSPATH')) {
    exit;
}

class Studio_Roles {

    const CAP_MANAGE_STUDIO = 'manage_studio_professionale';
    const CAP_VIEW_PATIENTS = 'view_studio_patients';
    const CAP_EDIT_PATIENTS = 'edit_studio_patients';
    const CAP_MANAGE_INVOICES = 'manage_studio_invoices';
    const CAP_SEND_EMAILS = 'send_studio_emails';

    public static function init() {
        // Garantisce che l'amministratore abbia sempre tutte le capacità
        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap(self::CAP_MANAGE_STUDIO)) {
            self::add_caps_to_role($admin);
        }
    }

    public static function add_roles() {
        // Ruolo Segretaria
        add_role('studio_segretaria', __('Segretaria Studio', 'studio-professionale'), array(
            'read' => true,
            self::CAP_VIEW_PATIENTS => true,
            self::CAP_EDIT_PATIENTS => true,
            self::CAP_MANAGE_INVOICES => true,
            self::CAP_SEND_EMAILS => true,
        ));

        // Assegna capacità anche all'amministratore
        $admin = get_role('administrator');
        if ($admin) {
            self::add_caps_to_role($admin);
        }
    }

    private static function add_caps_to_role($role) {
        $role->add_cap(self::CAP_MANAGE_STUDIO);
        $role->add_cap(self::CAP_VIEW_PATIENTS);
        $role->add_cap(self::CAP_EDIT_PATIENTS);
        $role->add_cap(self::CAP_MANAGE_INVOICES);
        $role->add_cap(self::CAP_SEND_EMAILS);
    }
}

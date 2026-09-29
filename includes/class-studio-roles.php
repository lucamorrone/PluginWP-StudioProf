<?php
if (!defined('ABSPATH')) exit;
class Studio_Roles {
 const CAP_MANAGE_STUDIO='manage_studio_professionale'; const CAP_VIEW_PATIENTS='view_studio_patients'; const CAP_EDIT_PATIENTS='edit_studio_patients'; const CAP_MANAGE_INVOICES='manage_studio_invoices'; const CAP_SEND_EMAILS='send_studio_emails'; const CAP_VIEW_CLINICAL='view_studio_clinical'; const CAP_MANAGE_DOCUMENTS='manage_studio_documents'; const CAP_VIEW_SECURITY='view_studio_security';
 public static function init(){self::add_roles();}
 public static function add_roles(){
  add_role('studio_segretaria',__('Segreteria Studio','studio-professionale'),array('read'=>true,self::CAP_VIEW_PATIENTS=>true,self::CAP_EDIT_PATIENTS=>true,self::CAP_MANAGE_INVOICES=>true,self::CAP_SEND_EMAILS=>true));
  add_role('studio_professionista',__('Professionista','studio-professionale'),array('read'=>true,self::CAP_VIEW_PATIENTS=>true,self::CAP_EDIT_PATIENTS=>true,self::CAP_MANAGE_INVOICES=>true,self::CAP_SEND_EMAILS=>true,self::CAP_VIEW_CLINICAL=>true,self::CAP_MANAGE_DOCUMENTS=>true,self::CAP_VIEW_SECURITY=>true));
  $admin=get_role('administrator');if($admin)self::all_caps($admin);
  $prof=get_role('studio_professionista');if($prof){foreach(array(self::CAP_VIEW_PATIENTS,self::CAP_EDIT_PATIENTS,self::CAP_MANAGE_INVOICES,self::CAP_SEND_EMAILS,self::CAP_VIEW_CLINICAL,self::CAP_MANAGE_DOCUMENTS,self::CAP_VIEW_SECURITY) as $c)$prof->add_cap($c);$prof->remove_cap(self::CAP_MANAGE_STUDIO);}
  $sec=get_role('studio_segretaria');if($sec){$sec->remove_cap(self::CAP_VIEW_CLINICAL);$sec->remove_cap(self::CAP_MANAGE_DOCUMENTS);$sec->remove_cap(self::CAP_VIEW_SECURITY);$sec->remove_cap(self::CAP_MANAGE_STUDIO);}
 }
 private static function all_caps($r){foreach(array(self::CAP_MANAGE_STUDIO,self::CAP_VIEW_PATIENTS,self::CAP_EDIT_PATIENTS,self::CAP_MANAGE_INVOICES,self::CAP_SEND_EMAILS,self::CAP_VIEW_CLINICAL,self::CAP_MANAGE_DOCUMENTS,self::CAP_VIEW_SECURITY) as $c)$r->add_cap($c);}
}
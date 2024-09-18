<?php
/* Copyright (C) 2023 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup easyurl     Module EasyURL
 *  \brief    EasyURL module descriptor
 *
 *  \file     core/modules/modEasyURL.class.php
 *  \ingroup  easyurl
 *  \brief    Description and activation file for module EasyURL
 */

// Load Dolibarr libraries
require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module EasyURL
 */
class modEasyURL extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        if (file_exists(__DIR__ . '/../../../saturne/lib/saturne_functions.lib.php')) {
            require_once __DIR__ . '/../../../saturne/lib/saturne_functions.lib.php';
            saturne_load_langs(['easyurl@easyurl']);
        } else {
            $this->error++;
            $this->errors[] = $langs->trans('activateModuleDependNotSatisfied', 'EasyURL', 'Saturne');
        }

        // ID for module (must be unique)
        $this->numero = 436305;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'easyurl';

        // Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
        // It is used to group modules by family in module setup page
        $this->family = '';

        // Module position in the family on 2 digits ('01', '10', '20', ...)
        $this->module_position = '';

        // Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
        $this->familyinfo = ['Eoxia' => ['position' => '01', 'label' => 'Eoxia']];
        // Module label (no space allowed), used if translation string 'ModuleEasyURLName' not found (EasyURL is name of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description, used if translation string 'ModuleEasyURLDesc' not found (EasyURL is name of module)
        $this->description = $langs->trans('EasyURLDescription');
        // Used only if file README.md and README-LL.md not found
        $this->descriptionlong = $langs->trans('EasyURLDescriptionLong');

        // Author
        $this->editor_name = 'Eoxia';
        $this->editor_url  = 'https://eoxia.com';

        // Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
        $this->version = '1.0.0';

        // Url to the file with your last numberversion of this module
        //$this->url_last_version = 'http://www.example.com/versionmodule.txt';

        // Key used in llx_const table to save module status enabled/disabled (where EASYURL is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

        // Name of image file used for this module
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        // To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
        $this->picto = 'easyurl_color@easyurl';

        // Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
        $this->module_parts = [
            // Set this to 1 if module has its own trigger directory (core/triggers)
            'triggers' => 1,
            // Set this to 1 if module has its own login method file (core/login)
            'login' => 0,
            // Set this to 1 if module has its own substitution function file (core/substitutions)
            'substitutions' => 1,
            // Set this to 1 if module has its own menus handler directory (core/menus)
            'menus' => 0,
            // Set this to 1 if module overwrite template dir (core/tpl)
            'tpl' => 1,
            // Set this to 1 if module has its own barcode directory (core/modules/barcode)
            'barcode' => 0,
            // Set this to 1 if module has its own models' directory (core/modules/xxx)
            'models' => 1,
            // Set this to 1 if module has its own printing directory (core/modules/printing)
            'printing' => 0,
            // Set this to 1 if module has its own theme directory (theme)
            'theme' => 0,
            // Set this to relative path of css file if module has its own css file
            'css' => [],
            // Set this to relative path of js file if module must load a js on all pages
            'js' => [],
            // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
            'hooks' => [
                'interventioncard',
                'propallist',
                'orderlist',
                'invoicelist'
            ],
            // Set this to 1 if features of module are opened to external users
            'moduleforexternal' => 0
        ];

        if (function_exists('saturne_get_objects_metadata')) {
            $objectsMetadata = saturne_get_objects_metadata();
            if (!empty($objectsMetadata)) {
                foreach ($objectsMetadata as $objectMetadata) {
                    $this->module_parts['hooks'][] = $objectMetadata['hook_name_card'];
                }
            }
        }

        // Data directories to create when module is enabled
        // Example: this->dirs = array("/easyurl/temp","/easyurl/subdir");
        $this->dirs = ['/easyurl/temp'];

        // Config pages. Put here list of php page, stored into easyurl/admin directory, to use to set up module
        $this->config_page_url = ['setup.php@easyurl'];

        // Dependencies
        // A condition to hide module
        $this->hidden = false;

        // List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
        $this->depends      = ['modAgenda', 'modSaturne', 'modExport'];
        $this->requiredby   = []; // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
        $this->conflictwith = []; // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

        // The language file dedicated to your module
        $this->langfiles = ['easyurl@easyurl'];

        // Prerequisites
        $this->phpmin                = [7, 4]; // Minimum version of PHP required by module
        $this->need_dolibarr_version = [16, 0]; // Minimum version of Dolibarr required by module

        // Messages at activation
        $this->warnings_activation     = []; // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        $this->warnings_activation_ext = []; // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','MX'='textmx'...)
        //$this->automatic_activation = array('FR'=>'EasyURLWasAutomaticallyActivatedBecauseOfYourCountryChoice');
        //$this->always_enabled = true; // If true, can't be disabled

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('EASYURL_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('EASYURL_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $i = 0;
        $this->const = [
            // CONST CONFIGURATION
            $i++ => ['EASYURL_AUTOMATIC_GENERATION', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYURL_MANUAL_GENERATION', 'integer', 1, '', 0, 'current'],

            // CONST MODULE
            $i++ => ['EASYURL_VERSION', 'chaine', $this->version, '', 0, 'current'],
            $i++ => ['EASYURL_DB_VERSION', 'chaine', $this->version, '', 0, 'current'],
            $i++ => ['EASYURL_SHOW_PATCH_NOTE', 'integer', 1, '', 0, 'current'],
            $i++ => ['EASYURL_ADVANCED_TRIGGER', 'integer', 1, '', 0, 'current'],

            // CONST SHORTENER
            $i++ => ['EASYURL_SHORTENER_ADDON', 'chaine', 'mod_shortener_standard', '', 0, 'current'],

            // CONST DOLIBARR
            $i++ => ['CONTRACT_ALLOW_ONLINESIGN', 'integer', 1, '', 0, 'current'],
            $i   => ['FICHINTER_ALLOW_ONLINE_SIGN', 'integer', 1, '', 0, 'current']
        ];

        // Some keys to add into the overwriting translation tables
        /*$this->overwrite_translation = array(
            'en_US:ParentCompany'=>'Parent company or reseller',
            'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
        )*/

        if (!isset($conf->easyurl) || !isset($conf->easyurl->enabled)) {
            $conf->easyurl = new stdClass();
            $conf->easyurl->enabled = 0;
        }

        // Array to add new pages in new tabs
        $this->tabs = [];

        // Dictionaries
        $this->dictionaries = [
            'langs' => 'easyurl@easyurl',
            // List of tables we want to see into dictionary editor
            'tabname' => [
                MAIN_DB_PREFIX . 'c_shortener_url_type'
            ],
            // Label of tables
            'tablib' => [
                'ShortenerUrlType'
            ],
            // Request to select fields
            'tabsql' => [
                'SELECT f.rowid as rowid, f.ref, f.label, f.description, f.position, f.active  FROM ' . MAIN_DB_PREFIX . 'c_shortener_url_type as f'
            ],
            // Sort order
            'tabsqlsort' => [
                'position ASC'
            ],
            // List of fields (result of select to show dictionary)
            'tabfield' => [
                'ref,label,description,position'
            ],
            // List of fields (list of fields to edit a record)
            'tabfieldvalue' => [
                'ref,label,description,position'
            ],
            // List of fields (list of fields for insert)
            'tabfieldinsert' => [
                'ref,label,description,position'
            ],
            // Name of columns with primary key (try to always name it 'rowid')
            'tabrowid' => [
                'rowid'
            ],
            // Condition to show each dictionary
            'tabcond' => [
                $conf->easyurl->enabled
            ]
        ];

        // Boxes/Widgets
        // Add here list of php file(s) stored in easyurl/core/boxes that contains a class to show a widget
        $this->boxes = [];

        // Cronjobs (List of cron jobs entries to add when module is enabled)
        // unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
        $this->cronjobs = [];

        // Permissions provided by this module
        $this->rights = [];
        $r = 0;

        /* EASYURL PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1); // Permission id (must not be already used)
        $this->rights[$r][1] = $langs->trans('LireModule', 'EasyURL');     // Permission label
        $this->rights[$r][4] = 'lire';                                                  // In php code, permission will be checked by test if ($user->rights->easyurl->session->read)
        $this->rights[$r][5] = 1;
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->trans('ReadModule', 'EasyURL');
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = 1;
        $r++;

        /* SHORTENER PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadObjects', $langs->transnoentities('Shorteners'));
        $this->rights[$r][4] = 'shortener';
        $this->rights[$r][5] = 'read';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('CreateObjects', $langs->transnoentities('Shorteners'));
        $this->rights[$r][4] = 'shortener';
        $this->rights[$r][5] = 'write';
        $r++;
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('DeleteObjects', $langs->transnoentities('Shorteners'));
        $this->rights[$r][4] = 'shortener';
        $this->rights[$r][5] = 'delete';
        $r++;

        /* ADMINPAGE PANEL ACCESS PERMISSIONS */
        $this->rights[$r][0] = $this->numero . sprintf('%02d', $r + 1);
        $this->rights[$r][1] = $langs->transnoentities('ReadAdminPage', 'EasyURL');
        $this->rights[$r][4] = 'adminpage';
        $this->rights[$r][5] = 'read';

        // Main menu entries to add
        $this->menu = [];
        $r = 0;

        // Add here entries to declare new menus
        // EASYURL MENU
        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=easyurl',
            'type'     => 'top',
            'titre'    => $langs->trans('EasyURL'),
            'prefix'   => '<i class="fas fa-home pictofixedwidth"></i>',
            'mainmenu' => 'easyurl',
            'leftmenu' => '',
            'url'      => '/easyurl/easyurlindex.php',
            'langs'    => 'easyurl@easyurl',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easyurl->enabled && $user->rights->easyurl->read',
            'perms'    => '$user->rights->easyurl->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=easyurl',
            'type'     => 'left',
            'titre'    => $langs->transnoentities('Shortener'),
            'prefix'   => '<i class="fas fa-link pictofixedwidth"></i>',
            'mainmenu' => 'easyurl',
            'leftmenu' => 'shortener',
            'url'      => '/easyurl/view/shortener/shortener_list.php',
            'langs'    => 'easyurl@easyurl',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easyurl->enabled',
            'perms'    => '$user->rights->easyurl->shortener->read',
            'target'   => '',
            'user'     => 0,
        ];

        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=easyurl',
            'type'     => 'left',
            'titre'    => $langs->trans('Tools'),
            'prefix'   => '<i class="fas fa-wrench pictofixedwidth"></i>',
            'mainmenu' => 'easyurl',
            'leftmenu' => 'easyurltools',
            'url'      => '/easyurl/view/easyurltools.php',
            'langs'    => 'easyurl@easyurl',
            'position' => 1000 + $r,
            'enabled'  => '$conf->easyurl->enabled',
            'perms'    => '$user->rights->easyurl->read && $user->rights->easyurl->adminpage->read',
            'target'   => '',
            'user'     => 0,
        ];

        // Exports profiles provided by this module
        $r = 1;

        $this->export_code[$r]       = $this->rights_class . '_' . $r;
        $this->export_label[$r]      = 'Shortener'; // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_icon[$r]       = 'fontawesome_fa-link_fas_#63ACC9';
        $this->export_enabled[$r]    = '!empty($conf->easyurl->enabled)';
        $this->export_permission[$r] = [["easyurl", "shortener"]];

        $this->export_fields_array[$r]     = [];
        $this->export_TypeFields_array[$r] = [];
        $this->export_entities_array[$r]   = [];

        $keyforclass     = 'Shortener';
        $keyforclassfile = '/easyurl/class/shortener.class.php';
        $keyforelement   = 'shortener';
        $keyforalias     = 't';

        require DOL_DOCUMENT_ROOT . '/core/commonfieldsinexport.inc.php';

        $this->export_sql_start[$r] = 'SELECT DISTINCT ';

        $this->export_sql_end[$r]  = ' FROM ' . MAIN_DB_PREFIX . 'easyurl_shortener as t';
        $this->export_sql_end[$r] .= ' WHERE 1 = 1';
        $this->export_sql_end[$r] .= ' AND t.entity IN (' . getEntity('shortener') . ')';
    }

    /**
     * Function called when module is enabled
     * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database
     * It also creates data directories
     *
     * @param  string     $options Options when enabling module ('', 'noboxes')
     * @return int                 1 if OK, 0 if KO
     * @throws Exception
     */
    public function init($options = ''): int
    {
        global $conf;

        // Permissions
        $this->remove($options);

        $sql = [];

        $result = $this->_load_tables('/easyurl/sql/');
        if ($result < 0) {
            return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
        }

        dolibarr_set_const($this->db, 'EASYURL_VERSION', $this->version, 'chaine', 0, '', $conf->entity);
        dolibarr_set_const($this->db, 'EASYURL_DB_VERSION', $this->version, 'chaine', 0, '', $conf->entity);

        // Create extrafields during init
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($this->db);

        // Propal extrafields
        $extraFields->update('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', '', 'propal', 0, 0, 2000, '', '', '', 5, 'EasyUrlLinkHelp', '', '', 0, 'easyurl@easyurl');
        $extraFields->addExtraField('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', 2000, '', 'propal', 0, 0, '', '', '', '', 5, 'EasyUrlLinkHelp', '', 0, 'easyurl@easyurl');

        // Order extrafields
        $extraFields->update('easy_url_payment_link', 'EasyUrlPaymentLink', 'url', '', 'commande', 0, 0, 2000, '', '', '', 5, 'EasyUrlLinkHelp', '', '', 0, 'easyurl@easyurl');
        $extraFields->addExtraField('easy_url_payment_link', 'EasyUrlPaymentLink', 'url', 2000, '', 'commande', 0, 0, '', '', '', '', 5, 'EasyUrlLinkHelp', '', 0, 'easyurl@easyurl');

        // Invoice extrafields
        $extraFields->update('easy_url_payment_link', 'EasyUrlPaymentLink', 'url', '', 'facture', 0, 0, 2000, '', '', '', 5, 'EasyUrlLinkHelp', '', '', 0, 'easyurl@easyurl');
        $extraFields->addExtraField('easy_url_payment_link', 'EasyUrlPaymentLink', 'url', 2000, '', 'facture', 0, 0, '', '', '', '', 5, 'EasyUrlLinkHelp', '', 0, 'easyurl@easyurl');

        // Contract extrafields
        $extraFields->update('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', '', 'contrat', 0, 0, 2000, '', '', '', 5, 'EasyUrlLinkHelp', '', '', 0, 'easyurl@easyurl');
        $extraFields->addExtraField('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', 2000, '', 'contrat', 0, 0, '', '', '', '', 5, 'EasyUrlLinkHelp', '', 0, 'easyurl@easyurl');

        // Fiche inter extrafields
        $extraFields->update('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', '', 'fichinter', 0, 0, 2000, '', '', '', 5, 'EasyUrlLinkHelp', '', '', 0, 'easyurl@easyurl');
        $extraFields->addExtraField('easy_url_signature_link', 'EasyUrlSignatureLink', 'url', 2000, '', 'fichinter', 0, 0, '', '', '', '', 5, 'EasyUrlLinkHelp', '', 0, 'easyurl@easyurl');

        // All element type extrafields
        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadata) {
            $extraFields->update('easy_url_all_link', 'EasyUrlAllLink', 'url', '', $objectMetadata['table_element'], 0, 0, 2100, '', '', '', 5, 'EasyUrlAllLinkHelp', '', '', 0, 'easyurl@easyurl');
            $extraFields->addExtraField('easy_url_all_link', 'EasyUrlAllLink', 'url', 2100, '', $objectMetadata['table_element'], 0, 0, '', '', '', '', 5, 'EasyUrlAllLinkHelp', '', 0, 'easyurl@easyurl');
        }

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled
     * Remove from database constants, boxes and permissions from Dolibarr database
     * Data directories are not deleted
     *
     * @param  string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = ''): int
    {
        $sql = [];
        return $this->_remove($sql, $options);
    }
}

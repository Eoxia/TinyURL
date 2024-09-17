<?php
/* Copyright (C) 2021-2024 EVARISK <technique@evarisk.com>
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
 * \file    class/shortener.class.php
 * \ingroup easyurl
 * \brief   This file is a CRUD class file for Shortener (Create/Read/Update/Delete)
 */

// Load Saturne libraries
require_once __DIR__ . '/../../saturne/class/saturneobject.class.php';

/**
 * Class for Shortener
 */
class Shortener extends SaturneObject
{
    /**
     * @var string Module name
     */
    public $module = 'easyurl';

    /**
     * @var string Element type of object
     */
    public $element = 'shortener';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management
     */
    public $table_element = 'easyurl_shortener';

    /**
     * @var int Does this object support multicompany module ?
     * 0 = No test on entity, 1 = Test with field entity, 'field@table' = Test with link by field@table
     */
    public $ismultientitymanaged = 1;

    /**
     * @var int Does object support extrafields ? 0 = No, 1 = Yes
     */
    public $isextrafieldmanaged = 0;

    /**
     * @var string Name of icon for shortener. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'shortener@easyurl' if picto is file 'img/object_shortener.png'
     */
    public string $picto = 'fontawesome_fa-link_fas_#63ACC9';

    public const STATUS_DELETED   = -1;
    public const STATUS_DRAFT     = 0;
    public const STATUS_VALIDATED = 1;
    public const STATUS_ASSIGN    = 10;

    /**
     * 'type' field format:
     *      'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *      'select' (list of values are in 'options'),
     *      'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
     *      'chkbxlst:...',
     *      'varchar(x)',
     *      'text', 'text:none', 'html',
     *      'double(24,8)', 'real', 'price',
     *      'date', 'datetime', 'timestamp', 'duration',
     *      'boolean', 'checkbox', 'radio', 'array',
     *      'mail', 'phone', 'url', 'password', 'ip'
     *      Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
     * 'label' the translation key.
     * 'picto' is code of a picto to show before value in forms
     * 'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or '!empty($conf->multicurrency->enabled)' ...)
     * 'position' is the sort order of field.
     * 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty '' or 0.
     * 'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     * 'noteditable' says if field is not editable (1 or 0)
     * 'default' is a default value for creation (can still be overwroted by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     * 'index' if we want an index in database.
     * 'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     * 'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     * 'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     * 'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     * 'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     * 'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     * 'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     * 'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     * 'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     * 'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 'validate' is 1 if you need to validate with $this->validateField()
     * 'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     * Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor
     */

    /**
     * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor
     */
    public $fields = [
        'rowid'                 => ['type' => 'integer',                            'label' => 'TechnicalID',      'enabled' => 1, 'position' => 1,   'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'comment' => 'Id'],
        'ref'                   => ['type' => 'varchar(128)',                       'label' => 'Ref',              'enabled' => 1, 'position' => 10,  'notnull' => 1, 'visible' => 4, 'noteditable' => 1, 'default' => '(PROV)', 'index' =>1, 'searchall' => 1, 'showoncombobox' => 1, 'validate' => 1, 'comment' => 'Reference of object'],
        'ref_ext'               => ['type' => 'varchar(128)',                       'label' => 'RefExt',           'enabled' => 1, 'position' => 20,  'notnull' => 0, 'visible' => 0],
        'entity'                => ['type' => 'integer',                            'label' => 'Entity',           'enabled' => 1, 'position' => 30,  'notnull' => 1, 'visible' => 0, 'index' => 1],
        'date_creation'         => ['type' => 'datetime',                           'label' => 'DateCreation',     'enabled' => 1, 'position' => 40,  'notnull' => 1, 'visible' => 2],
        'tms'                   => ['type' => 'timestamp',                          'label' => 'DateModification', 'enabled' => 1, 'position' => 50,  'notnull' => 0, 'visible' => 0],
        'import_key'            => ['type' => 'varchar(14)',                        'label' => 'ImportId',         'enabled' => 1, 'position' => 60,  'notnull' => 0, 'visible' => 0],
        'status'                => ['type' => 'smallint',                           'label' => 'Status',           'enabled' => 1, 'position' => 160, 'notnull' => 1, 'visible' => 2, 'default' => 0, 'index' => 1, 'arrayofkeyval' => [0 => 'StatusDraft', 1 => 'ValidatePendingAssignment', 10 => 'Assign'], 'css' => 'minwidth100 maxwidth300 widthcentpercentminusxx'],
        'label'                 => ['type' => 'varchar(255)',                       'label' => 'Label',            'enabled' => 1, 'position' => 70,  'notnull' => 1, 'visible' => 5, 'searchall' => 1, 'css' => 'minwidth100 maxwidth300 widthcentpercentminusxx', 'cssview' => 'wordbreak', 'showoncombobox' => 2, 'validate' => 1],
        'short_url'             => ['type' => 'url',                                'label' => 'ShortUrl',         'enabled' => 1, 'position' => 80,  'notnull' => 0, 'visible' => 1, 'copytoclipboard' => 1, 'css' => 'minwidth100 maxwidth300 widthcentpercentminusxx nowrap'],
        'original_url'          => ['type' => 'url',                                'label' => 'OriginalUrl',      'enabled' => 1, 'position' => 90,  'notnull' => 0, 'visible' => 1, 'copytoclipboard' => 1, 'css' => 'minwidth100 maxwidth300 widthcentpercentminusxx nowrap'],
        'type'                  => ['type' => 'sellist:c_shortener_url_type:label', 'label' => 'UrlType',          'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => 1, 'css' => 'maxwidth150 widthcentpercentminusxx'],
        'methode'               => ['type' => 'select',                             'label' => 'UrlMethode',       'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 5, 'arrayofkeyval' => ['' => '', 'yourls' => 'YOURLS', 'wordpress' => 'WordPress'], 'css' => 'maxwidth200 widthcentpercentminusxx', 'csslist' => 'minwidth150 center', 'help' => 'UrlMethodeDescription'],
        'element_type'          => ['type' => 'select',                             'label' => 'ElementType',      'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1, 'arrayofkeyval' => ['' => ''], 'css' => 'maxwidth150 widthcentpercentminusxx'],
        'fk_element'            => ['type' => 'integer',                            'label' => 'FkElement',        'enabled' => 1, 'position' => 130, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'minwidth200 maxwidth300 widthcentpercentminusxx'],
        'fk_easyurl_urlexport'  => ['type' => 'integer',                            'label' => 'UrlExport',        'enabled' => 1, 'position' => 140, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'easyurl_urlexport.rowid'],
        'fk_user_creat'         => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => 1, 'position' => 150, 'notnull' => 1, 'visible' => 0, 'foreignkey' => 'user.rowid'],
        'fk_user_modif'         => ['type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif',  'picto' => 'user', 'enabled' => 1, 'position' => 160, 'notnull' => 0, 'visible' => 0, 'foreignkey' => 'user.rowid'],
    ];

    /**
     * @var int ID
     */
    public int $rowid;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var string Ref ext
     */
    public $ref_ext;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int|string Creation date
     */
    public $date_creation;

    /**
     * @var int|string Timestamp
     */
    public $tms;

    /**
     * @var string Import key
     */
    public $import_key;

    /**
     * @var int Status
     */
    public $status;

    /**
     * @var string label
     */
    public string $label = '';

    /**
     * @var string Short Url
     */
    public string $short_url = '';

    /**
     * @var string Original Url
     */
    public string $original_url = '';

    /**
     * @var string|null Type
     */
    public ?string $type = '';

    /**
     * @var string Methode
     */
    public string $methode = '';

    /**
     * @var string Element type
     */
    public string $element_type = '';

    /**
     * @var int|string Element ID
     */
    public $fk_element;

    /**
     * @var int|null URL Export
     */
    public $fk_easyurl_urlexport;

    /**
     * @var int User ID
     */
    public $fk_user_creat;

    /**
     * @var int|null User ID
     */
    public $fk_user_modif;

    /**
     * Constructor
     *
     * @param  DoliDb    $db Database handler
     * @throws Exception
     */
    public function __construct(DoliDB $db)
    {
        global $langs;

        $objectsMetadata = saturne_get_objects_metadata();
        foreach ($objectsMetadata as $objectMetadata) {
            $this->fields['element_type']['arrayofkeyval'][$objectMetadata['tab_type']] = $langs->trans($objectMetadata['langs']);
        }

        parent::__construct($db, $this->module, $this->element);
    }

    /**
     * Return the status
     *
     * @param  int    $status ID status
     * @param  int    $mode   0 = long label, 1 = short label, 2 = Picto + short label, 3 = Picto, 4 = Picto + long label, 5 = Short label + Picto, 6 = Long label + Picto
     * @return string         Label of status
     */
    public function LibStatut(int $status, int $mode = 0): string
    {
        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;

            $this->labelStatus[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatus[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingAssignment');
            $this->labelStatus[self::STATUS_ASSIGN]    = $langs->transnoentitiesnoconv('Assign');

            $this->labelStatusShort[self::STATUS_DELETED]   = $langs->transnoentitiesnoconv('Deleted');
            $this->labelStatusShort[self::STATUS_DRAFT]     = $langs->transnoentitiesnoconv('StatusDraft');
            $this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ValidatePendingAssignment');
            $this->labelStatusShort[self::STATUS_ASSIGN]    = $langs->transnoentitiesnoconv('Assign');
        }

        $statusType = 'status' . $status;
        if ($status == self::STATUS_VALIDATED) {
            $statusType = 'status3';
        }
        if ($status == self::STATUS_ASSIGN) {
            $statusType = 'status4';
        }
        if ($status == self::STATUS_DELETED) {
            $statusType = 'status9';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * Sets object to supplied categories
     *
     * Deletes object from existing categories not supplied
     * Adds it to non-existing supplied categories
     * Existing categories are left untouched
     *
     * @param  int[]|int $categories Category or categories IDs
     * @return string
     */
    public function setCategories($categories): string
    {
        return '';
    }

    /**
     * Load dashboard info
     *
     * @return array
     * @throws Exception
     */
    public function load_dashboard(): array
    {
        $getNbShortenerByStatus      = self::getNbShortenerByStatus();
        $getNbShortenerByElementType = self::getNbShortenerByElementType();

        $array['graphs'] = [$getNbShortenerByStatus, $getNbShortenerByElementType];

        return $array;
    }

    /**
     * Get shortener by status.
     *
     * @return array     Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getNbShortenerByStatus(): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('ShortenerRepartition', dol_strtolower($langs->transnoentities('Status')));
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        $array['labels'] = [
            0 => [
                'label' => $langs->transnoentities('StatusDraft'),
                'color' => '#999999'
            ],
            1 => [
                'label' => $langs->transnoentities('ValidatePendingAssignment'),
                'color' => '#bca52b'
            ],
            10 => [
                'label' => $langs->transnoentities('Assign'),
                'color' => '#47e58e'
            ],
        ];

        $arrayNbShortenerByStatus = [0 => 0, 1 => 0, 10 => 0];
        $shorteners = self::fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0']);
        if (is_array($shorteners) && !empty($shorteners)) {
            foreach ($shorteners as $shortener) {
                if (empty($shortener->status)) {
                    $arrayNbShortenerByStatus[0]++;
                } else {
                    $arrayNbShortenerByStatus[$shortener->status]++;
                }
            }
        }

        $array['data'] = $arrayNbShortenerByStatus;

        return $array;
    }

    /**
     * Get shortener by element_type.
     *
     * @return array     Graph datas (label/color/type/title/data etc..)
     * @throws Exception
     */
    public function getNbShortenerByElementType(): array
    {
        global $conf, $langs;

        // Graph Title parameters
        $array['title'] = $langs->transnoentities('ShortenerRepartition', dol_strtolower($langs->transnoentities('ElementType')));
        $array['picto'] = $this->picto;

        // Graph parameters
        $array['width']      = '100%';
        $array['height']     = 400;
        $array['type']       = 'pie';
        $array['showlegend'] = $conf->browser->layout == 'phone' ? 1 : 2;
        $array['dataset']    = 1;

        $array['labels']['NoLinkedElement'] = ['label' => $langs->transnoentities('NoLinkedElement'), 'color' => '#999999'];
        $objectsMetadata = saturne_get_objects_metadata();
        foreach ($objectsMetadata as $objectMetadata) {
            $array['labels'][$objectMetadata['tab_type']]               = ['label' => $langs->transnoentities($objectMetadata['langs']), 'color' => $objectMetadata['color']];
            ksort($array['labels']);
        }

        $arrayNbShortenerByElementType = [];

        $shorteners = self::fetchAll('', '', 0, 0, ['customsql' => 't.status >= 0']);
        if (is_array($shorteners) && !empty($shorteners)) {
            foreach ($shorteners as $shortener) {
                if (empty($shortener->element_type)) {
                    $arrayNbShortenerByElementType['NoLinkedElement']++;
                } else {
                    $arrayNbShortenerByElementType[$shortener->element_type]++;
                }
                ksort($arrayNbShortenerByElementType);
            }
        }

        $array['data'] = $arrayNbShortenerByElementType;

        foreach ($array['labels'] as $key => $label) {
            if (!array_key_exists($key, $array['data'])) {
                unset($array['labels'][$key]);
            }
        }

        return $array;
    }

    /**
     * Display more object details
     *
     * @param  CommonObject $object Current object
     * @return string       $out    Output current table object details
     * @throws Exception
     */
    public function displayObjectDetails(CommonObject $object): string
    {
        require_once __DIR__ . '/../../saturne/lib/medias.lib.php';

        global $conf, $form, $langs, $user;

        switch ($object->element) {
            case 'propal' :
                $element_type = 'proposal';
                break;
            case 'commande' :
                $element_type = 'order';
                break;
            case 'facture' :
                $element_type = 'invoice';
                break;
            case 'contrat' :
                $element_type = 'contract';
                break;
            default :
                $element_type = $object->element;
                break;
        }

        $out  = '<table class="noborder ObjectInfo centpercent">';
        $out .= '<thead><tr class="liste_titre">';
        $out .= '<td class="minwidth100"><i class="far fa-minus-square toggleObjectInfo" style="font-size: 1.5em; margin-right: 4px; vertical-align: middle;"></i>' . $langs->trans('UrlType') . '</td>';
        $out .= '<td class="short-url" style="vertical-align: middle;">' . $langs->trans('ShortUrl');
        $out .= ($user->conf->EASYURL_SHOW_QRCODE ? img_picto($langs->trans('Enabled'), 'switch_on', 'class="show-qrcode marginleftonly pictoModule marginrightonly"') : img_picto($langs->trans('Disabled'), 'switch_off', 'class="show-qrcode marginleftonly pictoModule marginrightonly"'));
        $out .= $form->textwithpicto('', $langs->trans('ShowQRCode'));
        $out .= '</td>';
        $out .= '<td>' . $langs->trans('OriginalUrl') . '</td>';
        $out .= '<td class="center">' . dolButtonToOpenUrlInDialogPopup('assignShortener', $langs->transnoentities('AssignShortener'), '<span class="fa fa fa-link valignmiddle btnTitle-icon" title="' . $langs->trans('Assign') . '"></span>', '/custom/easyurl/view/shortener/shortener_card.php?element_type=' . $element_type . '&fk_element=' . $object->id . '&from_element=1&action=edit_assign&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id), '', 'btnTitle') . '</td>';
        $out .= '</thead></tr>';
        $out .= '<tbody>';

        $shorteners = self::fetchAll('', '', 0, 0, ['customsql' => 't.element_type = "' . $element_type . '" AND t.fk_element = ' . $object->id . ' AND t.status = ' . self::STATUS_ASSIGN]);
        if (is_array($shorteners) && !empty($shorteners)) {
            foreach ($shorteners as $shortener) {
                $out .= '<tr>';
                $out .= '<td class="minwidth100">' . getDictionaryValue('c_shortener_url_type', 'label', $shortener->type) . '</td>';
                $out .= '<td>' . ($user->conf->EASYURL_SHOW_QRCODE ? saturne_show_medias_linked('easyurl', $conf->easyurl->multidir_output[$conf->entity] . '/shortener/' . $shortener->ref . '/qrcode/', 'small', 1, 0, 0, 0, 80, 80, 0, 0, 1, 'shortener/'. $shortener->ref . '/qrcode/', $shortener, '', 0, 0) : $shortener->showOutputField($this->fields['short_url'], 'short_url', $shortener->short_url)) . '</td>';
                $out .= '<td>' . $shortener->showOutputField($this->fields['original_url'], 'original_url', $shortener->original_url) . '</td>';
                $out .= '<td class="center">' . ($user->rights->easyurl->shortener->write > 0 ? '<a class="editfielda" href="' . dol_buildpath('/custom/easyurl/view/shortener/shortener_card.php?id='. $shortener->id . '&element_type=' . $element_type . '&fk_element=' . $object->id . '&from_element_type=1&token=' . newToken() . '&action=edit&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?id=' . $object->id), 1) . '">' . img_edit($langs->trans('Modify')) . '</a>' : '') . '</td>';
                $out .= '</tr>';
            }
        } else {
            $out .= '<tr><td colspan="4" class="opacitymedium">' . $langs->trans('NoRecordFound') . '</td></tr>';
        }
        $out .= '</tbody></table>';

        return $out;
    }
}

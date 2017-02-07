<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BaseTwoACL\View\Helper;

use Cake\View\Helper;
use Cake\ORM\TableRegistry;
use BaseTwoACL\Controller\Component\ACLPermissions;

/**
 * CakePHP AclHelper
 * @author allan
 */
class AclHelper extends Helper
{

    public $helpers = [
        'Html',
        'Form'
    ];

    /**
     *
     * @var \Cake\ORM\Table; 
     */
    private $Modules;
    private $__defaultModule = 'Modules';

    public function initialize(array $config)
    {

        if (!empty($config['module']))
        {
            $this->__defaultModule = $config['module'];
        }

        $this->Modules = TableRegistry::get($this->__defaultModule);

        parent::initialize($config);
    }

    public function permissionTable($options = [], $user = null)
    {
        $modules = $this->Modules->find()->orderAsc('name');


        $tableContent = $this->Html->tableHeaders([__d('bt_acl', 'Module'), __d('bt_acl', 'Without permission'), __d('bt_acl', 'Read'), __d('bt_acl', 'Write'), __d('bt_acl', 'Delete')]);

        foreach ($modules as $module)
        {
            $tableContent .= $this->Html->tableRow([
                $this->Html->tableCell($module->name),
                $this->Html->tableCell($this->Form->radio("acl_{$module->id}", [['value' => 0, 'text' => false]], ['hiddenField' => false, 'value' => 0]), ['for' => "acl-{$module->id}-0"]),
                $this->Html->tableCell($this->Form->radio("acl_{$module->id}", [['value' => 1, 'text' => false]], ['hiddenField' => false])),
                $this->Html->tableCell($this->Form->radio("acl_{$module->id}", [['value' => 2, 'text' => false]], ['hiddenField' => false])),
                $this->Html->tableCell($this->Form->radio("acl_{$module->id}", [['value' => 3, 'text' => false]], ['hiddenField' => false]))
            ]);
        }

        $html = $this->Html->tag('table', $tableContent, $options);

        return $html;
    }

}

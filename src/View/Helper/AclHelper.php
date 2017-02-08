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
class ACLHelper extends Helper
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

    public function permissions($user = null)
    {
        $modules = $this->Modules->find()->orderAsc('name')->select(['id', 'name', 'description']);

        $userPermissions = false;

        if ($user !== null)
        {
            $userPermissions['read']   = $this->decompose($user->acl_read);
            $userPermissions['write']  = $this->decompose($user->acl_write);
            $userPermissions['delete'] = $this->decompose($user->acl_delete);
        }


        $result = [];
        foreach ($modules as $module)
        {
            $result[] = new AclInputHelper($this->_View, [
                'module'      => $module,
                'permissions' => [
                    'id'     => $module->id,
                    'read'   => $userPermissions['read'][$module->id],
                    'write'  => $userPermissions['write'][$module->id],
                    'delete' => $userPermissions['delete'][$module->id]
                ]
            ]);
        }



        return $result;
    }

    /**
     * Takes the value of a sum and decomposes on the base 2 power
     * @param int $value
     * @return mixed
     * @throws \Cake\Error\FatalErrorException
     */
    private function decompose($value)
    {
        if ($value === null)
        {
            throw new \Cake\Error\FatalErrorException(__d('bt_acl', 'A value is required for decomposition'));
        }

        $max = $this->Modules->find()->last()->id;

        $maxAllowedValue = ($max * 2) - 1;


        if ($value > $maxAllowedValue)
        {
            throw new \Cake\Error\FatalErrorException(__d('bt_acl', 'The value is greater than the maximum possible sum'));
        }

        for ($i = $max; $i >= 1; $i = $i / 2)
        {
            if ($value >= $i and $value < 2 * $i)
            {
                $result[$i] = true;
                $value      -= $i;
            } else
            {
                $result[$i] = false;
            }
        }

        return $result;
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y"
     * @param int $module
     * @param ACLPermissions $type
     * @return boolean
     * @throws \Cake\Error\FatalErrorException
     */
    public function verify($module, $type)
    {
        if (!in_array($type, [0, 1, 2, 3]))
        {
            throw new \Cake\Error\FatalErrorException(__d('bt_acl', 'Invalid ACL permission type'));
        }

        if (!$this->request->session()->check('Auth.User'))
        {
            return false;
        }

        switch ($type)
        {
            case ACLPermissions::READ :
                $value = $this->request->session()->read('Auth.User.acl_read');
                break;
            case ACLPermissions::WRITE :
                $value = $this->request->session()->read('Auth.User.acl_write');
                break;
            case ACLPermissions::DELETE :
                $value = $this->request->session()->read('Auth.User.acl_delete');
                break;
        }

        $decomposed = $this->decompose($value);

        if (isset($decomposed[$module]))
        {
            return (bool) $decomposed[$module];
        } else
        {
            throw new \Cake\Error\FatalErrorException(__d('bt_acl', 'Module id: {0} does not exist. It must be of the power base 2. Ex.: 1, 2, 4, 8, 16...', $module));
        }
    }

}

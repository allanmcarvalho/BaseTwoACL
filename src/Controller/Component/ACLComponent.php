<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BaseTwoACL\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;
use BaseTwoACL\Controller\Component\ACLPermissions;

/**
 * CakePHP ACLComponent
 * @author allan
 */
class ACLComponent extends Component
{

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

        if(!$this->request->session()->check('Auth.User'))
        {
            return false;
        }
        
        switch ($type)
        {
            case ACLPermissions::READ :
                $value = $this->request->session()->read('Auth.User.read');
                break;
            case ACLPermissions::WRITE :
                $value = $this->request->session()->read('Auth.User.write');
                break;
            case ACLPermissions::DELETE :
                $value = $this->request->session()->read('Auth.User.delete');
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

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y". If yes, grant the permission, if not, trigger an exception.
     * @param int $module
     * @param ACLPermissions $type
     * @throws \Cake\Network\Exception\MethodNotAllowedException
     */
    public function allow($module, $type)
    {
        if (!$this->verify($module, $type))
        {
            switch ($type)
            {
                case ACLPermissions::READ :
                    $typeLabel = __d('jpc', 'read');
                    break;
                case ACLPermissions::WRITE :
                    $typeLabel = __d('jpc', 'write');
                    break;
                case ACLPermissions::DELETE :
                    $typeLabel = __d('jpc', 'delete');
                    break;
            }
            
            $module = $this->Modules->get($module);
            $this->log(__d('bt_acl', 'The user was prevented from access controller/action: "{0}" because he had no {1} permission on the module "{2}"', $this->request->param('controller') . '/' . $this->request->param('action'), strtoupper($typeLabel), $module->name), \Psr\Log\LogLevel::NOTICE);

            throw new \Cake\Network\Exception\MethodNotAllowedException(__d('bt_acl', 'You are not allowed to {0} in module "{1}"', strtoupper($typeLabel), $module->name));
        }
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y". If yes, trigger an exception, if not, grant the permission.
     * @param int $module
     * @param ACLPermissions $type
     * @throws \Cake\Network\Exception\MethodNotAllowedException
     */
    public function deny($module, $type)
    {
        if ($this->verify($module, $type))
        {
            switch ($type)
            {
                case ACLPermissions::READ :
                    $typeLabel = __d('jpc', 'read');
                    break;
                case ACLPermissions::WRITE :
                    $typeLabel = __d('jpc', 'write');
                    break;
                case ACLPermissions::DELETE :
                    $typeLabel = __d('jpc', 'delete');
                    break;
            }
            
            $module = $this->Modules->get($module);
            $this->log(__d('bt_acl', 'The user was prevented from access controller/action: "{0}" because he had {1} permission on the module "{2}"', $this->request->param('controller') . '/' . $this->request->param('action'), strtoupper($typeLabel), $module->name), \Psr\Log\LogLevel::NOTICE);

            throw new \Cake\Network\Exception\MethodNotAllowedException(__d('bt_acl', 'You are not allowed to {0} in module "{1}"', strtoupper($typeLabel), $module->name));
        }
    }

}

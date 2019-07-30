<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BaseTwoACL\Controller\Component;

use App\Model\Entity\User;
use Cake\Controller\Component;
use Cake\Error\FatalErrorException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Psr\Log\LogLevel;

/**
 * CakePHP ACLComponent
 * @property Component\FlashComponent Flash
 * @author allan
 */
class ACLComponent extends Component
{

    public $components = [
        'Flash'
    ];

    /**
     *
     * @var Table;
     */
    private $Modules;

    /**
     * Default Table name
     * @var string
     */
    private $_defaultModule;

    /**
     * Type of deny result
     * @var string
     */
    private $_defaultDenyType;

    /**
     * Default redirect in deny
     * @var mixed
     */
    private $_defaultRedirect;
    public $redirectUrl;

    public function initialize(array $config)
    {

        $config += [
            'denyType' => 'flash',
            'module'   => 'Modules',
            'redirect' => $this->getController()->getRequest()->referer()
        ];

        $this->_setDenyType($config['denyType']);

        $this->_defaultModule = $config['module'];

        $this->_defaultRedirect = $config['redirect'];
        $this->redirectUrl      = $config['redirect'];

        $this->Modules = TableRegistry::getTableLocator()->get($this->_defaultModule);
        parent::initialize($config);
    }

    /**
     * Set a type of deny result
     * @param string $type
     * @throws FatalErrorException
     */
    private function _setDenyType($type)
    {
        if (!in_array(strtolower($type), ['exception', 'flash', 'boolean']))
        {
            throw new FatalErrorException(__d('bt_acl', 'The "errorType" config should be "flash", "boolean" or "exception"'));
        }

        $this->_defaultDenyType = strtolower($type);
    }

    /**
     * Takes the value of a sum and decomposes on the base 2 power
     * @param int $value
     * @return mixed
     * @throws FatalErrorException
     */
    private function decompose($value)
    {
        if ($value === null)
        {
            throw new FatalErrorException(__d('bt_acl', 'A value is required for decomposition'));
        }

        $max = $this->Modules->find()->last()->id;

        $maxAllowedValue = ($max * 2) - 1;


        if ($value > $maxAllowedValue)
        {
            throw new FatalErrorException(__d('bt_acl', 'The value is greater than the maximum possible sum'));
        }
        $result = [];
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
     *
     * @param Entity|User $user
     * @param array $data
     * @return Entity
     * @throws FatalErrorException
     */
    public function patchUserEntity($user, $data)
    {
        if (!isset($data['base_two_acl']))
        {
            throw new FatalErrorException(__d('bt_acl', 'Required data not found in request'));
        }

        $read   = 0;
        $write  = 0;
        $delete = 0;

        ksort($data['base_two_acl']);

        foreach ($data['base_two_acl'] as $key => $permission)
        {

            if ($permission == 1)
            {
                $read += $key;
            } elseif ($permission == 2)
            {
                $read  += $key;
                $write += $key;
            } elseif ($permission == 3)
            {
                $read   += $key;
                $write  += $key;
                $delete += $key;
            }
        }

        $user->acl_read   = $read;
        $user->acl_write  = $write;
        $user->acl_delete = $delete;

        return $user;
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y"
     * @param int $module module id to verify
     * @param ACLPermissions $type type of permission to verify
     * @return boolean
     * @throws FatalErrorException
     */
    public function verify($module, $type)
    {
        if (!in_array($type, [0, 1, 2, 3]))
        {
            throw new FatalErrorException(__d('bt_acl', 'Invalid ACL permission type'));
        }

        if (!$this->getController()->getRequest()->getSession()->check('Auth.User'))
        {
            return false;
        }

        $value = -1;
        switch ($type)
        {
            case ACLPermissions::READ :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_read');
                break;
            case ACLPermissions::WRITE :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_write');
                break;
            case ACLPermissions::DELETE :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_delete');
                break;
        }

        $decomposed = $this->decompose($value);

        if (isset($decomposed[$module]))
        {
            return (bool) $decomposed[$module];
        } else
        {
            throw new FatalErrorException(__d('bt_acl', 'Module id: {0} does not exist. It must be of the power base 2. Ex.: 1, 2, 4, 8, 16...', $module));
        }
    }

    /**
     * Verifies if the logged in user is allowed a in at least one module with permission "x"
     * @param ACLPermissions $type type of permission to verify
     * @return boolean
     * @throws FatalErrorException
     */
    public function verifyIfHaveAnyPermisson($type)
    {
        if (!in_array($type, [0, 1, 2, 3]))
        {
            throw new FatalErrorException(__d('bt_acl', 'Invalid ACL permission type'));
        }

        if (!$this->request->session()->check('Auth.User'))
        {
            return false;
        }

        switch ($type)
        {
            case ACLPermissions::READ :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_read');
                break;
            case ACLPermissions::WRITE :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_write');
                break;
            case ACLPermissions::DELETE :
                $value = $this->getController()->getRequest()->getSession()->read('Auth.User.acl_delete');
                break;
        }

        $decomposed = $this->decompose($value);



        $result = false;
        foreach ($decomposed as $key => $item)
        {
            if ($item == true)
            {
                $result[$key] = $item;
            }
        }

        return $result;
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y". If yes, grant the permission, if not, trigger an exception.
     * @param int $module module id to verify
     * @param ACLPermissions $type type of permission to verify
     * @param null $redirect
     * @param null $denyType
     * @return bool Description
     */
    public function allow($module, $type, $redirect = null, $denyType = null)
    {
        if ($redirect !== null)
        {
            $this->_defaultRedirect = $redirect;
        }

        if ($denyType !== null)
        {
            $this->_setDenyType($denyType);
        }

        if ($this->verify($module, $type))
        {
            return true;
        } else
        {
            $typeLabel = '';
            switch ($type)
            {
                case ACLPermissions::READ :
                    $typeLabel = __d('bt_acl', 'read');
                    break;
                case ACLPermissions::WRITE :
                    $typeLabel = __d('bt_acl', 'write');
                    break;
                case ACLPermissions::DELETE :
                    $typeLabel = __d('bt_acl', 'delete');
                    break;
            }
            $module = $this->Modules->get($module);

            $this->log(__d('bt_acl', 'The user was prevented from access controller/action: "{0}" because he had no "{1}" permission on the module "{2}"', $this->getController()->getRequest()->getParam('controller') . '/' . $this->getController()->getRequest()->getParam('action'), $typeLabel, $module->name), LogLevel::NOTICE);

            if ($this->_defaultDenyType == 'flash')
            {
                $this->Flash->error(__d('bt_acl', 'You are not allowed to "{0}" in module "{1}"', $typeLabel, $module->name));
                $this->redirectUrl = $this->_defaultRedirect;
                return false;
            } elseif ($this->_defaultDenyType == 'boolean')
            {
                return false;
            } elseif ($this->_defaultDenyType == 'exception')
            {
                throw new MethodNotAllowedException(__d('bt_acl', 'You are not allowed to "{0}" in module "{1}"', $typeLabel, $module->name));
            }
        }
        return false;
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y". If yes, grant the permission, if not, trigger an exception.
     * @param ACLPermissions $type type of permission to verify
     * @param null $redirect
     * @param null $denyType
     * @return bool Description
     */
    public function allowIfHaveAnyPermission($type, $redirect = null, $denyType = null)
    {
        if ($redirect !== null)
        {
            $this->_defaultRedirect = $redirect;
        }

        if ($denyType !== null)
        {
            $this->_setDenyType($denyType);
        }

        if ($this->verifyIfHaveAnyPermisson($type))
        {
            return true;
        } else
        {
            $typeLabel = '';
            switch ($type)
            {
                case ACLPermissions::READ :
                    $typeLabel = __d('bt_acl', 'read');
                    break;
                case ACLPermissions::WRITE :
                    $typeLabel = __d('bt_acl', 'write');
                    break;
                case ACLPermissions::DELETE :
                    $typeLabel = __d('bt_acl', 'delete');
                    break;
            }

            $this->log(__d('bt_acl', 'The user was prevented from access controller/action: "{0}" because he had no "{1}" permission at least one module', $this->getController()->getRequest()->getParam('controller') . '/' . $this->getController()->getRequest()->getParam('action'), $typeLabel), LogLevel::NOTICE);

            if ($this->_defaultDenyType == 'flash')
            {
                $this->Flash->error(__d('bt_acl', 'You must be authorized to "{0}" at least one module', $typeLabel));
                $this->redirectUrl = $this->_defaultRedirect;
                return false;
            } elseif ($this->_defaultDenyType == 'boolean')
            {
                return false;
            } elseif ($this->_defaultDenyType == 'exception')
            {
                throw new MethodNotAllowedException(__d('bt_acl', 'You must be authorized to "{0}" at least one module', $typeLabel));
            }
        }
        return false;
    }

    /**
     * Verifies if the logged in user is allowed in module "x" with permission "y". If yes, trigger an exception, if not, grant the permission.
     * @param int $module
     * @param ACLPermissions $type
     * @param null $redirect
     * @param null $denyType
     * @return bool
     */
    public function deny($module, $type, $redirect = null, $denyType = null)
    {
        if ($redirect !== null)
        {
            $this->_defaultRedirect = $redirect;
        }

        if ($denyType !== null)
        {
            $this->_setDenyType($denyType);
        }

        if (!$this->verify($module, $type))
        {
            return true;
        } else
        {
            $typeLabel = '';
            switch ($type)
            {
                case ACLPermissions::READ :
                    $typeLabel = __d('bt_acl', 'read');
                    break;
                case ACLPermissions::WRITE :
                    $typeLabel = __d('bt_acl', 'write');
                    break;
                case ACLPermissions::DELETE :
                    $typeLabel = __d('bt_acl', 'delete');
                    break;
            }
            $module = $this->Modules->get($module);

            $this->log(__d('bt_acl', 'The user was prevented from access controller/action: "{0}" because he had "{1}" permission on the module "{2}"', $this->getController()->getRequest()->getParam('controller') . '/' . $this->getController()->getRequest()->getParam('action'), $typeLabel, $module->name), LogLevel::NOTICE);

            if ($this->_defaultDenyType == 'flash')
            {
                $this->Flash->error(__d('bt_acl', 'You can not continue because you are allowed to "{0}" in module "{1}"', $typeLabel, $module->name));
                $this->redirectUrl = $this->_defaultRedirect;
                return false;
            } elseif ($this->_defaultDenyType == 'boolean')
            {
                return false;
            } elseif ($this->_defaultDenyType == 'exception')
            {
                throw new MethodNotAllowedException(__d('bt_acl', 'You are not allowed to "{0}" in module "{1}"', $typeLabel, $module->name));
            }
        }
        return false;
    }

    /**
     * Return a array or string of redirect
     * @return mixed
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Return a response redirect
     * @return mixed
     */
    public function getRedirect()
    {
        return $this->_registry->getController()->redirect($this->getRedirectUrl());
    }

    /**
     * Set a array or string of redirect
     * @param mixed $url
     */
    public function setRedirectUrl($url = [])
    {
        $this->redirectUrl = $url;
    }
}

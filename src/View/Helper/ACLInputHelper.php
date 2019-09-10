<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BaseTwoACL\View\Helper;

use App\Model\Entity\Module;
use Cake\ORM\Entity;
use Cake\View\Helper;

/**
 * CakePHP ACLInputHelper
 * @property Helper\FormHelper Form
 * @author allan
 */
class ACLInputHelper extends Helper
{

    public $helpers = ['Form'];

    /**
     * The entity with the module information
     * @var Entity|Module
     */
    public $module;

    /**
     * Contains the read, write, and delete permissions for a module
     * @var array
     */
    public $permissions;

    /**
     * Contains the default permission to automatically select the correct radio button
     * @var int
     */
    public $permission;

    /**
     * Initializes the Helper and verifies what kind of permission the module has (if a user has been passed as a parameter)
     * @param array $config
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->module = $config['module'];
        $this->permissions = $config['permissions'];

        if ($this->permissions == false) {
            $this->permission = 0;
        } elseif ($this->permissions['delete'] == true) {
            $this->permission = 3;
        } elseif ($this->permissions['write'] == true) {
            $this->permission = 2;
        } elseif ($this->permissions['read'] == true) {
            $this->permission = 1;
        } else {
            $this->permission = 0;
        }

    }

    /**
     * The module id represented in this object
     * @return int
     */
    public function moduleId()
    {
        return h($this->module->id);
    }

    /**
     * The name of the module represented in this object
     * @return string
     */
    public function name()
    {
        return h($this->module->name);
    }

    /**
     * The description of the module represented in this object
     * @return string
     */
    public function description()
    {
        return h($this->module->description);
    }

    /**
     * The name to be given in the set of radio buttons for this module
     * @return string
     */
    public function radioName()
    {
        return "acl_{$this->module->id}";
    }

    /**
     * Id of the radio button "without"
     * @return string
     */
    public function withoutId()
    {
        return "acl-{$this->module->id}-0";
    }

    /**
     * Returns the radio button for the "without" permission
     * @param array $options
     * @return string
     */
    public function without($options = [])
    {
        $options += [
            'text' => false
        ];

        $options['value'] = 0;
        return $this->Form->radio("base_two_acl.{$this->module->id}", [$options], ['label' => false, 'hiddenField' => false, 'value' => $this->permission]);
    }

    /**
     * Id of the radio button "read"
     * @return string
     */
    public function readId()
    {
        return "acl-{$this->module->id}-1";
    }

    /**
     * Returns the radio button for the "read" permission
     * @param array $options
     * @return string
     */
    public function read($options = [])
    {
        $options += [
            'text' => false
        ];

        $options['value'] = 1;
        return $this->Form->radio("base_two_acl.{$this->module->id}", [$options], ['label' => false, 'hiddenField' => false, 'value' => $this->permission]);
    }

    /**
     * Id of the radio button "write"
     * @return string
     */
    public function writeId()
    {
        return "acl-{$this->module->id}-2";
    }

    /**
     * Returns the radio button for the "write" permission
     * @param array $options
     * @return string
     */
    public function write($options = [])
    {
        $options += [
            'text' => false
        ];

        $options['value'] = 2;
        return $this->Form->radio("base_two_acl.{$this->module->id}", [$options], ['label' => false, 'hiddenField' => false, 'value' => $this->permission]);
    }

    /**
     * Id of the radio button "delete"
     * @return string
     */
    public function deleteId()
    {
        return "acl-{$this->module->id}-3";
    }

    /**
     * Returns the radio button for the "delete" permission
     * @param array $options
     * @return string
     */
    public function delete($options = [])
    {
        $options += [
            'text' => false
        ];

        $options['value'] = 3;
        return $this->Form->radio("base_two_acl.{$this->module->id}", [$options], ['label' => false, 'hiddenField' => false, 'value' => $this->permission]);
    }

}

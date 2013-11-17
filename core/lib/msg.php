<?php

/*
 * @class   Mag
 * Stores and handles on screen messages : errors and info
 *
 * An Advanced utility for util::get/set_flash_data();
 * **/

class Msg
{
    public $info ;
    public $confirm ;
    public $error ;
    public $form ;
    public $post ;
    
    public function __construct()
    {

    }
     
    public static function instance()
    {
        if (!isset($_SESSION['msg']))
            $_SESSION['msg'] = new self;

        if (count($_POST))
            $_SESSION['msg']->post = $_POST;

        return $_SESSION['msg'];
    }
    
    public function reset()
    {
        $this->info = null;
        $this->confirm = null;
        $this->error = null;
        $this->form = null;
        $this->post = null;
    }
    
    
    public static function notifications()
    {
        $notifications = null;
        $msg = self::instance();
        
        if (count($msg->info)) {
            $notifications['type'] = 'info';
            $notifications['list'] = $msg->info;
        } 
        
        if (count($msg->error)) {
            $notifications['type'] = 'error';
            $notifications['list'] = $msg->error;            
        }

        if (count($msg->confirm)) {
            $notifications['type'] = 'confirm';
            $notifications['list'] = $msg->confirm;            
        }

        if (count($msg->post)) {
            $_POST = $msg->post;
        } 
        
        return $notifications;
        
    }

    public static function form($field)
    {
        $msg = Msg::instance();
        
        if (!isset($msg->form[$field])) 
            return null;
        else
            return "<div class='form-error'><label>".join('<br/>', $msg->form[$field]) . "</label></div>";
    }

}

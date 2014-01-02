<?php

class user Extends DAO  {
    public $pk = "id"; 
    
    public function __construct($id = null)
    {
        parent::__construct("users", $id);        
    }

	public function create()
    {  
        if (parent::save() and $this->add("roles", "login")) 
        { 
            return true;
        } 
        else 
        {            
            Msg::instance()->errors[] = "Unable to create account at this time :(";
            return false;            
        }        
    }

    public static function recover_pass ($email, $username) 
    {
        $user = new User(array('email' => $email, 'username' => $username));
        
        if (!$user->id) return 0;
        
        $password = Rand::alphanum(3);
        $user->password = md5($password);
        $user->save(); 
        
        $message = "<p>Hi <b>{$user->username}</b>, <p>We just received a request to reset your password. Your new password is <b style='font-size:2em'>{$password}</b> </p> <p>You can change your password here <a href='" . HTTP_PATH. "user/index" . "'><b>login</b></a>. If you ever run into problems, don't hesitate to email us.<p>--<br/>Best Regards, <br/>.</p>";
        $from =  array(ADMIN_EMAIL => "Site name") ;
        $to = $user->email;
        $subject = "Your password";
        return Email::send ($subject, $to, $message, $from) ;
    }

    public function __get($var)
    {
        if ($var == 'name') 
        {        	
            $names = ucwords($this->fname) . " " . ucwords($this->lname);  
            return ($this->fname or $this->lname) ? $names : $this->username;
        }
        
        return parent::__get($var);        
    }

    public function __toString()
	{
		return $this->name;	
	}
}
<?php

class Email
{
    public static function send($subject, $to, $message, $from)
    {   
      $logger = new Swift_Plugins_Loggers_ArrayLogger();

        $site_domain = preg_replace('/^.+@/i', '', ADMIN_EMAIL);
        //Send mail
        $transport = Swift_SmtpTransport::newInstance($site_domain, 25)
          ->setUsername(ADMIN_EMAIL)
          ->setPassword(ADMIN_EMAIL_PASSWORD)
          ;

        $mailer = Swift_Mailer::newInstance($transport);
        $mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
        
        $message = Swift_Message::newInstance($subject)
          ->setFrom($from)
          ->setTo($to)
          ->setBody($message)
          ;
        $message->setContentType("text/html");
          
        //Send the message
        if ($result = $mailer->batchSend($message)) 
        {
          error_log($logger->dump());
          return TRUE;        
        } 
        else 
        {
          error_log($logger->dump());
          Msg::instance()->info[] = "Something went wrong, your password has not been reset. Please try again later.";
          throw new Exception("Could not send email");
          return false;
        }
    }
    
}

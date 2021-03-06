<?php

/**
 * Used to send plain text emails, HTML emails, or plain text and html emails with attachments both inline and not REQUIRES sb_Email.php and sb_Files (<-unless you specify the mime types on attachments manually)
 *
 * If DEBUG_EMAIL constant is defined, then all email goes to that address.
 *
 * @author Paul Visco
 * @version 2.25 06/08/03 06/16/09
 * @package sb_Email
 *
 */
class sb_Email_Writer {

/**
 * An instance of sb_Logger for logging the emails sent
 * @var sb_Logger
 */
    public $logger;

    /**
     * Determines if the body of the emails are logged in the log
     * @var boolean
     */
    public $log_body = true;

    /**
     * An instance of sb_Email which describes the email being sent
     *
     * @var sb_Email
     */
    protected $emails = Array();

    /**
     * The ip address of the sender
     * @var string
     */
    protected $remote_addr = '127.0.0.1';

    /**
     *The http host of the server sending the email, defaults to php_uname('n') if $_SERVER['HTTP_HOST'] is not set
     * @var string
     */
    protected $http_host = 'localhost';

    /**
     * Creates a new outbox to send from
     *
	 * @param sb_Logger $logger optional
	 *
	 * <code>
	 * //instanciate the email writer
	 * $myEmailWriter = new sb_Email_Writer();
	 *
	 * //add an instance of sb_Email to the outbox, you can add as many as you want
	 * $myEmailWriter->add_email_to_outbox($myMail);
	 *
	 * //then send, you could add more emails before sending
	 * var_dump($myEmailWriter->send());
	 *
	 * </code>
     */
    public function __construct($logger=null, $remote_addr='', $http_host='') {

        if($logger instanceOf sb_Logger) {
            $this->logger = $logger;
        } else if(isset(App::$logger) && App::$logger instanceof sb_Logger_Base) {
            $this->logger = App::$logger;
		} else {
			$this->logger = new sb_Logger_FileSystem();
		}

        $this->remote_addr = (!empty($remote_addr)) ? $remote_addr : Gateway::$remote_addr;
        $this->http_host = (!empty($http_host)) ? $http_host : (isset($_SERVER['HTTP_HOST'])? $_SERVER['HTTP_HOST'] : php_uname('n')) ;
    }

    /**
     * Sends the emails in the $emails array that were attached using add_email_to_outbox, logs progress if log file is specified
     *
     */
    public function send($email=0) {

        if($email instanceof sb_Email) {
            $this->add_email_to_outbox($email);
        }

        $sent_emails=0;

        foreach($this->emails as &$email) {

            $this->add_security_info($email);

            //all email goes to DEBUG_EMAIL if specified
            if(defined("DEBUG_EMAIL")) {
				$email->debug_info = "\n\nDEBUG MODE: Should be sent to: ".$email->to." when not in debug mode!";
                $email->debug_info .=  "\nDEBUG MODE: Should be sent from: ".$email->from." when not in debug mode!";

                $email->to = DEBUG_EMAIL;
                $email->from = DEBUG_EMAIL;

				if(!empty($email->cc)){
					$email->debug_info .=  "\nDEBUG MODE: Should be be CCed to: ".implode(", ", $email->cc)." when not in debug mode!";
					$email->cc = Array();
				}

				if(!empty($email->bcc)){
					$email->debug_info .=  "\nDEBUG MODE: Should be be BCCed to: ".implode(", ", $email->bcc)." when not in debug mode!";
					$email->bcc = Array();
				}
				
				$email->body .= $email->debug_info;
				if(!empty($email->body_HTML)){
					$email->body_HTML .= nl2br($email->debug_info);
				}
            }

            $email->construct_multipart_message();

            if(mail($email->to, $email->subject, $email->body, $email->_header_text)) {

                $email->sent = 1;
                $sent_emails++;

                $this->log_email($email, true);

            } else {
                $this->log_error($email, false);
            }

        }

        if($sent_emails == count($this->emails)) {
			$this->emails = Array();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Adds an email to the outbox which is sent with the send method
     *
     * @param sb_Email $email
     * @return boolean false if it has injectors, true if added to outbox
     */
    public function add_email_to_outbox($email) {
    
        if($this->check_headers_for_injection($email)) {
            return 0;
        } else {

            $this->emails[] = $email;
            return true;
        }

    }

    /**
     * Logs the sending of emails if logging is enable by specifying the log_file property
     *
     * @param $email sb_Email
     * @param $sent Boolean, was the email sent or not
     */
    private function log_email($email, $sent) {

        $message = "\nEmail sent at ".date('m/d/y h:i:s');
        $message .= "\nFrom:".$email->from. '@'.$this->remote_addr;
        $message .= "\nTo: ".$email->to;
		foreach($email->cc as $cc) {
            $message .="\nCc:".$cc;
        }
        foreach($email->bcc as $bcc) {
            $message .="\nBcc:".$bcc;
        }
        $message .= "\nSubject: ".$email->subject;
        $message .= "\nAttachments: ".count($email->attachments).' ';
        if($this->log_body) {
            $message .= "\nBody: ".$email->body;
            $message .= "\nBody_HTML: ".$email->body_HTML;
        }
        
        $names = Array();
        foreach($email->attachments as $attachment) {
            $names[] = $attachment->name;
        }

        $message .= "(".implode(",", $names).")";


        if($sent) {
            return $this->logger->sb_Email_Writer_Sent($message);
        } else {
            return $this->logger->sb_Email_Writer_Error($message);
        }

    }

    /**
     * Adds security info of sender
     *
     * @param sb_Email $email
     */
    private function add_security_info(sb_Email &$email) {

        $email->body .= "\n\nFor security purposes the following information was recorded: \nSending IP: ".$this->remote_addr." \nSending Host: ".$this->http_host;

        if(!empty($email->body_HTML)) {
            $email->body_HTML .= '<br /><br /><span style="font-size:10px;color:#BCBCBC;margin-top:20px;">For security purposes the following information was recorded: <br />Sending IP:'.$this->remote_addr.' <br />Sending Host: '.$this->http_host.'</span>';
        }
    }

    /**
     * Checks email for injections in from and to addr
     *
     * @param sb_Email $email
     * @return boolean
     */
    private function check_headers_for_injection(sb_Email $email) {
    //try and catch injection attempts and alert admin user
        if (preg_match("~\r|:~i",$email->to) || preg_match("~\r|:~i",$email->from)) {
            return true;
        //do something here to alert admin
        }

        return false;
    }
}

?>
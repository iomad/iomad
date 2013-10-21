<?php

require_once(dirname(__FILE__) . '/../config.php');
require_once(dirname(__FILE__) . '/../../../user/profile/lib.php');
require_once(dirname(__FILE__) . '/../../../local/iomad/lib/company.php');

class EmailTemplate {
    protected $user = null;
    protected $course = null;
    protected $template = null;
    protected $templatename = null;
    protected $company = null;
    protected $invoice = null;
    protected $classroom = null;
    protected $sender = null;
    protected $headers = null;
    protected $approveuser = null;
    
    /**
     * Send an email to (a) specified user(s)
     *
     * @param string $templatename Name of the template as described in
     *                             the global $email array or overridden
     *                             in the mdl_email_template table
     * @param array $options array of options to pass into each email
     * @param array $loopoptions array of array of options to pass into
     *                           individual emails. This could for instance
     *                           be used to send emails to multiple users
     *                           send($tname,
     *                                array('course'=>1),
     *                                array(array('user'=>1),array('user'=>2),array('user'=>3))
     *                           )
     * @return bool if no $loopoptions where specified:
     *              Returns true if mail was sent OK and false if there was an error
     *              or in case $loopoptions were specified:
     *              returns number of successes (ie. count of $loopoptions)
     *              or if there was an error, those $loopoptions for which there was an error
     */
    public static function send($templatename, $options = array(), $loopoptions = array())
    {
        if (count($loopoptions)) {
            $results = array();
            foreach($loopoptions as $loptions) {
                $combinedoptions = array_merge($options, $loptions);
                $ok = false;
                try {
                    $ok = self::send($templatename, $combinedoptions);
                } catch (Exception $e){ 
                }
                if (!$ok) {
                    $results[] = $loptions;
                }
            }
            
            if (count($results)) {
                return $results;
            } else {
                return count($loopoptions);
            }
        } else {
            $emailtemplate = new self($templatename, $options);
            return $emailtemplate->queue_for_cron();
        }
    }
    
    /**
     * Send an email to all users in a department (and it's subdepartments)
     *
     * @param integer $departmentid id of the department
     * @param string $templatename Name of the template as described in
     *                             the global $email array or overridden
     *                             in the mdl_email_template table
     * @param array $options array of options to pass into each email
     * @return bool Returns true if mail was sent OK and false if there was an error
     */    
    public static function send_to_all_users_in_department($departmentid, $templatename, $options = array()) {
        global $DB;
        
        $users = company::get_recursive_department_users($departmentid);
        $useroptions = array_map('self::getuseroption', $users);
        $result = self::send($templatename, $options, $useroptions);
        if ($result === true) {
            return true;
        } else {
            return $result == count($useroptions);
        }
    }

    private static function getuseroption($userrefobject) {
        return array('user'=> $userrefobject->userid);
    }

    public function __construct($templatename, $options = array()) {
        global $USER, $SESSION, $COURSE;

        $user = array_key_exists('user',$options) ? $options['user'] : null;
        $course = array_key_exists('course',$options) ? $options['course'] : null;
        $this->invoice = array_key_exists('invoice',$options) ? $options['invoice'] : null;
        $sender = array_key_exists('sender',$options) ? $options['sender'] : null;
        $approveuser = array_key_exists('approveuser',$options) ? $options['approveuser'] : null;
        $this->classroom = array_key_exists('classroom',$options) ? $options['classroom'] : null;
        $this->license = array_key_exists('license',$options) ? $options['license'] : null;
        $this->headers = array_key_exists('headers',$options) ? $options['headers'] : null;
        
        if (!isset($user)) {
            $user =& $USER;
        }
        if (!isset($course)) {
            $course =& $COURSE;
        }
        if (!isset($sender)) {
            if ($USER->id == 0) {
                // we are being run from cron.
                $sender =& self::get_sender($user);
            } else {
                // not been defined explicitly, use the current user.
                $sender = $USER;
            }
        }

        // set the sender to the default site one if use real sender is not true
        if (empty($CFG->iomad_email_senderisreal)) {
            $sender = generate_email_supportuser();
        }
        
        $this->user = $this->get_user($user);
        $this->approveuser = $this->get_user($approveuser);
        
        // check if we are being passed a password and add it if so
        if (isset($user->newpassword)) {
            $this->user->newpassword = $user->newpassword;
        }

        $this->sender = $this->get_user($sender);

        if (!isset($this->user->email)) {
            print_error("No user was specified or the specified user has no email to send $templatename to.");
        }
        
        if (isset($this->user->id) && !isset($this->user->profile)) {
            profile_load_custom_fields($this->user);
        }
        // check if we are an admin with a company set
        if (!empty($SESSION->currenteditingcompany)) {
            $this->company = new company($SESSION->currenteditingcompany);
        // otherwise use the creating users company
        } else {        
            $this->company = $DB->get_record_sql("SELECT * FROM {company} 
                                                  WHERE id = (
                                                   SELECT companyid FROM {company_user}
                                                   WHERE userid = :userid
                                                  )", array('userid'=>$USER->id));
        }
        
        $this->course = $this->get_course($course);

        $this->templatename = $templatename;
        $this->template = $this->get_template($templatename);
    }

    public function subject() {
        return $this->fill($this->template->subject);
    }
    
    public function body() {
        return $this->fill($this->template->body);
    }
    
    public function queue_for_cron() {
        global $DB;
        
        if (isset($this->user->id)) {
            $email = new stdClass;
            $email->templatename = $this->templatename;
            $email->modifiedtime = time();
            $email->subject = $this->subject();
            $email->body = $this->body();
            $email->varsreplaced = 1;
            $email->userid = $this->user->id;
            if ($this->course) {
                $email->courseid = $this->course->id;
            }
            if ($this->classroom) {
                $email->classroomid = $this->classroom->id;
            }
            if ($this->invoice) {
                $email->invoiceid = $this->invoice->id;
            }
            if ($this->sender) {
                $email->senderid = $this->sender->id;
            }
            if ($this->headers) {
                $email->headers = $this->headers;
            }
            
            return $DB->insert_record('email', $email);
        } else {
            // can't queue it for cron, attempt to send it immediately
            return $this->email_to_user();
        }
    }
    
    static public function send_to_user($email) {
        global $USER;

        // check if the user to be sent to is valid
        if ($user = self::get_user($email->userid)) {
            if (isset($email->senderid)) {
                $supportuser = self::get_user($email->senderid);
            } else {
                $supportuser = self::get_user(self::get_sender($user));
            }
            if (isset($email->headers)) {
                $supportuser->customheaders = unserialize($email->headers);
                email_to_user($USER, $supportuser, $email->subject, $email->body);
            }
            return email_to_user($user, $supportuser, $email->subject, $email->body);
        }
    }
    
    public function email_to_user() {
        global $USER;

        $subject = $this->subject();
        $body = $this->body();
        if (isset($this->sender->id)) {
            $supportuser = self::get_user($this->sender->id);
        } else {
            $supportuser = self::get_user(self::get_sender($this->userid));
        }
        if (isset($email->headers)) {
                $supportuser->customheaders = unserialize($email->headers);
                email_to_user($USER, $supportuser, $email->subject, $email->body);
        }
            

/*
        echo "<br />email from <i>" . $supportuser->email . "</i> to <i>" . $this->user->email . "</i><hr />";
        echo "<b>$subject</b><br/>";
        echo "<pre>$body</pre>";
*/

        return email_to_user($this->user, $supportuser, $subject, $body);
    }
    
    private function get_user($user) {
        global $DB;

        if ($user) {        
            // if $user is an integer, it is a user id, get the object from database
            if (is_int($user) || is_string($user)) {
                if ($user = $DB->get_record('user', array('id' => $user), '*')) {
                    return $user;
                } else {
                    return false;
                }
            } else {
                if (!empty($user->id)) {
                    if ($user->id > 0) {
                        if ($user = $DB->get_record('user', array('id' => $user->id), '*')) {
                            return $user;
                        } else {
                            return false;
                        }
                    } else {
                        if (!empty($user->email) && !empty($user->firstname) && !empty($user->lastname)) {
                            return $user;
                        } else {
                            return false;
                        }
                    }
                }
            }
        }
    }

    private function get_course($course) {
        global $DB;

        if ($course) {
            // if $course is an integer, it is a course id, get the object from database
            if (is_int($course) || is_string($course)) {
                if (!$course = $DB->get_record('course', array('id' => $course), '*', MUST_EXIST)) {
                    print_error('Course ID was incorrect');
                }
            }
            
            if ($course)
                return $course;
        }
    }
    
    private function get_template($templatename) {
        global $DB, $email;
    
        if (isset($this->company->id)) {
            $companyid = $this->company->id;
        }
    
        // try to get it out of the database, otherwise get it from config file
        if (!isset($companyid) || !$template = $DB->get_record('email_template', array('name' => $templatename, 'companyid' => $companyid), '*')) {
            if (isset($email[$templatename])) {
                $template = (object) $email[$templatename];
            } else {
                print_error("Email template '$templatename' not found");
            }
        }

        return $template;
    }
    
    function fill($templatestring) {
        $aMethods = EmailVars::vars();

        $vars = new EmailVars($this->company,$this->user,$this->course,$this->invoice,$this->classroom, $this->license, $this->sender, $this->approveuser);

        foreach($aMethods as $funcname) {
            $replacement = "{" . $funcname . "}";
            
            if (stripos($templatestring, $replacement) !== false) {
                $val = $vars->$funcname;
                
                $templatestring = str_replace($replacement, $val, $templatestring);
            }
        }
        
        return $templatestring;
    }
    
    private function get_sender($user)  {
        
        // Get the user's company
        if ($usercompany = company::get_company_byuserid($user->id)) {
            // is there a default contact userid?
            if (isset($usercompany->defaultcontactid)) {
                $returnid = $usercompany->defaultcontactid;
            } else {
                // use the default support email account
                $returnid = generate_email_supportuser();
            }
        } else {
            // no company use default support user
            $returnid = generate_email_supportuser();
        }
        return $returnid;
    }
}

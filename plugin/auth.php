<?php
namespace plugin;

class auth extends \dw\_plugin {
    public static $authType = null;
    private static $sessData = null;
    public $whitelist = null;
    public $loginttype = "password";
    public $users = null;
    public $password = null;
    const LOGINTYPE_PASSWORD = "password";
    const LOGINTYPE_USER = "user";
    static function event_dw_xhtml_htmlhead_pre() {
        \dw\app::sess();
        $authType = \plugin\auth::authtype();
        \dw\xhtml::AddMeta('authtype', $authType);
    }
    static function event_dw_xhtml_htmlhead_post() {
        $authType = \plugin\auth::authtype();
        if($authType == "Anonymous") {
            \plugin\auth::s_loginForm();
        }
    }
    static function _set_passwordtime($passwordtime) {
        \dw\app::sess("passwordtime", $passwordtime);
        $authType = "Session opened at " . $passwordtime->format("Y-m-d  H:i:s");
        \plugin\auth::authtype($authType);
    }
    private static function evalAuthType() {
        $whitelist = self::s_config("whitelist");
        if(is_array($whitelist)) {
            if(in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
                self::$authType = "White list IP " . $_SERVER['REMOTE_ADDR'];
                return self::$authType;
            }
        }
        $passwordtime = \dw\app::sess("passwordtime");
        if(! $passwordtime) {
            return null;
        }
        return \plugin\auth::authtype("Session opened at " . $passwordtime->format("Y-m-d  H:i:s"));
    }
    static function authtype($parmAuthValue = null) {
        if(! is_null($parmAuthValue)) {
            self::$authType = $parmAuthValue;
            return \dw\app::sess('authtype', self::$authType);
        }
        if(! is_null(self::$authType)) {
            return self::$authType;
        }
        self::$authType = \dw\app::sess('authtype');
        if(! is_null(self::$authType)) {
            return self::$authType;
        }
        self::evalAuthType();
        if(is_null(self::$authType)) {
            self::$authType = "Anonymous";
        }
        return \dw\app::sess('authtype', self::$authType);
    }
    static function s_loginForm($redirect = null) {
        if(is_null($redirect)) {
            $redirect = $_SERVER['REQUEST_URI'];
        }
        $whitelist = self::s_config("whitelist");
        if(is_array($whitelist)) {
            $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
            \dw\xhtml::outhtml("You are loging in from an unknown location<br>");
            \dw\xhtml::outhtml("Your current IP address is '$REMOTE_ADDR'<br>");
        }
        $formData['redirect'] = $redirect;
        $loginttype = self::s_config("loginttype");
        switch ($loginttype) {
            case self::LOGINTYPE_PASSWORD:
                \dw\xhtml::form($formData, "auth.loginpassword");
                die();
                break;
            case self::LOGINTYPE_USER:
                \dw\xhtml::form($formData, "auth.loginuser");
                die();
                break;
            default:
                \dw\app::appThrow("Invalid Login Type", $loginttype);
        }
    }
    static function s_action_login() {
        $authtype = \plugin\auth::authtype();
        if($authtype != "Anonymous") {
            \dw\xhtml::redirect($redirect);
        }
        $redirect = "";
        if(\dw\props::s_post_init()) {
            $redirect = \dw\props::s_post_val("redirect");
            $loginttype = self::s_config("loginttype");
            switch ($loginttype) {
                case self::LOGINTYPE_PASSWORD:
                    $password = \dw\props::s_post_val("password");
                    $correctpassword = self::s_config("password");
                    if($password == $correctpassword) {
                        $passwordtime = new \DateTime();
                        \plugin\auth::_set_passwordtime($passwordtime);
                        $loginMethod = ['\db\loginhistoryRecord',"create" ];
                        if(is_callable($loginMethod)) {
                            $loginMethod();
                        }
                        $passwordtime = \dw\app::sess("passwordtime");
                        $authtype = \plugin\auth::authtype();
                        \dw\xhtml::redirect($redirect);
                    }
                    break;
                case self::LOGINTYPE_USER:
                    $password = \dw\props::s_post_val("password");
                    $username = \dw\props::s_post_val("username");
                    
                    $usersarray = self::s_config("users");
                    
                    if(@$usersarray[$username] = $password) {
                        $passwordtime = new \DateTime();
                        \plugin\auth::_set_passwordtime($passwordtime);
                        $loginMethod = ['\db\loginhistoryRecord',"create" ];
                        if(is_callable($loginMethod)) {
                            $loginMethod();
                        }
                        $passwordtime = \dw\app::sess("passwordtime");
                        $authtype = \plugin\auth::authtype();
                        \dw\xhtml::redirect($redirect);
                    }
                    break;
            }
        }
        \plugin\auth::s_loginForm($redirect);
    }
}
<?php
namespace plugin;

class auth extends \dw\_plugin {
    public static $authType = null;
    private static $sessData = null;
    public $whitelist = null;
    static function event_dw_xhtml_htmlhead_pre() {
        $authType = \plugin\auth::authtype();
        \dw\xhtml::AddMeta('authtype', $authType);
    }
    static function event_dw_xhtml_htmlhead_post() {
        $authType = \plugin\auth::authtype();
        if($authType == "Anonymous") {
            \plugin\auth::s_loginForm();
        }
    }
    static function getSessData($propName) {
        if(\plugin\auth::$sessData == null) {
            session_start();
            \plugin\auth::$sessData = $_SESSION;
            session_write_close();
        }
        return \dw\props::s_arrayget(\plugin\auth::$sessData, $propName, null);
    }
    static function setSessData($propName, $propVal) {
        session_start();
        $_SESSION[$propName] = $propVal;
        \plugin\auth::$sessData = $_SESSION;
        session_write_close();
        return $propVal;
    }
    static function _set_passwordtime($passwordtime) {
        \plugin\auth::setSessData("passwordtime", $passwordtime);
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
        $passwordtime = self::getSessData("passwordtime");
        if(! $passwordtime) {
            return null;
        }
        return self::authtype("Session opened at " . $passwordtime->format("Y-m-d  H:i:s"));
    }
    static function authtype($parmAuthValue = null) {
        if(! is_null($parmAuthValue)) {
            self::$authType = self::setSessData('authtype', $parmAuthValue);
            return self::$authType;
        }
        if(! is_null(self::$authType)) {
            return self::$authType;
        }
        self::$authType = self::getSessData('authtype');
        if(! is_null(self::$authType)) {
            return self::$authType;
        }
        self::evalAuthType();
        if(is_null(self::$authType)) {
            self::$authType = "Anonymous";
        }
        self::setSessData('authtype', self::$authType);
        return self::$authType;
    }
    static function s_loginForm($redirect = null) {
        if(is_null($redirect)) {
            $redirect = $_SERVER['REQUEST_URI'];
        }
        $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        \dw\xhtml::outhtml("You are loging in from an unknown location<br>");
        \dw\xhtml::outhtml("Your current IP address is '$REMOTE_ADDR'<br>");
        $formData['redirect'] = $redirect;
        \dw\xhtml::form($formData, "auth.login");
        \dw\app::appThrow(null);
    }
    static function s_action_login() {
        $authtype = \plugin\auth::authtype();
        if($authtype != "Anonymous") {
            \dw\xhtml::redirect($redirect);
        }
        $redirect = "";
        if(\dw\props::s_post_init()) {
            $password = \dw\props::s_post_val("password");
            $redirect = \dw\props::s_post_val("redirect");
            if($password == 'iminbantian') {
                $passwordtime = new \DateTime();
                \plugin\auth::_set_passwordtime($passwordtime);
                $loginMethod = ['\db\loginhistoryRecord',create ];
                if(is_callable($loginMethod)) {
                    $loginMethod();
                }
                $passwordtime = \plugin\auth::getSessData("passwordtime");
                $authtype = \plugin\auth::authtype();
                \dw\xhtml::redirect($redirect);
            }
        }
        \plugin\auth::s_loginForm($redirect);
    }
}
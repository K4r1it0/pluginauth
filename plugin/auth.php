<?php
namespace plugin;

class auth extends \dw\_plugin {
    public static $authType = null;
    private static $sessData = null;
  
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
    private static function isWhitelist($ipAddress) {
        $whitelistip['ChinaOffice'] = '183.11.38.214';
        $whitelistip['ChinaOffice2'] = '183.11.70.90';
        $whitelistip['LAOffice1'] = '173.51.77.65';
        $whitelistip['LAOffice2'] = '173.51.77.66';
        $whitelistip['LAOffice3'] = '173.51.77.67';
        $whitelistip['LAOffice4'] = '173.51.77.68';
        $whitelistip['localhost-ip4'] = '127.0.0.1';
        $whitelistip['localhost-ip6'] = '::1';
        $whitelistip['dwightpldt'] = '124.104.243.183';
        $whitelistip['lin.stealthpartner.com'] = '45.79.132.66';
        $whitelistip['lin0.stealthpartner.com'] = '139.162.4.152';
        $whitelistip['am.stealthpartner.com'] = '13.58.56.237';
        
        // $whitelistip['dwightpldt'] ='112.202.99.123';
        // $whitelistip['dwightpldt'] ='112.207.219.16';
        // $whitelistip['dwightpldt'] ='112.202.120.245';
        // $whitelistip['dwightpldt'] ='124.104.254.221';
        // $whitelistip['dwightpldt'] ='124.104.253.24';
        // $whitelistip['dwightpldt'] ='49.145.120.175';
        // $whitelistip['dwightpldt'] ='112.207.197.85';
        // $whitelistip['dwightpldt'] = '112.202.99.123';
        // 104.148.3.108 Global Frag Networks
        // 104.237.90.41 GigeNET Los Angeles
        // 110.34.147.212 Krypt Technologies Thailand
        // 113.118.186.6 Shenzhen
        // 113.87.12.111 Shenzhen
        // 113.87.12.120 Shenzhen
        // 113.87.13.88 Shenzhen
        // 118.193.159.216 Shanghai
        // 118.193.238.229 Shanghai
        // 163.125.81.63 China Unicom Shenzen network
        // 174.139.30.91 Krypt Technologies California
        // 183.239.140.210 China Mobile communications corporation
        // 183.239.140.218 China Mobile communications corporation
        // 183.54.40.175 Shenzhen
        // 183.54.41.42 Shenzhen
        // 192.111.134.242 Total Server Solutions Atlanta
        // 198.15.134.164 SERVERYOU China
        // 45.56.159.162 SoftLayer Technologies Japan
        // 45.56.159.213 SoftLayer Technologies Japan
        // 45.56.159.80 SoftLayer Technologies Japan
        // 47.90.91.200 Alibaba (China)
        // 85.203.47.67 Leaseweb Asia Pacific Hong Kong
        
        return in_array($_SERVER['REMOTE_ADDR'], $whitelistip);
    }
    private static function evalAuthType() {
        if(self::isWhitelist($_SERVER['REMOTE_ADDR'])) {
            self::$authType = "White list IP " . $_SERVER['REMOTE_ADDR'];
            return self::$authType;
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
        $formOpt['method'] = 'post';
        $formOpt['url']['controller'] = 'identity';
        $formOpt['url']['action'] = 'login';
        $formOpt['title'] = "LOGIN";
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
                $loginMethod = ['\db\loginhistoryRecord', create];
                if( is_callable($loginMethod )){
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
<?php

/**
 * Created by PhpStorm.
 * User: drillphoto
 * Date: 21.07.17
 * Time: 17:11
 */
namespace AmoCRM;

use AmoCRM\Helpers\Info;

/**
 * Class Amo
 * @package AmoCRM
 */
class Amo
{
    /**
     * @var string
     */
    private static $domain;
    /**
     * @var string
     */
    private static $userLogin;
    /**
     * @var string
     */
    private static $userHash;
    /**
     * @var bool
     */
    private static $authorization;
    /**
     * @var Info
     */
    public static $info;

    /**
     * @param string $phone
     * @return integer
     */
    public static function clearPhone($phone)
    {
        return (int)preg_replace("/[^0-9]/", '', $phone);
    }

    /**
     * @param string $url
     * @param bool $isPost
     * @param array $data
     * @return mixed|null
     */
    public static function cUrl($url, $isPost = false, $data = array())
    {
        if (self::$authorization) {
            if (stripos($url, 'unsorted') !== false) {
                $url .= '?login=' . self::$userLogin . '&api_key=' . self::$userHash;
            }
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
            curl_setopt($curl, CURLOPT_URL, 'https://' . self::$domain . '.amocrm.ru/' . $url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if ($isPost) {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            }
            curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            $out = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($out);
            if ($response)
                return $response->response;
        }
        return null;
    }

    /**
     * @param string $domain
     * @param string $userLogin
     * @param string $userHash
     * @return bool
     */
    public static function authorization($domain, $userLogin, $userHash)
    {
        self::$domain = $domain;
        self::$userLogin = $userLogin;
        self::$userHash = $userHash;
        self::$authorization = true;
        $user = array(
            'USER_LOGIN' => self::$userLogin,
            'USER_HASH' => self::$userHash
        );
        $link = 'private/api/auth.php?type=json';
        $res = self::cUrl($link, true, $user);
        if (file_exists(__DIR__ . '/cookie.txt')) {
            self::$authorization = $res->auth;
            if (self::$authorization)
                self::$info = new Info(self::loadInfo());
        } else {
            echo 'Недостаточно прав для создания файлов!';
            self::$authorization = false;
        }
        return self::$authorization;
    }

    /**
     * @return boolean
     */
    public static function isAuthorization()
    {
        return self::$authorization;
    }

    /**
     * @return false|mixed
     */
    private static function loadInfo()
    {
        $link = 'private/api/v2/json/accounts/current';
        $res = Amo::cUrl($link);
        return $res->account;
    }

    /**
     * @param $phone
     * @param $email
     * @return Contact[]|null
     */
    public static function searchContact($phone, $email = null)
    {
        $link = 'private/api/v2/json/contacts/list?query=';
        $contacts = array();
        if (!empty($phone)) {
            $phone = self::clearPhone($phone);
            $linkPhone = $link . $phone;
            $res = self::cUrl($linkPhone);
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = new Contact();
                    $contact->loadInStdClass($stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($email)) {
            $linkEmail = $link . $email;
            $res = self::cUrl($linkEmail);
            if ($res) {
                foreach ($res->contacts as $stdClass) {
                    $contact = new Contact();
                    $contact->loadInStdClass($stdClass);
                    $contacts[$contact->getId()] = $contact;
                }
            }
        }
        if (!empty($contacts))
            return $contacts;
        return null;
    }

    /**
     * @param string $query
     * @return Lead[]|null
     */
    public static function searchLead($query)
    {
        $link = "private/api/v2/json/leads/list?query=$query";
        $res = Amo::cUrl($link);
        if (!empty($res)) {
            $leads = array();
            foreach ($res->leads as $stdClass) {
                $lead = new Lead();
                $lead->loadInStdClass($stdClass);
                $leads[$lead->getId()] = $lead;
            }
            return $leads;
        }
        return null;
    }
}
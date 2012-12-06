<?php

/**
 * FogBugz API Reference:
 * http://www.fogcreek.com/fogbugz/docs/70/topics/advanced/api.html
 */

class FogBugzClient
{

    const API_URL = 'https://allmyapps.fogbugz.com/api.asp';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const COMMON_COLUMNS = 'ixBug,ixBugParent,ixBugChildren,fOpen,sTitle,sPersonAssignedTo,ixPriority';

    protected static $guestCommands = array('logon');
    private $token;

    public function __construct($email, $password)
    {
        $data = $this->exec('logon', array(
            'email' => $email,
            'password' => $password
        ), self::METHOD_GET);

        if ($data->error && $data->error->attributes()->code)
        {
            throw new Exception((string) $data->error
                . ' (error code: ' . $data->error->attributes()->code . ')');
        }

        $this->token = (string) $data->token;
    }

    public function exec($cmd, $params = array(), $method = self::METHOD_GET, $asXML = false)
    {
        $params['cmd'] = $cmd;
        if (!in_array($cmd, self::$guestCommands))
        {
            if (!$this->token)
            {
                throw new Exception(get_class($this) . ' is not logged in');
            }
            $params['token'] = $this->token;
        }
        $url = self::API_URL;
        if ($method === self::METHOD_GET)
        {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($method === self::METHOD_POST)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        $data = curl_exec($ch);
        curl_close($ch);

        if ($asXML)
        {
            return $data;
        }
        return simplexml_load_string($data);
    }

    public function _getFixFor($id)
    {
        return $this->exec('viewFixFor', array('ixFixFor' => $id))->fixfor;
    }

    public function _getCase($id)
    {
        return $this->exec('search', array(
            'q' => (string) $id,
            'cols' => self::COMMON_COLUMNS
        ))->cases->case;
    }

    public function _getCases($ids)
    {
        $q = array();
        foreach ($ids as $id)
        {
            if (!($id = trim((string) $id)))
            {
                continue;
            }
            $q[] = 'ixbug:"' . $id . '"';
        }
        return $this->exec('search', array(
            'q' => implode(' OR ', $q),
            'cols' => self::COMMON_COLUMNS
        ));
    }

}

<?php
/*
 * This file is part of the Badcow Clickatell PHP API.
 *
 * (c) 2012 Samuel Williams <sam@badcow.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Badcow\Clickatell;

use SimpleXMLElement;

class Clickatell
{
    /**
     * The Clickatell session ID
     *
     * @var string
     */
    protected $session_id;

    /**
     * An array of number to send the SMS to
     *
     * @var array
     */
    protected $numbers = array();

    /**
     * The body of the text message
     * @var string
     */
    protected $text;

    /**
     * The response from the XML server (as XML)
     *
     * @var string
     */
    protected $response;

    /**
     * The sender of the SMS (only if configured)
     *
     * @var string
     */
    protected $from;

    /**
     * The last XML request sent to the gateway
     *
     * @var string
     */
    protected $xml;

    /**
     * The Clickatell API ID
     *
     * @var string
     */
    protected $apiId;

    /**
     * The Clickatell username
     *
     * @var string
     */
    protected $username;

    /**
     * The Clickatell password
     *
     * @var string
     */
    protected $password;

    /**
     * The delay (in minutes) before sending message
     *
     * @var int
     */
    protected $delay;

    /**
     * The user defined message ID
     *
     * @var string
     */
    protected $messageID;

    /**
     * The validity period in minutes. Message will not be delivered
     * if it is still queued on gateway after this time period.
     *
     * @var string
     */
    protected $validityPeriod;

    /**
     * @const
     */
    const API_URL = 'https://api.clickatell.com/xml/xml';

    /**
     * @param string $apiId
     * @param string $username
     * @param string $password
     */
    public function __construct($apiId, $username, $password)
    {
        $this->apiId = $apiId;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Authenticates and opens SMS session
     *
     * @throws \ErrorException
     * @return Clickatell
     */
    public function authenticate()
    {
        $xml = new SimpleXMLElement('<clickAPI/>');

        $auth = $xml->addChild('auth');
        $auth->addChild('api_id', $this->apiId);
        $auth->addChild('user', $this->username);
        $auth->addChild('password', $this->password);

        $response = $this->sendQuery($xml);

        if ($response->{'authResp'}->{'fault'}) {
            throw new \ErrorException('Authentication Failed');
        }

        $this->session_id = $response->{'authResp'}->{'session_id'};

        return $this;
    }

    /**
     * Sets the messages' sender
     *
     * @param string $value The message sender
     * @return Clickatell
     */
    public function setFrom($value)
    {
        $this->from = $value;

        return $this;
    }

    /**
     * Sets the message body text
     *
     * @param string $text The message text
     * @return Clickatell
     */
    public function setMessage($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Sets the user defined message ID
     *
     * @param string $value The user defined message ID
     * @return Clickatell
     */
    public function setMessageID($value)
    {
        $this->messageID = $value;

        return $this;
    }

    /**
     * She delay (in minutes) before sending message
     *
     * @param int $delay
     * @return Clickatell
     */
    public function setDelay($delay)
    {
        $this->delay = (int) $delay;

        return $this;
    }

    /**
     * Sets the validity period (in minutes) after which,
     * if the message has not already been sent, it will not be.
     *
     * @param integer $period
     * @return Clickatell
     */
    public function setValidityPeriod($period)
    {
        $this->validityPeriod = (int) $period;

        return $this;
    }

    /**
     * Add the number(s) of the message recipient(s)
     *
     * @param string|array $numbers
     * @return Clickatell
     */
    public function addNumbers($numbers)
    {
        if(!is_array($numbers)) {
            $numbers = array($numbers);
        }

        foreach ($numbers AS $number) {
            $this->numbers[] = $number;
        }

        return $this;
    }

    /**
     * Get the numbers
     *
     * @return array
     */
    public function getNumbers()
    {
        return $this->numbers;
    }

    /**
     * Get remaining Clickatell balance
     * @return string
     * @throws \ErrorException
     * @return string
     */
    public function getBalance()
    {
        $xml = new SimpleXMLElement('<clickAPI/>');
        $xml->addChild('getBalance')
            ->addChild('session_id', $this->session_id);

        $response = $this->sendQuery($xml);

        if ($response->{'getBalanceResp'}->{'fault'}) {
            throw new \ErrorException('Balance Query Failed');
        }

        return $response->{'getBalanceResp'}->{'ok'};
    }

    /**
     * Get the clickatell session ID
     *
     * @return string The Clickatell session ID
     */
    public function getSessionID()
    {
        return $this->session_id;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function setMessageXML()
    {
        $xml = new SimpleXMLElement('<clickAPI/>');

        foreach ($this->numbers AS $number) {
            $msg = $xml->addChild('sendMsg');
            $msg->addChild('session_id', $this->session_id);
            $msg->addChild('to', $number);
            $msg->addChild('text', $this->text);

            //Optional elements
            if(isset($this->from)) $msg->addChild('from', $this->from);
            if(isset($this->delay)) $msg->addChild('deliv_time', $this->delay);
            if(isset($this->messageID)) $msg->addChild('cliMsgId', $this->messageID);
            if(isset($this->validityPeriod)) $msg->addChild('validity', $this->validityPeriod);
        }

        return $xml;
    }

    /**
     * Send the SMS
     *
     * @return string API Message ID
     * @throws \ErrorException
     */
    public function sendSMS()
    {
        $xml = $this->setMessageXML();
        $response = $this->sendQuery($xml);

        if($response->{'sendMsgResp'}->{'fault'}) {
            throw new \ErrorException('Send Message Failed');
        }

        return $response->{'sendMsgResp'}->{'apiMsgId'};
    }

    /**
     * Get the XML response
     * @return string The response in the form of XML string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return object
     */
    public function sendQuery(SimpleXMLElement $xml)
    {
        $params = array(
            'http' => array(
                'method' => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query(array('data' => $xml->asXML())),
            ));

        $context = stream_context_create($params);
        $file = fopen(self::API_URL, 'r', false, $context);

        $this->response = stream_get_contents($file);
        $this->xml = $xml->asXML();

        return simplexml_load_string($this->response);
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }
}
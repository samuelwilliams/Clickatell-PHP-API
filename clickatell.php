<?php
/**
 * Copyright (C) 2012 Sam Williams <sam@swilliams.com.au>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class clickatell
{
    /**
     * The Clickatell session ID
     * @var string
     */
    protected $session_id;

    /**
     * An array of number to send the SMS to
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
     * @var string
     */
    protected $response;

    /**
     * The sender of the SMS (only if configured)
     * @var string
     */
    protected $from;

    /**
     * The xml being headers
     * @var string
     */
    protected $xml;

    /**
     * The Clickatell credit balance
     * @var string
     */
    protected $balance;

    /**
     * The Clickatell API ID
     * @var string
     */
    protected $apiID;

    /**
     * The Clickatell username
     * @var string
     */
    protected $username;

    /**
     * The Clickatell password
     * @var string
     */
    protected $password;

    /**
     * The Clickatell API URL
     * @var string
     */
    protected $apiURL;

    /**
     * The delay (in minutes) before sending message
     * @var int
     */
    protected $delay;

    /**
     * The user defined message ID
     * @var string
     */
    protected $messageID;

    /**
     * The validity period in minutes. Message will not be delivered if it is still queued on gateway after this time period.
     * @var string
     */
    protected $validityPeriod;

    /**
     * Authenticates and opens SMS session
     * @throws ErrorException
     */
    public function authenticate()
    {
        $xml = new SimpleXMLElement('<clickAPI/>');
        $auth = $xml->addChild('auth');

        $auth->addChild('api_id', $this->apiID);
        $auth->addChild('user', $this->username);
        $auth->addChild('password', $this->password);

        $url = sprintf('%s?data=%s',$this->apiURL,urlencode($xml->asXML()));

        $response = simplexml_load_file($url);

        if($response->{'authResp'}->{'fault'})
        {
            throw new ErrorException('Authentication Failed');
        }
        else
        {
            $this->session_id = $response->{'authResp'}->{'session_id'};
        }
    }

    /**
     * Sets the Clickatell API ID
     * @param string $id The Clickatell API ID
     * @return clickatell
     */
    public function setApiID($id)
    {
        $this->apiID = $id;
        return $this;
    }

    /**
     * Sets the Clickatell Username
     * @param string $username The Clickatell username
     * @return clickatell
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Sets the Clickatell password
     * @param string $password The Clickatell password
     * @return clickatell
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Sets the Clickatell API URL
     * @param string $url The Clickatell API URL
     * @return clickatell
     */
    public function setApiURL($url)
    {
        $this->apiURL = $url;
        return $this;
    }

    /**
     * Sets the messages' sender
     * @param string $value The message sender
     * @return clickatell
     */
    public function setFrom($value)
    {
        $this->from = $value;
        return $this;
    }

    /**
     * Sets the message body text
     * @param string $text The message text
     * @return clickatell
     */
    public function setMessage($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Sets the user defined message ID
     * @param string $value The user defined message ID
     * @return clickatell
     */
    public function setMessageID($value)
    {
        $this->messageID = $value;
        return $this;
    }

    /**
     * Sets the delay
     * @param integer $value The delay (in minutes) before sending message
     * @return clickatell
     */
    public function setDelay($value)
    {
        $this->delay = (int) $value;
        return $this;
    }

    /**
     * Sets the validity period
     * @param integer $value The validity period in minutes.
     * @return clickatell
     */
    public function setValidityPeriod($value)
    {
        $this->validityPeriod = (int) $value;
        return $this;
    }

    /**
     * Add the number(s) of the message recipient(s)
     * @param string|array $numbers The number(s) to send the SMS to
     * @return clickatell
     */
    public function addNumbers($numbers)
    {
        if(!is_array($numbers))
        {
            $numbers = array($numbers);
        }

        foreach($numbers AS $number)
        {
            $this->numbers[] = $number;
        }

        return $this;
    }

    /**
     * Get remaining Clickatell balance
     * @return string
     * @throws ErrorException
     */
    public function getBalance()
    {
        if(!isset($this->session_id))
        {
            $this->authenticate();
        }
        
        $xml = new SimpleXMLElement('<clickAPI/>');
        $balance = $xml->addChild('getBalance');
        $balance->addChild('session_id', $this->session_id);

        $url = sprintf('%s?data=%s', $this->apiURL,urlencode($xml->asXML()));

        $this->response = file_get_contents($url);

        $response = simplexml_load_string($this->response);

        if($response->{'getBalanceResp'}->{'fault'})
        {
            throw new ErrorException('Balance Query Failed');
        }
        else
        {
            $this->balance = $response->{'getBalanceResp'}->{'ok'};
        }

        return $this->balance;
    }

    /**
     * Get the numbers
     * @return array
     */
    public function getNumbers()
    {
        return $this->numbers;
    }

    /**
     * Get the clickatell session ID
     * @return string The Clickatell session ID
     */
    public function getSessionID()
    {
        return $this->session_id;
    }

    /**
     * Set the request XML
     */
    public function setXML()
    {
        $xml = new SimpleXMLElement('<clickAPI/>');

        foreach($this->numbers AS $number)
        {
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

        $this->xml = $xml->asXML();
    }

    /**
     * Get the request XML
     * @return string The message XML
     */
    public function getXML()
    {
        return $this->xml;
    }

    /**
     * Send the SMS
     * @return string API Message ID
     * @throws ErrorException
     */
    public function sendSMS()
    {
        if(!isset($this->session_id))
        {
            $this->authenticate();
        }
        
        $this->setXML();

        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query(array('data' => $this->xml))
            ));

        $context = stream_context_create($params);
        $file = fopen($this->apiURL, 'r', false, $context);
        $this->response = stream_get_contents($file);
        
        $response = simplexml_load_string($this->response);

        if($response->{'sendMsgResp'}->{'fault'})
        {
            throw new ErrorException('Send Message Failed');
        }
        else
        {
            return $response->{'sendMsgResp'}->{'apiMsgId'};
        }
    }

    /**
     * Get the XML response
     * @return string The response in the form of XML string
     */
    public function getResponse()
    {
        return $this->response;
    }
}
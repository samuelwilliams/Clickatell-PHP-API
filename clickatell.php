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
    protected $session_id;
    protected $numbers = array();
    protected $text;
    protected $response;
    protected $from;
    protected $rejectedNumbers = array();
    protected $xml;
    protected $balance;
    protected $apiID;
    protected $username;
    protected $password;
    protected $apiURL;

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

        if($response->authResp->fault)
        {
            throw new ErrorException('Authentication Failed');
        }
        else
        {
            $this->session_id = $response->authResp->session_id;
        }
    }

    /**
     * @param $id The Clickatell API ID
     * @return clickatell
     */
    public function setApiID($id)
    {
        $this->apiID = $id;
        return $this;
    }

    /**
     * The Clickatell Username
     * @param string $username
     * @return clickatell
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * The Clickatell password
     * @param string $password
     * @return clickatell
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * The API URL
     * @param string $url
     * @return clickatell
     */
    public function setApiURL($url)
    {
        $this->apiURL = $url;
        return $this;
    }

    /**
     * @param $value The message sender
     * @return clickatell
     */
    public function setFrom($value)
    {
        $this->from = $value;
    }

    /**
     * @param string $text The message text
     * @return clickatell
     */
    public function setMessage($text)
    {
        $this->text = $text;
    }

    /**
     * This function is configured to validate Australian Mobile numbers.
     * This will need to be changed if sending SMS messages to numbers outside of Australia
     * @param (string|array) $numbers The number(s) to send the SMS to
     */
    public function addNumbers($numbers)
    {
        if(!is_array($numbers))
        {
            $numbers = array($numbers);
        }

        foreach($numbers AS $val)
        {
            $num = preg_replace('/[^\d]/','',$val);
            if(preg_match('/(61|0)4\d{8}/',$num))
            {
                $this->numbers[] = $num;
            }
            else
            {
                $this->rejectedNumbers[] = $val;
            }
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

        if($response->getBalanceResp->fault)
        {
            throw new ErrorException('Balance Query Failed');
        }
        else
        {
            $this->balance = $response->getBalanceResp->ok;
        }

        return $this->balance;
    }

    /**
     * @return array
     */
    public function getNumbers()
    {
        return $this->numbers;
    }
    
    /**
     * @return array The rejected numbers
     */
    public function getRejectedNumbers()
    {
        return $this->rejectedNumbers;
    }

    /**
     * @return string The Clickatell session ID
     */
    public function getSessionID()
    {
        return $this->session_id;
    }

    /**
     * Set the messages XML
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
            $msg->addChild('from', $this->from);
        }

        $this->xml = $xml->asXML();
    }

    /**
     * @return string
     */
    public function getXML()
    {
        return $this->xml;
    }

    /**
     * Send the SMS
     * @return API Message ID
     * @throws ErrorException
     */
    public function sendSMS()
    {
        if(!isset($this->session_id))
        {
            $this->authenticate();
        }
        
        $this->setXML();

        $url = sprintf('%s?data=%s', $this->apiURL, urlencode($this->xml));
        $this->response = file_get_contents($url);
        
        $response = simplexml_load_string($this->response);

        if($response->sendMsgResp->fault)
        {
            throw new ErrorException('Send Message Failed');
        }
        else
        {
            return $response->sendMsgResp->apiMsgId;
        }
    }

    /**
     * @return string The response in the form of XML
     */
    public function getResponse()
    {
        return $this->response;
    }
}
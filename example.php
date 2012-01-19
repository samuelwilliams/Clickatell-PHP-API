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

require_once('clickatell.php');

/**
 * The API is configured to filter Australian numbers,
 * this can be changed upon implementation. If you come
 * up with a better solution, please make a pull request.
 */
$phone_numbers = array(
    '0412345678',
    '+61412 345 678',
    '61412 345 678'
);

$sms = new clickatell();

$sms->setApiID('1234567')
    ->setUsername('username')
    ->setPassword('password')
    ->setFrom('Joe Bloggs')
    ->addNumbers($phone_numbers);

$sms->authenticate();
$sms->sendSMS();

echo sprintf('Your SMS balance is <strong>%s</strong>', $sms->getBalance());

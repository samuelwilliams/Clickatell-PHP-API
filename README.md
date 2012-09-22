Badcow Clickatell PHP API
=========================

This is an API for use with the Clickatell SMS service. It allows the sending of a single message to multiple numbers. The script uses the Clickatell XML API, you will need to subscribe to a Clickatell account to use this API.

## Example
    require_once __DIR__ . '/Clickatell/Clickatell.php';

    use Badcow\Clickatell\Clickatell;

    $clickatell = new Clickatell('1234567', 'username', 'password');

    $clickatell->authenticate()
        ->addNumbers(array(
            '19991234567',
            '19997654321',
        ))
        ->setFrom('Sam Williams')
        ->setMessage('Hello this is a test message')
        ->sendSMS();
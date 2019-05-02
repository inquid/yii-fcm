<?php

namespace inquid\fcm;

use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\ServiceAccount;
use wadeshuler\sms\BaseSms;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Json;


/**
 * Fcm is a wrapper component for the Firebase SDK.
 *
 * To use Fcm, you should configure it in the application configuration like the following:
 *
 * To send an Notification, you may use the following code:
 *
 *'fcm' => [
 * 'class' => 'inquid\fcm\Fcm',
 *
 * // Advanced app use '@common/sms', basic use '@app/sms'
 * 'viewPath' => '@common/sms',     // Optional: defaults to '@app/sms'
 *
 * // send all sms to a file by default. You have to set
 * // 'useFileTransport' to false and configure the messageConfig['from'],
 * // 'sid', and 'token' to send real messages
 * 'useFileTransport' => false,
 *
 * 'messageConfig' => [
 * 'from' => '+15552221234',  // Your Twilio number (full or shortcode)
 * ],
 *
 * // Create and download your Service Account http://console.firebase.google.com
 * 'serviceAccountPath' => '@app/credentials/google-service-account.json',
 * 'projectId' => 'your_project_id',
 * // Tell Twilio where to POST information about your message.
 * // @see https://www.twilio.com/docs/sms/send-messages#monitor-the-status-of-your-message
 * //'statusCallback' => 'https://example.com/path/to/callback',      // optional
 * ],
 */
class Fcm extends BaseSms
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'inquid\fcm\Message';

    private $_firebaseClient;

    public $serviceAccountPath = '@app/credentials/google-service-account.json';

    public $projectId;

    public function init()
    {
        if ($this->useFileTransport === false) {
            if (!isset($this->serviceAccountPath) || empty($this->serviceAccountPath)) {
                throw new InvalidConfigException(self::class . ": Firebase 'serviceAccountPath' configuration parameter is required!");
            }

            if (!isset($this->projectId) || empty($this->projectId)) {
                throw new InvalidConfigException(self::class . ": Firebase 'projectId' configuration parameter is required!");
            }
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function sendMessage($message)
    {
        /* @var $message Message */
        try {
            $from = $message->getFrom();
            $to = $message->getTo();

            if (!isset($from) || empty($from)) {
                throw new InvalidConfigException(self::class . ": Invalid 'from' phone number!");
            }

            if (!isset($to) || empty($to)) {
                throw new InvalidConfigException(self::class . ": Invalid 'to' phone number!");
            }

            $client = $this->getFirebaseClient();
            $messaging = $client->getMessaging();
            $cloudMessage = CloudMessage::withTarget('topic', $message->getTopic());

            $cloudMessage->withNotification([
                'title' => $message->getTitle(),
                'body' => $message->getMessage()
            ]);

            $androidConfig = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'normal',
                'notification' => [
                    'title' => $message->getTitle(),
                    'body' => $message->getMessage(),
                    'icon' => 'stock_ticker_update',
                    'color' => '#f45342',
                ],
            ]);
            $cloudMessage = $cloudMessage->withAndroidConfig($androidConfig);

            $appleConfig = ApnsConfig::fromArray([
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $message->getTitle(),
                            'body' => $message->getMessage(),
                        ],
                        'badge' => 42,
                    ],
                ],
            ]);
            $cloudMessage = $cloudMessage->withApnsConfig($appleConfig);

            $result = $messaging->send($cloudMessage);

            try {
                $firebase->getMessaging()->validate($message);
            } catch (InvalidMessage $e) {
                Yii::error(Json::encode($e->errors()));
            }

            return $result;

        } catch (InvalidConfigException $e) {
            file_put_contents(Yii::getAlias('@runtime') . '/logs/sms-exception.log', '[' . date('m-d-Y h:i:s a', time()) . '] SMS Failed - Phone: ' . $to . PHP_EOL . $e->getMessage() . PHP_EOL . '---' . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            file_put_contents(Yii::getAlias('@runtime') . '/logs/sms-exception.log', '[' . date('m-d-Y h:i:s a', time()) . '] SMS Failed - Phone: ' . $to . PHP_EOL . $e->getMessage() . PHP_EOL . '---' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return false;
    }

    /**
     * @return \Kreait\Firebase\Factory firebase instance.
     */
    public function getFirebaseClient()
    {
        if (!is_object($this->_firebaseClient)) {
            $this->_firebaseClient = $this->createFirebaseClient();
        }
        return $this->_firebaseClient;
    }

    /**
     * Creates Firebase Client instance.
     * @return \Kreait\Firebase\Factory firebase instance.
     */
    protected function createFirebaseClient()
    {
        $serviceAccount = ServiceAccount::fromJsonFile(Yii::getAlias($this->serviceAccountPath));
        return (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri("https://{$this->projectId}.firebaseio.com")
            ->create();
    }

}

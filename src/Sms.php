<?php

namespace inquid\fcm;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\BaseStringHelper;
use wadeshuler\sms\BaseSms;


/**
 * Sms is a wrapper component for the Firebase SDK.
 *
 * To use Sms, you should configure it in the application configuration like the following:
 *
 * ```php
 * $firebase
    ->getMessaging()
    ->send([
        'topic' => 'my-topic',
        // 'condition' => "'TopicA' in topics && ('TopicB' in topics || 'TopicC' in topics)",
        // 'token' => '...',
        'notification' => [
            'title' => 'Notification title',
            'body' => 'Notification body',
        ],
        'data' => [
            'key_1' => 'Value 1',
            'key_2' => 'Value 2',
        ],
        'android' => [
            'ttl' => '3600s',
            'priority' => 'normal',
            'notification' => [
                'title' => '$GOOG up 1.43% on the day',
                'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                'icon' => 'stock_ticker_update',
                'color' => '#f45342',
            ],
        ],
        'apns' => [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'alert' => [
                        'title' => '$GOOG up 1.43% on the day',
                        'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                    ],
                    'badge' => 42,
                ],
            ],
        ],
        'webpush' => [
            'notification' => [
                'title' => '$GOOG up 1.43% on the day',
                'body' => '$GOOG gained 11.80 points to close at 835.67, up 1.43% on the day.',
                'icon' => 'https://my-server/icon.png',
            ],
            'fcm_options' => [
                'link' => 'https://my-server/some-page',
            ],
        ],
    ])
 * ```
 *
 * To send an Notification, you may use the following code:
 *
 *
 */
class Sms extends BaseSms
{
    /**
     * @var string message default class name.
     */
    public $messageClass = 'inquid\fcm\Message';

    //public $from;

    public $sid;

    public $token;

    public $statusCallback;

    private $_firebaseClient;

    public function init()
    {
        if ( $this->useFileTransport === false )
        {
            if ( ! isset($this->sid) || empty($this->sid) ) {
                throw new InvalidConfigException(self::class . ": Firebase 'sid' configuration parameter is required!");
            }

            if ( ! isset($this->token) || empty($this->token) ) {
                throw new InvalidConfigException(self::class . ": Firebase 'token' configuration parameter is required!");
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

            if ( ! isset($from) || empty($from) ) {
                throw new InvalidConfigException(self::class . ": Invalid 'from' phone number!");
            }

            if ( ! isset($to) || empty($to) ) {
                throw new InvalidConfigException(self::class . ": Invalid 'to' phone number!");
            }

            $client = new Client($this->sid, $this->token);

            $payload = [
                'from' => $from,
                'body' => $message->toString()
            ];

            if ( isset($this->statusCallback) && ! empty($this->statusCallback) ) {
                $payload['statusCallback'] = $this->statusCallback;
            }

            if ( isset($message->mediaUrl) && ! empty($message->mediaUrl) ) {
                $payload['mediaUrl'] = $message->mediaUrl;
            }

            $result = $client->messages->create(
                $to,
                $payload
            );

            return $result;

        } catch (InvalidConfigException $e) {
            file_put_contents(Yii::getAlias('@runtime') . '/logs/sms-exception.log', '[' . date('m-d-Y h:i:s a', time()) . '] SMS Failed - Phone: ' . $to . PHP_EOL . $e->getMessage() . PHP_EOL . '---' . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            file_put_contents(Yii::getAlias('@runtime') . '/logs/sms-exception.log', '[' . date('m-d-Y h:i:s a', time()) . '] SMS Failed - Phone: ' . $to . PHP_EOL . $e->getMessage() . PHP_EOL . '---' . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return false;
    }

    /**
     * @return \Client Firebase Client instance
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
     * @return \Client firebase instance.
     */
    protected function createFirebaseClient()
    {
        return new Client($this->sid, $this->token);
    }

}

<?php

namespace inquid\fcm;

use Yii;
use wadeshuler\sms\BaseMessage;

class Message extends BaseMessage
{

    private $_mediaUrl;

    /**
     * Nicename function for getTextBody()
     */
    public function getMessage()
    {
        return $this->getTextBody();
    }

    /**
     * Nicename function for setTextBody()
     */
    public function setMessage($text)
    {
        $this->setTextBody($text);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMediaUrl()
    {
        return $this->_mediaUrl;
    }

    /**
     * @inheritdoc
     */
    public function setMediaUrl($url)
    {
        $this->_mediaUrl = $url;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return $this->getTextBody();
    }

}

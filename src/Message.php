<?php

namespace inquid\fcm;

use wadeshuler\sms\BaseMessage;

class Message extends BaseMessage
{

    private $_mediaUrl;
    private $topic;
    private $title;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string mixed
     */
    public function getTopic()
    {
        return $this->topic;
    }

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
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;

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

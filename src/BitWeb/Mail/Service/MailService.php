<?php

namespace BitWeb\Mail\Service;

use BitWeb\Mail\Configuration;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Mime;
use Zend\Mime\Part;

class MailService
{

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var Configuration
     */
    protected $configuration;
    protected $bypassConfiguration = false;

    public function __construct(TransportInterface $transportInterface, Configuration $configuration)
    {
        $this->setTransport($transportInterface);
    }

    /**
     * @param \BitWeb\Mail\Configuration $configuration
     * @return self
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return \BitWeb\Mail\Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function setTransport(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function setBypassConfiguration($bypass = false)
    {
        $this->bypassConfiguration = (bool)$bypass;
    }

    public function send(Message $message, array $attachments = array())
    {
        if (!$this->bypassConfiguration) {
            if ($this->getConfiguration()->getSendAllMailsToBcc() !== null) {
                $message->addBcc($this->getConfiguration()->getSendAllMailsToBcc());
            }

            if ($this->getConfiguration()->getSendAllMailsTo() != null) {
                $message->setTo($this->getConfiguration()->getSendAllMailsTo());
            }
        }

        $content = $message->getBody();

        $bodyMessage = new \Zend\Mime\Message();
        $multiPartContentMessage = new \Zend\Mime\Message();

        $text = new Part(strip_tags($content));
        $text->type = "text/plain";
        $text->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $multiPartContentMessage->addPart($text);

        $html = new Part($content);
        $html->type = Mime::TYPE_HTML;
        $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $html->charset = 'utf-8';
        $multiPartContentMessage->addPart($html);


        $multiPartContentMimePart = new Part($multiPartContentMessage->generateMessage());
        $multiPartContentMimePart->type = 'multipart/alternative;' . PHP_EOL . ' boundary="' .
            $multiPartContentMessage->getMime()->boundary() . '"';

        $bodyMessage->addPart($multiPartContentMimePart);

        foreach ($attachments as $attachment) {
            $bodyMessage->addPart($attachment);
        }

        $message->setBody($bodyMessage);
        $message->setEncoding("UTF-8");

        $this->transport->send($message);

        $this->setBypassConfiguration();
    }
}
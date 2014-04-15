<?php

namespace BitWeb\Mail\Service;

use BitWeb\Mail\Configuration;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Mime;
use Zend\Mime\Part;

class MailService
{
    const SEND_MAIL = 'bitweb.mailService.send';

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;
    protected $bypassConfiguration = false;

    /**
     * @param \Zend\EventManager\EventManagerInterface $eventManager
     * @return self
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * @return \Zend\EventManager\EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->eventManager;
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

    public function __construct(TransportInterface $transportInterface, Configuration $configuration)
    {
        $this->setTransport($transportInterface);
        $this->setConfiguration($configuration);
    }

    public function initializeListener()
    {
        if ($this->eventManager !== null) {
            return;
        }
        
        $this->getEventManager()->attach(self::SEND_MAIL, function (EventInterface $e) use ($this) {
            $target = $e->getTarget();
            $message = new Message();
            $attachments = [];
            if (isset($target['to']) && is_array($target['to'])) {
                $message->setTo($target['to']);
            }

            if (isset($target['cc']) && is_array($target['cc'])) {
                foreach ($target['cc'] as $cc) {
                    $message->addCc($cc);
                }
            }

            if (isset($target['bcc']) && is_array($target['bcc'])) {
                foreach ($target['bcc'] as $bcc) {
                    $message->addBcc($bcc);
                }
            }

            if (isset($target['from']) && is_array($target['from'])) {
                $message->setFrom($target['from']);
            }

            if (isset($target['subject']) && is_array($target['subject'])) {
                $message->setSubject($target['subject']);
            }

            if (isset($target['body']) && is_array($target['body'])) {
                $message->setBody($target['body']);
            }

            if (isset($target['attachments']) && is_array($target['attachments'])) {
                foreach ($target['attachments'] as $filePath) {
                    $attachments[] = $this->getPartFromFile($filePath);
                }
            }

            $this->send($message, $attachments);
        });
    }

    protected function getPartFromFile($filePath)
    {
        $part = new Part(file_get_contents($filePath));
        $part->type = mime_content_type($filePath);
        $part->disposition = \Zend\Mime\Mime::DISPOSITION_ATTACHMENT;
        $part->encoding = \Zend\Mime\Mime::ENCODING_BASE64;
        $part->charset = 'UTF-8';
        $part->filename = basename($filePath);

        return $part;
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

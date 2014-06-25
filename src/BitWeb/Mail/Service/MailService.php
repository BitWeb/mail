<?php

namespace BitWeb\Mail\Service;

use BitWeb\Mail\Configuration;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mime\Mime;
use Zend\Mime\Part;

class MailService
{
    use EventManagerAwareTrait;

    const EVENT_SEND_MAIL = 'bitweb.mailService.send';

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

    public function attachDefaultListeners()
    {
        if ($this->eventManager !== null) {
            return;
        }

        $this->getEventManager()->attach(self::EVENT_SEND_MAIL, function (EventInterface $e) {
            $params = $e->getParams();
            $message = new Message();
            $attachments = [];
            if (isset($params['to']) && is_array($params['to'])) {
                $message->setTo($params['to']['email'], $params['to']['name']);
            }

            if (isset($params['cc']) && is_array($params['cc'])) {
                foreach ($params['cc'] as $cc) {
                    $message->addCc($cc['email'], $cc['name']);
                }
            }

            if (isset($params['bcc']) && is_array($params['bcc'])) {
                foreach ($params['bcc'] as $bcc) {
                    $message->addBcc($bcc['email'], $bcc['name']);
                }
            }

            if (isset($params['from']) && is_array($params['from'])) {
                $message->setFrom($params['from']['email'], $params['from']['name']);
            }

            if (isset($params['subject']) && $params['subject']) {
                $message->setSubject($params['subject']);
            }

            if (isset($params['body']) && $params['body']) {
                $message->setBody($params['body']);
            }

            if (isset($params['attachments']) && is_array($params['attachments'])) {
                foreach ($params['attachments'] as $filePath) {
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
        if ($this->getConfiguration()->getSendAllMailsToBcc() !== null) {
            $message->addBcc($this->getConfiguration()->getSendAllMailsToBcc());
        }

        if ($this->getConfiguration()->getSendAllMailsTo() != null) {
            $message->setTo($this->getConfiguration()->getSendAllMailsTo());
        }

        $content = $message->getBody();

        $bodyMessage = new \Zend\Mime\Message();
        $multiPartContentMessage = new \Zend\Mime\Message();

        $text = new Part(strip_tags($content));
        $text->type = Mime::TYPE_TEXT;
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
    }
}

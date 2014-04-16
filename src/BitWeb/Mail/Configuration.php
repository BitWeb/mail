<?php

namespace BitWeb\Mail;

use BitWeb\Stdlib\AbstractConfiguration;

class Configuration extends AbstractConfiguration
{

    /**
     * @var string
     */
    protected $sendAllMailsTo = null;

    /**
     * Add additional receivers, usually empty array for local
     *
     * @var array
     */
    protected $sendAllMailsToBcc = null;

    /**
     * @param array|string $sendAllMailsTo
     * @return self
     */
    public function setSendAllMailsTo($sendAllMailsTo)
    {
        $this->sendAllMailsTo = $sendAllMailsTo;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getSendAllMailsTo()
    {
        return $this->sendAllMailsTo;
    }

    /**
     * @param array|string $sendAllMailsToBcc
     * @return self
     */
    public function setSendAllMailsToBcc($sendAllMailsToBcc)
    {
        $this->sendAllMailsToBcc = $sendAllMailsToBcc;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getSendAllMailsToBcc()
    {
        return $this->sendAllMailsToBcc;
    }
}
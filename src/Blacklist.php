<?php

namespace Aacotroneo\Saml2;

use Illuminate\Support\Facades\Cache;

class Blacklist
{
    /**
     * The unique key held within the blacklist.
     * @var string
     */
    protected $key = 'saml2.used-ids';

    /**
     * Returns an array with used message ids.
     * @return array
     */
    protected function get()
    {
        return Cache::get($this->key, []);
    }

    /**
     * Add the message id to the blacklist.
     * @param  string $messageId
     */
    public function add($messageId)
    {
        $list = $this->get();
        $list[] = $messageId;
        Cache::forever($this->key, $list);
    }

    /**
     * Determine whether the message id has been blacklisted.
     * @param  string $messageId
     * @return bool
     */
    public function has($messageId)
    {
        return in_array($messageId, $this->get());
    }

    /**
     * Remove all tokens from the blacklist.
     * @return bool
     */
    public function clear()
    {
        Cache::forget($this->key);
    }
}

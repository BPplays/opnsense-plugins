<?php

namespace OPNsense\Chrony\Api;

use OPNsense\Base\ApiMutableModelControllerBase;

class ServerController extends ApiMutableModelControllerBase
{
    protected static $internalModelClass = '\OPNsense\Chrony\Server';
    protected static $internalModelName = 'server';

    public function get()
    {
        return parent::get();
    }

    public function set()
    {
        return parent::set();
    }

    public function remove()
    {
        return parent::remove();
    }
}

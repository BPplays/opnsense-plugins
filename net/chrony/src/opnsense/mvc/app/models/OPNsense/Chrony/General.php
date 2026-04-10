<?php

namespace OPNsense\Chrony;

use OPNsense\Base\BaseModel;

class General extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->migratePeers();
    }

    private function migratePeers()
    {
        $config = $this->getConfig();
        if (!$config) {
            return;
        }

        $general = $config->get('OPNsense.chrony.general');
        if (!$general) {
            return;
        }

        $peers = $general['peers'] ?? null;
        $fallback = $general['fallbackpeers'] ?? null;

        if ($peers === null && $fallback === null) {
            return;
        }

        $servers = $config->get('OPNsense.chrony.server') ?: [];
        $newServers = [];

        if ($peers) {
            $peerList = explode(',', $peers);
            foreach ($peerList as $peer) {
                $peer = trim($peer);
                if ($peer) {
                    $newServers[] = [
                        'type' => 'server',
                        'hostname' => $peer,
                        'burst_mode' => 'iburst',
                        'minpoll' => 6,
                        'maxpoll' => 10,
                        'prefer' => 0,
                        'noselect' => 0,
                        'trust' => 0,
                        'require' => 0,
                        'port' => 123,
                        'version' => 4,
                        'offset' => 0.0,
                    ];
                }
            }
        }

        if ($fallback) {
            $fallback = trim($fallback);
            if ($fallback) {
                $newServers[] = [
                    'type' => 'server',
                    'hostname' => $fallback,
                    'burst_mode' => 'none',
                    'minpoll' => 6,
                    'maxpoll' => 10,
                    'prefer' => 0,
                    'noselect' => 0,
                    'trust' => 0,
                    'require' => 0,
                    'port' => 123,
                    'version' => 4,
                    'offset' => 0.0,
                ];
            }
        }

        if (!empty($newServers)) {
            $mergedServers = array_merge($servers, $newServers);
            $config->set('OPNsense.chrony.server', $mergedServers);
            
            // Clear old keys
            $general['peers'] = null;
            $general['fallbackpeers'] = null;
            $config->set('OPNsense.chrony.general', $general);
            
            $config->save();
        }
    }
}


<?php

namespace OPNsense\Chrony\Migrations;

use OPNsense\Base\BaseModelMigration;

class M1_0_0 extends BaseModelMigration
{
    public function run($model)
    {
        parent::run($model);

        $config = \OPNsense\Base\BaseModel::getConfig();
        $chronyConfig = $config['opnsense']['chrony'] ?? [];
        $general = $chronyConfig['general'] ?? [];

        $servers = [];

        if (isset($general['peers']) && !empty($general['peers'])) {
            $peers = $general['peers'];
            if (is_array($peers)) {
                foreach ($peers as $peer) {
                    $servers[] = [
                        'type' => 'server',
                        'hostname' => $peer,
                        'burst_mode' => 'iburst',
                    ];
                }
            } elseif (is_string($peers)) {
                $peerList = explode(',', $peers);
                foreach ($peerList as $peer) {
                    $peer = trim($peer);
                    if ($peer) {
                        $servers[] = [
                            'type' => 'server',
                            'hostname' => $peer,
                            'burst_mode' => 'iburst',
                        ];
                    }
                }
            }
        }

        if (isset($general['fallbackpeers']) && !empty($general['fallbackpeers'])) {
            $fallback = $general['fallbackpeers'];
            if (is_string($fallback)) {
                $fallback = trim($fallback);
            }
            if ($fallback) {
                $servers[] = [
                    'type' => 'server',
                    'hostname' => $fallback,
                    'burst_mode' => 'none',
                ];
            }
        }

        if (!empty($servers)) {
            $config['opnsense']['chrony']['server'] = $servers;
            \OPNsense\Base\BaseModel::saveConfig($config);
        }
    }
}

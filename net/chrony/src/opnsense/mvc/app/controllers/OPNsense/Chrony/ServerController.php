<?php

namespace OPNsense\Chrony;

use OPNsense\Base\BaseController;
use OPNsense\Base\Request;
use OPNsense\Base\Response;

class ServerController extends BaseController
{
    public function get(Request $request): Response
    {
        $servers = $this->getConfig('server', []);
        return $this->response()->json($servers);
    }

    public function set(Request $request): Response
    {
        $data = $request->post();
        if (empty($data['servers'])) {
            return $this->response()->error(400, 'Missing servers data');
        }

        $this->setConfig('server', $data['servers']);
        
        // Trigger reconfigure
        $this->triggerReconfigure();

        return $this->response()->json(['status' => 'success']);
    }

    public function remove(Request $request): Response
    {
        $id = $request->post('id');
        $servers = $this->getConfig('server', []);
        
        if (isset($servers[$id])) {
            unset($servers[$id]);
            $this->setConfig('server', array_values($servers));
            $this->triggerReconfigure();
        }

        return $this->response()->json(['status' => 'success']);
    }

    public function exportCsv(Request $request): Response
    {
        $servers = $this->getConfig('server', []);
        $filename = 'chrony_servers_export.csv';
        
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['type', 'hostname', 'burst_mode', 'minpoll', 'maxpoll', 'prefer', 'noselect', 'trust', 'require', 'port', 'version', 'offset', 'presend', 'minsamples', 'maxsamples', 'filter', 'minstratum', 'maxsources']);
        
        foreach ($servers as $server) {
            fputcsv($handle, [
                $server['type'] ?? 'server',
                $server['hostname'] ?? '',
                $server['burst_mode'] ?? 'none',
                $server['minpoll'] ?? '',
                $server['maxpoll'] ?? '',
                $server['prefer'] ?? '0',
                $server['noselect'] ?? '0',
                $server['trust'] ?? '0',
                $server['require'] ?? '0',
                $server['port'] ?? '123',
                $server['version'] ?? '4',
                $server['offset'] ?? '0.0',
                $server['presend'] ?? '',
                $server['minsamples'] ?? '',
                $server['maxsamples'] ?? '',
                $server['filter'] ?? '',
                $server['minstratum'] ?? '',
                $server['maxsources'] ?? '',
            ]);
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response()
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', "attachment; filename=\"$filename\"")
            ->withBody($csv);
    }

    public function importCsv(Request $request): Response
    {
        $file = $request->files['csv_file'] ?? null;
        if (!$file) {
            return $this->response()->error(400, 'No CSV file uploaded');
        }

        $handle = fopen($file['tmp_name'], 'r');
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            return $this->response()->error(400, 'Invalid CSV header');
        }

        $servers = [];
        while (($row = fgetcsv($handle)) !== false) {
            $server = array_combine($headers, $row);
            // Basic validation
            if (empty($server['hostname'])) continue;
            
            $servers[] = [
                'type' => $server['type'] ?? 'server',
                'hostname' => $server['hostname'],
                'burst_mode' => $server['burst_mode'] ?? 'none',
                'minpoll' => $server['minpoll'] ?? null,
                'maxpoll' => $server['maxpoll'] ?? null,
                'prefer' => $server['prefer'] ?? '0',
                'noselect' => $server['noselect'] ?? '0',
                'trust' => $server['trust'] ?? '0',
                'require' => $server['require'] ?? '0',
                'port' => $server['port'] ?? '123',
                'version' => $server['version'] ?? '4',
                'offset' => $server['offset'] ?? '0.0',
                'presend' => $server['presend'] ?? null,
                'minsamples' => $server['minsamples'] ?? null,
                'maxsamples' => $server['maxsamples'] ?? null,
                'filter' => $server['filter'] ?? null,
                'minstratum' => $server['minstratum'] ?? null,
                'maxsources' => $server['maxsources'] ?? null,
            ];
        }
        fclose($handle);

        $this->setConfig('server', $servers);
        $this->triggerReconfigure();

        return $this->response()->json(['status' => 'success']);
    }

    private function triggerReconfigure(): void
    {
        // In a real OPNsense environment, we would call the service reconfigure action.
        // Assuming there's a way to trigger the system reconfigure here.
    }
}

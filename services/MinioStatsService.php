<?php

namespace app\services;

class MinioStatsService
{
    public static function getServerInfo($alias = 'local')
    {
        $cmd = "mc admin info $alias";
        $output = shell_exec($cmd);
        return self::parseInfo($output);
    }

    protected static function parseInfo($raw)
    {
        $result = [
            'endpoint' => '',
            'status' => '',
            'uptime' => '',
            'version' => '',
            'network' => '',
            'drives' => '',
            'pool' => '',
            'storage' => [],
            'summary' => '',
            'drives_online' => '',
            'drives_offline' => '',
            'ec' => '',
        ];

        $lines = explode("\n", trim($raw));
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (preg_match('/^(?<status>[^\w\d\s])\s*(?<endpoint>[^\s]+:\d+)/u', $line, $m)) {
                $result['status'] = $m['status'];
                $result['endpoint'] = $m['endpoint'];
            } elseif (strpos($line, 'Uptime:') === 0) {
                $result['uptime'] = trim(str_replace('Uptime:', '', $line));
            } elseif (strpos($line, 'Version:') === 0) {
                $result['version'] = trim(str_replace('Version:', '', $line));
            } elseif (strpos($line, 'Network:') === 0) {
                $result['network'] = trim(str_replace('Network:', '', $line));
            } elseif (strpos($line, 'Drives:') === 0) {
                $result['drives'] = trim(str_replace('Drives:', '', $line));
            } elseif (strpos($line, 'Pool:') === 0) {
                $result['pool'] = trim(str_replace('Pool:', '', $line));
            } elseif (preg_match('/^(\d+(\.\d+)?\s\w+B) Used, (\d+) Buckets, (\d+) Objects$/', $line, $m)) {
                $result['summary'] = [
                    'used' => $m[1],
                    'buckets' => $m[3],
                    'objects' => $m[4],
                ];
            } elseif (preg_match('/^(\d+) drive online, (\d+) drives offline, EC:(\d+)/', $line, $m)) {
                $result['drives_online'] = $m[1];
                $result['drives_offline'] = $m[2];
                $result['ec'] = $m[3];
            }
            // Storage usage таблица парсится отдельно
            if (strpos($line, '│') === 0 && isset($lines[$i+1]) && strpos($lines[$i+1], '│') === 0) {
                // нашлась строка с Usage
                $headers = array_map('trim', explode('│', trim($line, '│')));
                $row = array_map('trim', explode('│', trim($lines[$i+1], '│')));
                $result['storage'] = array_combine($headers, $row);
            }
        }

        return $result;
    }
}

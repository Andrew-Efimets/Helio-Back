<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    $db = config('database.connections.mysql');
    $date = now()->format('Y-m-d_H-i');

    $backupDir = storage_path("app/backups");
    $fullPath = "{$backupDir}/db_dump_{$date}.sql";

    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $result = Process::env([
        'MYSQL_PWD' => $db['password']
    ])->run([
        '/usr/bin/mariadb-dump',
        '--host=' . $db['host'],
        '--user=' . $db['username'],
        '--skip-ssl',
        $db['database'],
        '--result-file=' . $fullPath
    ]);

    if ($result->successful() && file_exists($fullPath) && filesize($fullPath) > 0) {
        chmod($fullPath, 0644);
        logger()->info(
            "Дамп успешно создан: {$fullPath}, размер: " . filesize($fullPath)
        );
    } else {
        logger()->error("Ошибка дампа: " . $result->errorOutput());
    }

    Storage::delete(
        collect(Storage::files('backups'))
            ->filter(fn ($file) => Storage::lastModified($file) < now()->subDays(7)->getTimestamp())
            ->toArray()
    );
})->everyThirtyMinutes();

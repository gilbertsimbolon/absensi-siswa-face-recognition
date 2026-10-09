<?php
$bat = str_replace('/', '\\', __DIR__ . '/python-service/run_service.bat');
$cmd = 'powershell -WindowStyle Hidden -Command "Start-Process -FilePath \'' . $bat . '\' -WindowStyle Hidden"';
exec($cmd, $out, $ret);
echo "PowerShell Start-Process result: $ret\n";


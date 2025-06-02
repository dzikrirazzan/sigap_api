<?php
// Test timezone - hapus file ini setelah testing
echo "Server timezone: " . date_default_timezone_get() . "\n";
echo "Current time: " . date('Y-m-d H:i:s T') . "\n";
echo "Jakarta time: " . date('Y-m-d H:i:s T', time()) . "\n";

<?php

use Illuminate\Support\Facades\Schedule;

// Tự động chạy lệnh quét và xóa các nhật ký quá 90 ngày vào 00:00 mỗi ngày
Schedule::command('model:prune')->daily();
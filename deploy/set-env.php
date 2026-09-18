#!/usr/bin/env php
<?php

/**
 * يضبط مفاتيح ملف .env من سطر الأوامر دون تحرير يدوي على الاستضافة:
 *
 *   php deploy/set-env.php [--file=.env] MAIL_HOST=smtp.hostinger.com MAIL_PASSWORD ...
 *
 * KEY=VALUE يكتب القيمة، وKEY وحدها تقرأ قيمتها من متغيّر البيئة بالاسم نفسه
 * (لتمرير الأسرار بلا ظهورها في سطر الأوامر أو السجلّ). المفتاح الموجود
 * يُستبدل في مكانه والغائب يُلحَق آخر الملف، والقيم ذات الرموز الخاصة تُحاط
 * بعلامات اقتباس كما يفهمها Laravel.
 */
$file = '.env';
$pairs = [];

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--file=')) {
        $file = substr($argument, 7);

        continue;
    }

    [$key, $value] = array_pad(explode('=', $argument, 2), 2, null);

    if (! preg_match('/^[A-Z][A-Z0-9_]*$/', (string) $key)) {
        fwrite(STDERR, "مفتاح غير صالح: {$key}\n");
        exit(1);
    }

    if ($value === null) {
        $value = getenv($key);

        if ($value === false) {
            fwrite(STDERR, "المتغيّر {$key} غير موجود في البيئة\n");
            exit(1);
        }
    }

    $pairs[$key] = $value;
}

if ($pairs === []) {
    fwrite(STDERR, "الاستعمال: php deploy/set-env.php [--file=.env] KEY=VALUE KEY ...\n");
    exit(1);
}

if (! is_file($file)) {
    fwrite(STDERR, "الملف {$file} غير موجود\n");
    exit(1);
}

$quote = function (string $value): string {
    if ($value === '') {
        return '';
    }

    // بسيطة: حروف وأرقام ورموز البريد والمسارات — بلا اقتباس
    if (preg_match('/^[A-Za-z0-9_.@:\/+-]+$/', $value)) {
        return $value;
    }

    // اقتباس مفرد: لا تفسير لأي رمز داخله (كلمات المرور والأسماء العربية)
    if (! str_contains($value, "'")) {
        return "'".$value."'";
    }

    return '"'.addcslashes($value, '\\"').'"';
};

$contents = (string) file_get_contents($file);

foreach ($pairs as $key => $value) {
    $line = $key.'='.$quote($value);
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

    if (preg_match($pattern, $contents)) {
        $contents = (string) preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $contents, 1);
    } else {
        $contents = rtrim($contents, "\n")."\n".$line."\n";
    }

    echo "✓ {$key}\n";
}

file_put_contents($file, $contents);

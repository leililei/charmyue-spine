<?php

/**
 * 版本文件 → 清单下载 → aria2 下载列表
 *
 * 用法: php parse_charmyue.php <版本文件> <下载目录>
 * 示例: php parse_charmyue.php 1.0.178 charmyue
 */

$baseUrl     = 'https://myres.res.charmyue.cn/Res/StreamingAssets/WebGL/data';
$versionFile = $argv[1] ?? '1.0.178';
$downloadDir = $argv[2] ?? 'charmyue';
$urlTemplate = "{$baseUrl}/ResPackage/{prefix}/{hash}.unity3d";

// ===================== 1. 从版本文件提取清单哈希 =====================

$data = file_get_contents("{$baseUrl}/Versions/version_{$versionFile}.bytes");
$len = strlen($data);
echo "Version: $versionFile ({$len} bytes)\n";

$targetName = 'PackageManifest_ResPackage.bytes';
$hash = null;

for ($i = 0; $i < $len - 40; $i++) {
    $nameLen = ord($data[$i]) | (ord($data[$i + 1]) << 8);
    if ($nameLen !== strlen($targetName)) continue;

    $name = substr($data, $i + 2, $nameLen);
    if ($name !== $targetName) continue;

    $s = $i + 2 + $nameLen;
    $hashLen = ord($data[$s]) | (ord($data[$s + 1]) << 8);
    if ($hashLen !== 32) continue;

    $hash = substr($data, $s + 2, 32);
    if (preg_match('/^[0-9a-f]{32}$/', $hash)) break;

    $hash = null;
}

if (!$hash) {
    die("未找到 {$targetName} 的哈希!\n");
}

echo "MD5: {$hash}\n";

// ===================== 2. 下载清单 =====================

$manifestUrl = "{$baseUrl}/ManifestFiles/{$hash}/{$targetName}";
echo "URL: {$manifestUrl}\n";

$manifestData = @file_get_contents($manifestUrl);
if ($manifestData === false) {
    die("下载失败!\n");
}
$mLen = strlen($manifestData);
echo "清单: {$mLen} bytes\n\n";

// ===================== 3. 解析清单 =====================

$entries = [];
for ($i = 0; $i < $mLen - 42; $i++) {
    $n = ord($manifestData[$i]);
    if ($n < 10 || $n > 200) continue;
    if (ord($manifestData[$i + 1]) !== 0x00) continue;

    $filename = substr($manifestData, $i + 2, $n);
    if (substr($filename, -8) !== '.unity3d') continue;
    if (!preg_match('/^[\x20-\x7E]+$/', $filename)) continue;

    $s = $i + 2 + $n;
    if (ord($manifestData[$s + 4]) !== 0x20 || ord($manifestData[$s + 5]) !== 0x00) continue;

    $fileHash = substr($manifestData, $s + 6, 32);
    if (!preg_match('/^[\x20-\x7E]{32}$/', $fileHash)) continue;

    if (ord($manifestData[$s + 38]) !== 0x08 || ord($manifestData[$s + 39]) !== 0x00) continue;

    if (strpos($filename, 'spine_') !== 0) continue;

    $entries[$fileHash] = $filename;
}

echo "spine_ 资源: " . count($entries) . "\n\n";

// ===================== 4. 输出 =====================

$fp = fopen('aria2_charmyue.txt', 'w');
foreach ($entries as $h => $f) {
    $prefix = substr($h, 0, 2);
    $url = str_replace(['{hash}', '{prefix}'], [$h, $prefix], $urlTemplate);
    fwrite($fp, "{$url}\n  out={$f}\n");
}
fclose($fp);

echo "Output: aria2_charmyue.txt\n";
echo "Run: aria2c -i aria2_charmyue.txt -d \"{$downloadDir}\" --continue\n";

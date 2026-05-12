<?php
/**
 * Generates a restaurant-grade notification WAV file.
 * Three descending "ding" tones at high amplitude — audible over music/conversation.
 *
 * Run once:  php generate_notification_sound.php
 * Output:    public/sounds/notification.wav
 */

$outputPath  = __DIR__ . '/public/sounds/notification.wav';
$sampleRate  = 44100;
$channels    = 1;          // mono
$bitsPerSmp  = 16;
$amplitude   = 0.95;       // near-max volume

// ── Tone sequence ────────────────────────────────────────────────────────────
// Each entry: [frequency_hz, duration_seconds]
$sequence = [
    [1046, 0.25],   // C6  – high ding
    [880,  0.25],   // A5  – mid  ding
    [698,  0.40],   // F5  – low  dong  (longest tail)
    [0,    0.05],   // short silence at end
];

// ── Build PCM samples ────────────────────────────────────────────────────────
$samples = [];

foreach ($sequence as [$freq, $dur]) {
    $numSamples = (int)($dur * $sampleRate);

    for ($i = 0; $i < $numSamples; $i++) {
        if ($freq === 0) {
            $samples[] = 0;
            continue;
        }

        $t = $i / $sampleRate;

        // Smooth attack (10 ms) + decay envelope for natural bell-like tone
        $attackSamples = (int)(0.01 * $sampleRate);
        $attack  = min($i / max($attackSamples, 1), 1.0);
        $decay   = exp(-4.0 * $t / $dur);           // exponential decay
        $envelope = $attack * $decay;

        // Mix fundamental + 2nd harmonic (50%) for richer bell tone
        $wave = sin(2 * M_PI * $freq * $t)
              + 0.50 * sin(2 * M_PI * $freq * 2 * $t)
              + 0.25 * sin(2 * M_PI * $freq * 3 * $t);
        $wave /= 1.75; // normalise harmonics

        $sample    = (int)($amplitude * $envelope * $wave * 32767);
        $sample    = max(-32768, min(32767, $sample)); // clamp
        $samples[] = $sample;
    }
}

// ── WAV binary ───────────────────────────────────────────────────────────────
$dataSize  = count($samples) * ($bitsPerSmp / 8);
$byteRate  = $sampleRate * $channels * ($bitsPerSmp / 8);
$blockAlign = $channels * ($bitsPerSmp / 8);

$header = pack('A4V',  'RIFF', 36 + $dataSize)
        . pack('A4',   'WAVE')
        . pack('A4V',  'fmt ', 16)
        . pack('vv',   1, $channels)            // PCM, mono
        . pack('V',    $sampleRate)
        . pack('V',    $byteRate)
        . pack('vv',   $blockAlign, $bitsPerSmp)
        . pack('A4V',  'data', $dataSize);

$pcm = '';
foreach ($samples as $s) {
    $pcm .= pack('s', $s);  // signed 16-bit little-endian
}

file_put_contents($outputPath, $header . $pcm);

echo "✅  Generated: public/sounds/notification.wav\n";
echo "    Duration : " . round(count($samples) / $sampleRate, 2) . " seconds\n";
echo "    Size     : " . number_format(filesize($outputPath)) . " bytes\n";


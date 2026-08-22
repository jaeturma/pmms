<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrationCodeChallenge
{
    private const SESSION_KEY = 'registration_code_challenge';

    public function generate(Request $request): string
    {
        $code = app()->environment('testing') ? 'ABC12' : Str::upper(Str::random(5));
        $request->session()->put(self::SESSION_KEY, hash_hmac('sha256', $code, (string) config('app.key')));

        $image = imagecreatetruecolor(260, 56);
        $background = imagecolorallocate($image, 241, 245, 249);
        $ink = imagecolorallocate($image, 15, 23, 42);
        $noise = imagecolorallocate($image, 148, 163, 184);
        imagefill($image, 0, 0, $background);

        for ($i = 0; $i < 8; $i++) {
            imageline($image, random_int(0, 260), random_int(0, 56), random_int(0, 260), random_int(0, 56), $noise);
        }

        foreach (str_split($code) as $index => $character) {
            $glyph = imagecreatetruecolor(10, 16);
            imagefill($glyph, 0, 0, $background);
            imagestring($glyph, 5, 0, 0, $character, $ink);
            imagecopyresized($image, $glyph, 16 + ($index * 46), random_int(8, 13), 0, 0, 24, 38, 10, 16);
            imagedestroy($glyph);
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function verify(Request $request, ?string $answer): bool
    {
        $expected = $request->session()->pull(self::SESSION_KEY);

        return is_string($expected) && is_string($answer) && hash_equals(
            $expected,
            hash_hmac('sha256', Str::upper(trim($answer)), (string) config('app.key')),
        );
    }
}
